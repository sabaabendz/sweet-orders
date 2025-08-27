<?php
require_once __DIR__ . '/../templates/client_header.php';
require_once __DIR__ . '/../../model/Product.php';

$cart = $_SESSION['cart'] ?? [];
?>
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
            background-color: #f78fb3;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .checkout-btn:hover {
            background-color: #f8a5c2;
            transform: translateY(-2px);
        }
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #ffeaa7;
            color: #2d3436;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #f8a5c2;
            color: white;
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
    <section class="hero" style="padding: 40px 20px;">
        <h2>🛒 Votre Panier</h2>
        <p>Gérez vos articles et passez votre commande</p>
    </section>

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

            <a href="/Sweet/index.php?controller=client&action=checkout" class="checkout-btn">✅ Passer la commande</a>
            <a href="/Sweet/index.php?controller=client&action=catalogue" class="btn" style="background: #6c757d;">← Continuer mes achats</a>
        <?php else: ?>
            <div class="empty-cart">
                <p>Votre panier est vide.</p>
                <a href="/Sweet/index.php?controller=client&action=catalogue" class="btn">Découvrir nos produits</a>
            </div>
        <?php endif; ?>
    </div>