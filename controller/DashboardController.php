<?php
// controller/DashboardController.php
require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../model/Product.php';
require_once __DIR__ . '/../model/Commande.php';
require_once __DIR__ . '/../model/LigneCommande.php';





final class DashboardController {
    private Product $productModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->productModel = new Product();
    }

    public function index(): void {
    // Vérifier que l'utilisateur est admin ou préparateur
    AuthController::requireStaff();
    
    // Section actuelle
    $section = $_GET['section'] ?? 'home';
    $action = $_GET['action'] ?? 'index';
    
    // Handle ONLY the actions that need processing, not form display
    if ($section === 'produits') {
        switch ($action) {
            case 'store':
                $this->storeProduct();
                return;
            case 'update':
                $this->updateProduct();
                return;
            case 'delete':
                $this->deleteProduct();
                return;
        }
    }
    
    // Get data for display
    $stats = $this->getStats();
    $products = ($section === 'produits') ? $this->getProducts() : [];
    $users = [];
    if ($section === 'utilisateurs' && $_SESSION['user']['role'] === 'admin') {
        $users = $this->getUsers();
    }

    $orders = [];
    if ($section === 'commandes') {
        $commandeModel = new Commande();
        $ligneModel = new LigneCommande();

        // filters
        $search = $_GET['search'] ?? '';
        $status = $_GET['status_filter'] ?? '';

        $orders = $commandeModel->getAll($search, $status);
        foreach ($orders as &$order) {
            $order['lignes'] = $ligneModel->getByCommandeId($order['id']);
        }
    }

    // Get product for editing if needed
    $product = null;
    if ($section === 'produits' && $action === 'form' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if ($id > 0) {
            $product = $this->productModel->getById($id);
        }
    }

    // Show the dashboard view (this view itself will include the right section: produits, commandes, etc.)
    include __DIR__ . '/../view/dashboard.php';
}


    private function storeProduct(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=dashboard&section=produits');
            exit;
        }

        try {
            $nom = trim($_POST['nom'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $prix = (float)($_POST['prix'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $categorie = $_POST['categorie'] ?? 'autre';

            if (empty($nom) || $prix <= 0) {
                $_SESSION['error'] = 'Le nom et le prix sont obligatoires.';
            } else {
                $data = [
                    'nom' => $nom,
                    'description' => $description,
                    'prix' => $prix,
                    'stock' => $stock,
                    'categorie' => $categorie
                ];

                $this->productModel->create($data);
                $_SESSION['success'] = 'Produit créé avec succès.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la création: ' . $e->getMessage();
        }

        header('Location: index.php?controller=dashboard&section=produits');
        exit;
    }

    private function updateProduct(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=dashboard&section=produits');
            exit;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            $nom = trim($_POST['nom'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $prix = (float)($_POST['prix'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $categorie = $_POST['categorie'] ?? 'autre';

            if ($id <= 0 || empty($nom) || $prix <= 0) {
                $_SESSION['error'] = 'Données invalides.';
            } else {
                $data = [
                    'nom' => $nom,
                    'description' => $description,
                    'prix' => $prix,
                    'stock' => $stock,
                    'categorie' => $categorie
                ];

                $this->productModel->update($id, $data);
                $_SESSION['success'] = 'Produit mis à jour avec succès.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la mise à jour: ' . $e->getMessage();
        }

        header('Location: index.php?controller=dashboard&section=produits');
        exit;
    }

    private function deleteProduct(): void {
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            $_SESSION['error'] = 'ID invalide.';
        } else {
            try {
                $this->productModel->delete($id);
                $_SESSION['success'] = 'Produit supprimé avec succès.';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
            }
        }

        header('Location: index.php?controller=dashboard&section=produits');
        exit;
    }

    private function getStats(): array {
        try {
            $db = Database::getConnection();
            
            // Count pending orders - using lowercase table name
            $stmt = $db->prepare("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'");
            $stmt->execute();
            $commandesEnAttente = (int)$stmt->fetchColumn();
            
            // Count products in stock - using lowercase table name
            $stmt = $db->prepare("SELECT COUNT(*) FROM produits WHERE stock > 0");
            $stmt->execute();
            $produitsEnStock = (int)$stmt->fetchColumn();
            
            // Count clients - using lowercase table name
            $stmt = $db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client'");
            $stmt->execute();
            $clientsInscrits = (int)$stmt->fetchColumn();
            
            return [
                'commandes_en_attente' => $commandesEnAttente,
                'produits_en_stock' => $produitsEnStock,
                'clients_inscrits' => $clientsInscrits
            ];
        } catch (Exception $e) {
            error_log("Error getting stats: " . $e->getMessage());
            return [
                'commandes_en_attente' => 0,
                'produits_en_stock' => 0,
                'clients_inscrits' => 0
            ];
        }
    }

    private function getProducts(): array {
        try {
            $db = Database::getConnection();
            $search = $_GET['search'] ?? '';

            if (!empty($search)) {
                // Fixed query - using positional parameters instead of named ones
                $stmt = $db->prepare("SELECT * FROM produits 
                                      WHERE nom LIKE ? 
                                         OR categorie LIKE ? 
                                         OR description LIKE ?
                                      ORDER BY date_ajout DESC");
                $searchParam = "%$search%";
                $stmt->execute([$searchParam, $searchParam, $searchParam]);
            } else {
                $stmt = $db->query("SELECT * FROM produits ORDER BY date_ajout DESC");
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting products: " . $e->getMessage());
            // If there's an error, let's try to debug it
            $_SESSION['error'] = 'Erreur lors de la récupération des produits: ' . $e->getMessage();
            return [];
        }
    }

    private function getUsers(): array {
        try {
            $db = Database::getConnection();
            $search = $_GET['search'] ?? '';

            if (!empty($search)) {
                // Fixed query - using lowercase table name and single parameter
                $stmt = $db->prepare("SELECT id, nom, prenom, email, role, date_creation 
                                      FROM utilisateurs 
                                      WHERE nom LIKE ? 
                                         OR prenom LIKE ? 
                                         OR email LIKE ?
                                      ORDER BY date_creation DESC");
                $searchParam = "%$search%";
                $stmt->execute([$searchParam, $searchParam, $searchParam]);
            } else {
                $stmt = $db->query("SELECT id, nom, prenom, email, role, date_creation 
                                    FROM utilisateurs 
                                    ORDER BY date_creation DESC");
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting users: " . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage();
            return [];
        }
    }

    private function getOrders(): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT c.id, c.id_client, c.date_commande, c.statut, u.nom AS nom_client
                FROM commandes c
                LEFT JOIN utilisateurs u ON c.id_client = u.id
                ORDER BY c.date_commande DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des commandes: " . $e->getMessage());
            return [];
        }
    }
}
?>