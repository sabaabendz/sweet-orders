<?php
// controller/OrdersController.php - FIXED VERSION with proper role-based access
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

    // FIXED: Cancel action - admin only
    public function cancel(): void {
        AuthController::requireAdmin(); // Only admins can cancel orders
        
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
        // Redirect to cancel method instead
        $this->cancel();
    }

    // FIXED: Update status with role-based restrictions
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

        // FIXED: Role-based status validation
        $allowedStatuses = [];
        if ($userRole === 'preparateur') {
            // Preparateur can move orders: en_attente -> en_cours -> terminee
            $allowedStatuses = ['en_cours', 'terminee'];
        } elseif ($userRole === 'admin') {
            // Admin can set any status except 'supprimee' (use cancel action for that)
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

        // FIXED: Additional validation for preparateur - now allows both en_attente and en_cours
        if ($userRole === 'preparateur') {
            // Preparateur can only modify orders that are 'en_attente' or 'en_cours'
            if (!in_array($order['statut'], ['en_attente', 'en_cours'])) {
                $_SESSION['error'] = "Vous ne pouvez modifier que les commandes en attente ou en cours";
                $this->redirectToDashboard();
                return;
            }
            
            // Additional logic: en_attente can only go to en_cours, en_cours can only go to terminee
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

    // Client order history - shows ALL orders including cancelled ones
    public function historique(): void {
        AuthController::requireClient();
        
        $clientId = $_SESSION['user']['id'];
        
        try {
            $orders = $this->commandeModel->getByClientId($clientId);
            $ligneModel = $this->ligneModel; // Make it available to the view
            
            include __DIR__ . '/../view/orders/orders_history.php';
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la récupération des commandes";
            header('Location: index.php?controller=client&action=catalogue');
            exit;
        }
    }

    // Client view method for order details
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

    // Create a new order (client only)
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

                // Check stock availability
                if ($prod['stock'] < $qty) {
                    $_SESSION['error'] = "Stock insuffisant pour " . $prod['nom'];
                    header('Location: index.php?controller=client&action=catalogue');
                    exit;
                }

                $this->ligneModel->create($id_commande, $id_produit, $qty, $prod['prix']);
                
                // Update stock
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

    // FIXED: View method with proper role-based access and dashboard integration
    public function view(): void {
        // Check if user is client, redirect to clientView
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'client') {
            $this->clientView();
            return;
        }
        
        // Staff viewing order
        AuthController::requireStaff();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "Commande invalide";
            $this->redirectToDashboard();
            return;
        }

        $order = $this->commandeModel->getById($id);
        if (!$order) {
            $_SESSION['error'] = "Commande introuvable";
            $this->redirectToDashboard();
            return;
        }

        // FIXED: Role-based access control for viewing orders
        $userRole = $_SESSION['user']['role'];
        if ($userRole === 'preparateur') {
            // Preparateur can only see orders that are 'en_attente' or 'en_cours'
            if (!in_array($order['statut'], ['en_attente', 'en_cours'])) {
                $_SESSION['error'] = "Vous ne pouvez voir que les commandes en attente ou en cours";
                $this->redirectToDashboard();
                return;
            }
        }

        $lines = $this->ligneModel->getByCommandeId($id);

        // FIXED: Always use dashboard template for staff - no standalone views
        include __DIR__ . '/../view/dashboard_with_content.php';
    }

    // FIXED: Index method - always redirect to dashboard for staff
    public function index(): void {
        AuthController::requireStaff();

        // FIXED: Always redirect staff to dashboard orders section
        header('Location: index.php?controller=dashboard&section=commandes');
        exit;
    }

    // NEW: Get orders for preparators - pending and in progress orders
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
                        c.date_commande ASC"; // Priority: pending first, then in progress, oldest first
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting preparator orders: " . $e->getMessage());
            return [];
        }
    }

    // Helper method to get orders with totals (for admin)
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