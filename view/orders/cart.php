<?php
// view/orders/cart.php - FIXED VERSION
require_once __DIR__ . '/../../model/Product.php';

$isLoggedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
$cart = $_SESSION['cart'] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Panier - CakeShop</title>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
    <style>
        .cart-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cart-table th, .cart-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .cart-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .cart-table tr:last-child td {
            border-bottom: none;
        }
        .checkout-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background-color: #1a73e8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .checkout-btn:hover {
            background-color: #155ab6;
        }
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #1a73e8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #155ab6;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo-container">
            <img src="../../public/assets/images/sweetorderlogo.png" alt="CakeShop Logo" class="logo">
            <h1>CakeShop</h1>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="../../index.php">Accueil</a></li>
                <li><a href="../../index.php?controller=client&action=catalogue">Catalogue</a></li>
                <li><a href="../../index.php?controller=client&action=cart">Panier</a></li>
                <li><a href="../../index.php?controller=commandes&action=historique">Mes Commandes</a></li>
                <li><a href="../../index.php?controller=auth&action=logout">Déconnexion (<?= htmlspecialchars($user['prenom']) ?>)</a></li>
            </ul>
        </nav>
    </header>

    <div class="cart-container">
        <h1>🛒 Votre Panier</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($cart)): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix Unitaire</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0; 
                    foreach ($cart as $productId => $qty): 
                        $product = (new Product())->getById($productId);
                        if ($product):
                            $lineTotal = $product['prix'] * $qty;
                            $total += $lineTotal;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($product['nom']) ?></td>
                            <td><?= $qty ?></td>
                            <td><?= number_format($product['prix'], 2, ',', ' ') ?> €</td>
                            <td><?= number_format($lineTotal, 2, ',', ' ') ?> €</td>
                        </tr>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                    <tr>
                        <td colspan="3"><strong>Total</strong></td>
                        <td><strong><?= number_format($total, 2, ',', ' ') ?> €</strong></td>
                    </tr>
                </tbody>
            </table>

            <a href="../../index.php?controller=client&action=checkout" class="checkout-btn">✅ Passer la commande</a>
            <a href="../../index.php?controller=client&action=catalogue" class="btn" style="background: #6c757d;">← Continuer mes achats</a>
        <?php else: ?>
            <div class="empty-cart">
                <p>Votre panier est vide.</p>
                <a href="../../index.php?controller=client&action=catalogue" class="btn">Découvrir nos produits</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 CakeShop - Pâtisserie artisanale</p>
    </footer>
</body>
</html>