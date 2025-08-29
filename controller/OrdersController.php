<?php
// controller/OrdersController.php - FIXED VERSION with proper delete
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

    // FIXED: Properly handle cancel/delete action
    public function cancel(): void {
        AuthController::requireStaff();
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "ID de commande invalide";
            $this->redirectToDashboard();
            return;
        }

        // Check if order exists and is not already cancelled
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

        // Cancel the order (soft delete)
        if ($this->commandeModel->updateStatus($id, 'supprimee')) {
            $_SESSION['success'] = "Commande #" . $id . " annulée avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de l'annulation de la commande";
        }

        $this->redirectToDashboard();
    }

    // FIXED: Remove old delete method that wasn't working
    public function delete(): void {
        // Redirect to cancel method instead
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

        if ($id <= 0) {
            $_SESSION['error'] = "ID de commande invalide";
            $this->redirectToDashboard();
            return;
        }

        $allowedStatuses = ['en_attente', 'en_cours', 'terminee'];
        if (!in_array($statut, $allowedStatuses)) {
            $_SESSION['error'] = "Statut invalide";
            $this->redirectToDashboard();
            return;
        }

        // Check if order exists
        $order = $this->commandeModel->getById($id);
        if (!$order) {
            $_SESSION['error'] = "Commande introuvable";
            $this->redirectToDashboard();
            return;
        }

        // Update status
        if ($this->commandeModel->updateStatus($id, $statut)) {
            $_SESSION['success'] = "Statut mis à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du statut";
        }

        $this->redirectToDashboard();
    }

    // Helper method to redirect to dashboard
    private function redirectToDashboard(): void {
        header('Location: index.php?controller=dashboard&section=commandes');
        exit;
    }

    // FIXED: Client order history - shows ALL orders including cancelled ones
    public function historique(): void {
        AuthController::requireClient();
        
        $clientId = $_SESSION['user']['id'];
        
        try {
            // Get ALL orders for current client, including cancelled ones
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

        // Get order and verify it belongs to current client
        $order = $this->commandeModel->getById($id);
        if (!$order || $order['id_client'] != $clientId) {
            $_SESSION['error'] = "Commande introuvable ou accès non autorisé";
            header('Location: index.php?controller=commandes&action=historique');
            exit;
        }

        $lines = $this->ligneModel->getByCommandeId($id);

        include __DIR__ . '/../view/client/order_details.php';
    }

    // For client: create a new order with products
    public function create(): void {
        AuthController::requireClient();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=client&action=catalogue');
            exit;
        }

        $id_client = $_SESSION['user']['id'];
        $products = $_POST['products'] ?? []; // array: ['product_id' => quantity]

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

                $this->ligneModel->create($id_commande, $id_produit, $qty, $prod['prix']);
            }

            $_SESSION['success'] = "Commande créée avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la création de la commande: " . $e->getMessage();
        }

        header('Location: index.php?controller=client&action=catalogue');
        exit;
    }

    // FIXED: View method for both staff and clients
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

        $lines = $this->ligneModel->getByCommandeId($id);

        // Check if we're coming from dashboard
        //$fromDashboard = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'dashboard') !== false;
        $fromDashboard = isset($_GET['from_dashboard']);

        if ($fromDashboard || isset($_GET['from_dashboard'])) {
            // Dashboard context - use enhanced dashboard
            $user = $_SESSION['user'];
            $section = 'commandes';
            $action = 'view';
            $stats = $this->getBasicStats();
            
            include __DIR__ . '/../view/orders/view.php';
        } else {
            // Standalone view
            include __DIR__ . '/../view/orders/view.php';
        }
    }

    // FIXED: Index method for staff order management
    public function index(): void {
        AuthController::requireStaff();

        // Get search and filter parameters
        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status_filter'] ?? '');

        // Get orders with proper filtering
        $orders = $this->getOrdersWithTotals($search, $statusFilter);

        // Check if we're being called from dashboard
        if (isset($_GET['controller']) && $_GET['controller'] === 'dashboard') {
            return; // Let dashboard handle the display
        }

        // Standalone orders page
        include __DIR__ . '/../view/orders/list.php';
    }

    // Helper method to get orders with totals
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
            } else {
                // By default, exclude deleted orders unless specifically requested
                $sql .= " AND c.statut != 'supprimee'";
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

    // Helper method for basic stats
    private function getBasicStats(): array {
        try {
            $db = Database::getConnection();
            return [
                'commandes_en_attente' => 0, // Placeholder
                'revenus_semaine' => 0,
                'produits_en_stock' => 0,
                'clients_actifs' => 0
            ];
        } catch (Exception $e) {
            return [];
        }
    }
}
?>