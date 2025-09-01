<?php
// controller/OrdersController.php - VERSION AVEC DEBUG COMPLET
require_once __DIR__ . '/../model/Commande.php';
require_once __DIR__ . '/../model/LigneCommande.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../model/Product.php';

final class OrdersController {
    private Commande $commandeModel;
    private LigneCommande $ligneModel;
    private Product $productModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->commandeModel = new Commande();
        $this->ligneModel = new LigneCommande();
        $this->productModel = new Product();
    }

    // MÉTHODE VIEW AVEC DEBUG COMPLET
    public function view(): void {
        // Debug: Afficher toutes les données reçues
        error_log("=== DEBUG VIEW METHOD ===");
        error_log("GET params: " . print_r($_GET, true));
        error_log("SESSION user: " . print_r($_SESSION['user'] ?? 'No user', true));
        
        // Check if user is client, redirect to clientView
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'client') {
            error_log("Redirecting to clientView for client");
            $this->clientView();
            return;
        }
        
        // Staff viewing order
        AuthController::requireStaff();

        $id = (int)($_GET['id'] ?? 0);
        error_log("Order ID extracted: " . $id);
        
        if ($id <= 0) {
            error_log("Invalid ID: " . $id);
            $_SESSION['error'] = "Commande invalide - ID: " . $id;
            $this->redirectToDashboard();
            return;
        }

        // DEBUG: Tester la récupération directe avec la base de données
        try {
            require_once __DIR__ . '/../model/Database.php';
            $db = Database::getConnection();
            
            // Test direct de la base de données
            $stmt = $db->prepare("SELECT * FROM commandes WHERE id = ?");
            $stmt->execute([$id]);
            $orderDirect = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Direct DB query result: " . print_r($orderDirect, true));
            
            // Test avec le modèle
            $orderModel = $this->commandeModel->getById($id);
            error_log("Model query result: " . print_r($orderModel, true));
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
        }

        // Utiliser la méthode du modèle
        $order = $this->commandeModel->getById($id);
        
        if (!$order) {
            error_log("Order not found with ID: " . $id);
            
            // DEBUG: Lister toutes les commandes pour voir ce qui existe
            try {
                $stmt = $db->prepare("SELECT id, statut FROM commandes LIMIT 10");
                $stmt->execute();
                $allOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Available orders: " . print_r($allOrders, true));
            } catch (Exception $e) {
                error_log("Error listing orders: " . $e->getMessage());
            }
            
            $_SESSION['error'] = "Commande introuvable - ID recherché: " . $id;
            $this->redirectToDashboard();
            return;
        }

        error_log("Order found: " . print_r($order, true));

        // Role-based access control
        $userRole = $_SESSION['user']['role'];
        if ($userRole === 'preparateur') {
            if (!in_array($order['statut'], ['en_attente', 'en_cours'])) {
                $_SESSION['error'] = "Vous ne pouvez voir que les commandes en attente ou en cours";
                $this->redirectToDashboard();
                return;
            }
        }

        // Récupérer les lignes de commande
        $lines = $this->ligneModel->getByCommandeId($id);
        error_log("Order lines found: " . count($lines));
        error_log("Lines data: " . print_r($lines, true));

        // Préparer les variables pour la vue
        $content = 'order_details';
        $orderDetails = $order;
        $orderLines = $lines;
        $ligneModel = $this->ligneModel;

        // Calculer le total
        $orderTotal = 0;
        foreach ($lines as $line) {
            $orderTotal += $line['quantite'] * $line['prix_unitaire'];
        }

        error_log("Order total calculated: " . $orderTotal);
        error_log("Including view file...");

        // Inclure la vue
        include __DIR__ . '/../view/dashboard_with_content.php';
    }

    // MÉTHODE ALTERNATIVE SIMPLIFIÉE POUR TESTER
    public function viewSimple(): void {
        AuthController::requireStaff();
        
        $id = (int)($_GET['id'] ?? 0);
        
        // Test direct sans modèle
        try {
            require_once __DIR__ . '/../model/Database.php';
            $db = Database::getConnection();
            
            // Récupération directe
            $stmt = $db->prepare("
                SELECT c.*, CONCAT(u.prenom, ' ', u.nom) AS nom_client
                FROM commandes c
                LEFT JOIN utilisateurs u ON c.id_client = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                echo "Commande ID $id introuvable dans la base de données";
                return;
            }
            
            // Récupérer les lignes
            $stmt = $db->prepare("
                SELECT lc.*, p.nom as nom_produit
                FROM lignes_commande lc
                LEFT JOIN produits p ON lc.id_produit = p.id
                WHERE lc.id_commande = ?
            ");
            $stmt->execute([$id]);
            $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Affichage simple pour debug
            echo "<h2>Commande #" . $order['id'] . "</h2>";
            echo "<p>Client: " . $order['nom_client'] . "</p>";
            echo "<p>Statut: " . $order['statut'] . "</p>";
            echo "<h3>Articles (" . count($lines) . "):</h3>";
            
            if (empty($lines)) {
                echo "<p>Aucune ligne de commande trouvée</p>";
            } else {
                echo "<ul>";
                foreach ($lines as $line) {
                    echo "<li>" . $line['nom_produit'] . " - Qty: " . $line['quantite'] . " - Prix: " . $line['prix_unitaire'] . "€</li>";
                }
                echo "</ul>";
            }
            
        } catch (Exception $e) {
            echo "Erreur: " . $e->getMessage();
        }
    }

    // Cancel action - admin only
    public function cancel(): void {
        AuthController::requireAdmin();
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "ID de commande invalide";
            $this->redirectToDashboard();
            return;
        }

        $order = $this->commandeModel->getById($id);
        if (!$order) {
            $_SESSION['error'] = "Commande introuvable";
            $this->redirectToDashboard();
            return;
        }

        if ($order['statut'] === 'supprimee') {
            $_SESSION['error'] = "Cette commande est déjà annulée";
            $this->redirectToDashboard();
            return;
        }

        if ($this->commandeModel->updateStatus($id, 'supprimee')) {
            $_SESSION['success'] = "Commande #" . $id . " annulée avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de l'annulation de la commande";
        }

        $this->redirectToDashboard();
    }

    public function delete(): void {
        $this->cancel();
    }

    public function updateStatus(): void {
        AuthController::requireStaff();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToDashboard();
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $statut = trim($_POST['statut'] ?? '');
        $userRole = $_SESSION['user']['role'];

        if ($id <= 0) {
            $_SESSION['error'] = "ID de commande invalide";
            $this->redirectToDashboard();
            return;
        }

        $allowedStatuses = [];
        if ($userRole === 'preparateur') {
            $allowedStatuses = ['en_cours', 'terminee'];
        } elseif ($userRole === 'admin') {
            $allowedStatuses = ['en_attente', 'en_cours', 'terminee'];
        }

        if (!in_array($statut, $allowedStatuses)) {
            $_SESSION['error'] = "Statut invalide pour votre rôle";
            $this->redirectToDashboard();
            return;
        }

        $order = $this->commandeModel->getById($id);
        if (!$order) {
            $_SESSION['error'] = "Commande introuvable";
            $this->redirectToDashboard();
            return;
        }

        if ($userRole === 'preparateur') {
            if (!in_array($order['statut'], ['en_attente', 'en_cours'])) {
                $_SESSION['error'] = "Vous ne pouvez modifier que les commandes en attente ou en cours";
                $this->redirectToDashboard();
                return;
            }
            
            if ($order['statut'] === 'en_attente' && $statut !== 'en_cours') {
                $_SESSION['error'] = "Les commandes en attente ne peuvent que passer en cours";
                $this->redirectToDashboard();
                return;
            }
            
            if ($order['statut'] === 'en_cours' && $statut !== 'terminee') {
                $_SESSION['error'] = "Les commandes en cours ne peuvent que passer en terminée";
                $this->redirectToDashboard();
                return;
            }
        }

        if ($this->commandeModel->updateStatus($id, $statut)) {
            $_SESSION['success'] = "Statut mis à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du statut";
        }

        $this->redirectToDashboard();
    }

    private function redirectToDashboard(): void {
        header('Location: index.php?controller=dashboard&section=commandes');
        exit;
    }

    public function historique(): void {
        AuthController::requireClient();
        
        $clientId = $_SESSION['user']['id'];
        
        try {
            $orders = $this->commandeModel->getByClientId($clientId);
            $ligneModel = $this->ligneModel;
            
            include __DIR__ . '/../view/orders/orders_history.php';
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la récupération des commandes";
            header('Location: index.php?controller=client&action=catalogue');
            exit;
        }
    }

    public function clientView(): void {
        AuthController::requireClient();
        
        $id = (int)($_GET['id'] ?? 0);
        $clientId = $_SESSION['user']['id'];
        
        if ($id <= 0) {
            $_SESSION['error'] = "Commande invalide";
            header('Location: index.php?controller=commandes&action=historique');
            exit;
        }

        $order = $this->commandeModel->getById($id);
        if (!$order || $order['id_client'] != $clientId) {
            $_SESSION['error'] = "Commande introuvable ou accès non autorisé";
            header('Location: index.php?controller=commandes&action=historique');
            exit;
        }

        $lines = $this->ligneModel->getByCommandeId($id);

        include __DIR__ . '/../view/client/order_details.php';
    }

    public function create(): void {
        AuthController::requireClient();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=client&action=catalogue');
            exit;
        }

        $id_client = $_SESSION['user']['id'];
        $products = $_POST['products'] ?? [];

        if (empty($products)) {
            $_SESSION['error'] = "Veuillez sélectionner au moins un produit.";
            header('Location: index.php?controller=client&action=catalogue');
            exit;
        }

        try {
            $id_commande = $this->commandeModel->create($id_client);

            foreach ($products as $id_produit => $qty) {
                $id_produit = (int)$id_produit;
                $qty = (int)$qty;
                if ($qty <= 0) continue;

                $prod = $this->productModel->getById($id_produit);
                if (!$prod) continue;

                if ($prod['stock'] < $qty) {
                    $_SESSION['error'] = "Stock insuffisant pour " . $prod['nom'];
                    header('Location: index.php?controller=client&action=catalogue');
                    exit;
                }

                $this->ligneModel->create($id_commande, $id_produit, $qty, $prod['prix']);
                
                $newStock = $prod['stock'] - $qty;
                $this->productModel->update($id_produit, ['stock' => $newStock]);
            }

            $_SESSION['success'] = "Commande créée avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la création de la commande: " . $e->getMessage();
        }

        header('Location: index.php?controller=client&action=catalogue');
        exit;
    }

    public function index(): void {
        AuthController::requireStaff();
        header('Location: index.php?controller=dashboard&section=commandes');
        exit;
    }

    // MÉTHODE DE DEBUG POUR TESTER
    public function debug(): void {
        echo "<h2>DEBUG OrdersController</h2>";
        echo "<h3>Paramètres GET:</h3>";
        var_dump($_GET);
        
        echo "<h3>Session:</h3>";
        var_dump($_SESSION);
        
        $id = (int)($_GET['id'] ?? 0);
        echo "<h3>ID extrait: $id</h3>";
        
        if ($id > 0) {
            try {
                require_once __DIR__ . '/../model/Database.php';
                $db = Database::getConnection();
                
                echo "<h3>Test connexion DB: OK</h3>";
                
                // Lister toutes les commandes
                $stmt = $db->prepare("SELECT id, statut, date_commande FROM commandes");
                $stmt->execute();
                $allOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h3>Toutes les commandes dans la DB:</h3>";
                foreach ($allOrders as $ord) {
                    echo "ID: " . $ord['id'] . " - Statut: " . $ord['statut'] . " - Date: " . $ord['date_commande'] . "<br>";
                }
                
                // Test de la commande spécifique
                $stmt = $db->prepare("SELECT * FROM commandes WHERE id = ?");
                $stmt->execute([$id]);
                $specificOrder = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<h3>Commande ID $id:</h3>";
                if ($specificOrder) {
                    var_dump($specificOrder);
                } else {
                    echo "AUCUNE COMMANDE TROUVÉE AVEC L'ID $id";
                }
                
                // Test avec le modèle
                echo "<h3>Test avec le modèle Commande:</h3>";
                $modelResult = $this->commandeModel->getById($id);
                if ($modelResult) {
                    var_dump($modelResult);
                } else {
                    echo "MODÈLE RETOURNE NULL POUR L'ID $id";
                }
                
            } catch (Exception $e) {
                echo "Erreur DB: " . $e->getMessage();
            }
        }
    }

    // MÉTHODES RESTANTES INCHANGÉES...
    
    private function getPreparatorOrders(string $search = ''): array {
        try {
            require_once __DIR__ . '/../model/Database.php';
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
                WHERE c.statut IN ('en_attente', 'en_cours')
            ";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql .= " ORDER BY 
                        CASE 
                            WHEN c.statut = 'en_attente' THEN 1 
                            WHEN c.statut = 'en_cours' THEN 2 
                        END,
                        c.date_commande ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting preparator orders: " . $e->getMessage());
            return [];
        }
    }

    private function getOrdersWithTotals(string $search = '', string $statusFilter = ''): array {
        try {
            require_once __DIR__ . '/../model/Database.php';
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
            
            $sql .= " ORDER BY c.date_commande DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting orders with totals: " . $e->getMessage());
            return [];
        }
    }
}
?>