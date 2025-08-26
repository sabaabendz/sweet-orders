<?php
// controller/OrdersController.php - FIXED VERSION
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

    public function index(): void {
        AuthController::requireStaff(); // Only admin/preparer can see all orders

        $orders = $this->commandeModel->getAll();
        
        foreach ($orders as &$order) {
            // Get lines for each order to show total
            $lines = $this->ligneModel->getByCommandeId($order['id']);
            $order['total'] = 0;
            foreach ($lines as $line) {
                $order['total'] += $line['quantite'] * $line['prix_unitaire'];
            }
        }

        // Check if we're being called from dashboard
        if (isset($_GET['controller']) && $_GET['controller'] === 'dashboard') {
            return; // Let dashboard handle the display
        }

        // Standalone orders page - include full template
        include __DIR__ . '/../view/templates/admin_header.php';
        include __DIR__ . '/../view/orders/list.php';
    }

    public function view(): void {
        // Allow both staff and clients to view orders
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'client') {
            // Client viewing their own order
            $this->clientView();
            return;
        }
        
        AuthController::requireStaff();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "Commande invalide";
            header('Location: index.php?controller=commandes');
            exit;
        }

        $order = $this->commandeModel->getById($id);
        if (!$order) {
            $_SESSION['error'] = "Commande introuvable";
            header('Location: index.php?controller=commandes');
            exit;
        }

        $lines = $this->ligneModel->getByCommandeId($id);

        include __DIR__ . '/../view/templates/admin_header.php';
        include __DIR__ . '/../view/orders/view.php';
    }

    public function updateStatus(): void {
        AuthController::requireStaff();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=commandes');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $statut = trim($_POST['statut'] ?? '');

        // Validate input
        if ($id <= 0) {
            $_SESSION['error'] = "ID de commande invalide";
            header('Location: index.php?controller=commandes');
            exit;
        }

        $allowedStatuses = ['en_attente', 'en_cours', 'terminée'];
        if (!in_array($statut, $allowedStatuses)) {
            $_SESSION['error'] = "Statut invalide";
            header('Location: index.php?controller=commandes');
            exit;
        }

        // Check if order exists
        $order = $this->commandeModel->getById($id);
        if (!$order) {
            $_SESSION['error'] = "Commande introuvable";
            header('Location: index.php?controller=commandes');
            exit;
        }

        // Update status
        if ($this->commandeModel->updateStatus($id, $statut)) {
            $_SESSION['success'] = "Statut mis à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du statut";
        }

        // FIXED: Redirect back to dashboard if coming from dashboard
        if (isset($_POST['from_dashboard']) || isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'dashboard') !== false) {
            header('Location: index.php?controller=dashboard&section=commandes');
        } else {
            header('Location: index.php?controller=commandes');
        }
        exit;
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

    // Client order history
    public function historique(): void {
        AuthController::requireClient();
        
        $clientId = $_SESSION['user']['id'];
        
        try {
            // Get orders for current client
            $orders = $this->commandeModel->getByClientId($clientId);
            
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
}
?>