<?php
// controller/DashboardController.php - FIXED VERSION
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
        AuthController::requireStaff();
        
        $section = $_GET['section'] ?? 'home';
        $action = $_GET['action'] ?? 'index';
        
        // Handle product actions
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

        // Handle orders section
        $orders = [];
        if ($section === 'commandes') {
            $commandeModel = new Commande();
            $ligneModel = new LigneCommande();

            $search = $_GET['search'] ?? '';
            $status = $_GET['status_filter'] ?? '';

            // FIXED: Show ALL orders including cancelled ones
            $orders = $this->getOrdersWithTotals($search, $status);
        }

        // Get product for editing if needed
        $product = null;
        if ($section === 'produits' && $action === 'form' && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            if ($id > 0) {
                $product = $this->productModel->getById($id);
            }
        }

        include __DIR__ . '/../view/dashboard.php';
    }

    // FIXED: Get orders with totals - show ALL orders including cancelled
    private function getOrdersWithTotals(string $search = '', string $statusFilter = ''): array {
        try {
            $db = Database::getConnection();
            
            $sql = "
                SELECT c.*, 
                       CONCAT(u.prenom, ' ', u.nom) AS nom_client,
                       COALESCE(order_totals.total, 0) as total
                FROM commandes c
                LEFT JOIN utilisateurs u ON c.id_client = u.id
                LEFT JOIN (
                    SELECT id_commande, SUM(quantite * prix_unitaire) as total
                    FROM lignes_commande
                    GROUP BY id_commande
                ) order_totals ON c.id = order_totals.id_commande
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($statusFilter)) {
                $sql .= " AND c.statut = ?";
                $params[] = $statusFilter;
            }
            // REMOVED: No longer excluding cancelled orders by default
            // This shows ALL orders including cancelled ones
            
            $sql .= " ORDER BY c.date_commande DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting orders with totals: " . $e->getMessage());
            return [];
        }
    }

    // Rest of your existing methods...
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
            
            // Count pending orders
            $stmt = $db->prepare("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'");
            $stmt->execute();
            $commandesEnAttente = (int)$stmt->fetchColumn();
            
            // Count products in stock
            $stmt = $db->prepare("SELECT COUNT(*) FROM produits WHERE stock > 0");
            $stmt->execute();
            $produitsEnStock = (int)$stmt->fetchColumn();
            
            // Count clients
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
            $_SESSION['error'] = 'Erreur lors de la récupération des produits: ' . $e->getMessage();
            return [];
        }
    }

    private function getUsers(): array {
        try {
            $db = Database::getConnection();
            $search = $_GET['search'] ?? '';

            if (!empty($search)) {
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
}
?>