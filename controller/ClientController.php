<?php
require_once __DIR__ . '/../model/Product.php';
require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../model/Commande.php';
require_once __DIR__ . '/../model/LigneCommande.php';
require_once __DIR__ . '/../controller/AuthController.php';

class ClientController {
    private $productModel;

    public function __construct() {
        $this->productModel = new Product();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function catalogue() {
        // Get all products with stock > 0
        $products = $this->productModel->getAllInStock();
        
        // FIXED: Load the correct view path - no need for templates/client_header
        include __DIR__ . '/../view/catalogue.php';
    }

    public function cart(): void {
        // Require authentication for cart access
        AuthController::requireClient();
        
        $cart = $_SESSION['cart'] ?? [];
        include __DIR__ . '/../view/orders/cart.php';
    }

    public function addToCart(): void {
        // Require authentication to add to cart
        AuthController::requireClient();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $qty = (int)($_POST['quantity'] ?? 1);

            if ($id > 0 && $qty > 0) {
                // Check if product exists and has enough stock
                $product = $this->productModel->getById($id);
                if (!$product) {
                    $_SESSION['error'] = "Produit introuvable";
                    header("Location: index.php?controller=client&action=catalogue");
                    exit;
                }
                
                if ($product['stock'] < $qty) {
                    $_SESSION['error'] = "Stock insuffisant pour ce produit";
                    header("Location: index.php?controller=client&action=catalogue");
                    exit;
                }
                
                if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
                
                // Check total quantity in cart
                $currentQty = $_SESSION['cart'][$id] ?? 0;
                if ($currentQty + $qty > $product['stock']) {
                    $_SESSION['error'] = "Quantité totale dépasse le stock disponible";
                    header("Location: index.php?controller=client&action=catalogue");
                    exit;
                }
                
                if (isset($_SESSION['cart'][$id])) {
                    $_SESSION['cart'][$id] += $qty;
                } else {
                    $_SESSION['cart'][$id] = $qty;
                }
                $_SESSION['success'] = "Produit ajouté au panier !";
            } else {
                $_SESSION['error'] = "Quantité invalide";
            }
        }

        header("Location: index.php?controller=client&action=catalogue");
        exit;
    }

    public function checkout(): void {
        // Require authentication for checkout
        AuthController::requireClient();
        
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            $_SESSION['error'] = "Panier vide";
            header("Location: index.php?controller=client&action=catalogue");
            exit;
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // Create the order
            $stmt = $db->prepare("INSERT INTO commandes (id_client, date_commande, statut) VALUES (?, NOW(), 'en_attente')");
            $stmt->execute([$_SESSION['user']['id']]);
            $orderId = (int)$db->lastInsertId();

            // Add order lines
            $stmtLine = $db->prepare("INSERT INTO lignes_commande (id_commande, id_produit, quantite, prix_unitaire) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $stmtLine->execute([$orderId, $productId, $quantity, $product['prix']]);
                    
                    // Update product stock
                    $newStock = $product['stock'] - $quantity;
                    $stmtStock = $db->prepare("UPDATE produits SET stock = ? WHERE id = ?");
                    $stmtStock->execute([$newStock, $productId]);
                }
            }

            $db->commit();
            unset($_SESSION['cart']);
            $_SESSION['success'] = "Commande passée avec succès !";

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Erreur lors de la commande : " . $e->getMessage();
        }

        header("Location: index.php?controller=client&action=catalogue");
        exit;
    }
}
?>