<?php
// view/catalogue.php - FIXED VERSION
$isLoggedIn = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Catalogue - CakeShop</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
    <style>
        .catalogue-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-notice {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #ffeeba;
        }
        
        .auth-notice a {
            color: #1a73e8;
            text-decoration: none;
            font-weight: bold;
        }
        
        .products-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        
        .product-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            width: 250px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .product-card h3 {
            margin: 10px 0;
        }
        
        .product-card p {
            font-size: 0.9rem;
            color: #555;
            min-height: 40px;
        }
        
        .product-card .price {
            font-weight: bold;
            margin: 10px 0;
            color: #1a73e8;
        }
        
        .product-card .stock {
            margin-bottom: 10px;
            color: #333;
        }
        
        .product-card .btn {
            background-color: #1a73e8;
            color: #fff;
            text-decoration: none;
            padding: 10px;
            border: none;
            border-radius: 5px;
            transition: 0.2s;
            cursor: pointer;
        }
        
        .product-card .btn:hover {
            background-color: #155ab6;
        }
        
        .product-card .btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .login-prompt {
            background-color: #e9ecef;
            color: #495057;
            padding: 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .navigation {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .navigation a {
            margin: 0 10px;
            color: #1a73e8;
            text-decoration: none;
            font-weight: bold;
        }
        
        .success-message, .error-message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo-container">
            <img src="public/assets/images/sweetorderlogo.png" alt="CakeShop Logo" class="logo">
            <h1>CakeShop</h1>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="index.php?controller=client&action=catalogue">Catalogue</a></li>
                
                <?php if ($isLoggedIn): ?>
                    <li><a href="index.php?controller=client&action=cart">🛒 Panier</a></li>
                    <li><a href="index.php?controller=commandes&action=historique">Mes commandes</a></li>
                    
                    <?php if ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'preparateur'): ?>
                        <li><a href="index.php?controller=dashboard">Dashboard</a></li>
                    <?php endif; ?>
                    
                    <li><a href="index.php?controller=auth&action=logout">Déconnexion (<?= htmlspecialchars($_SESSION['user']['prenom']) ?>)</a></li>
                <?php else: ?>
                    <li><a href="index.php?controller=auth&action=login">Connexion</a></li>
                    <li><a href="index.php?controller=auth&action=register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="catalogue-container">
        <h1>📦 Catalogue des Produits</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!$isLoggedIn): ?>
            <div class="auth-notice">
                <strong>ℹ️ Vous devez vous connecter pour commander des produits.</strong>
                <br>
                <a href="index.php?controller=auth&action=login">Se connecter</a> ou 
                <a href="index.php?controller=auth&action=register">Créer un compte</a>
            </div>
        <?php endif; ?>

        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $prod): ?>
                    <div class="product-card">
                        <h3><?= htmlspecialchars($prod['nom']) ?></h3>
                        <p><?= htmlspecialchars(substr($prod['description'], 0, 60)) ?><?= strlen($prod['description']) > 60 ? '...' : '' ?></p>
                        <div class="price"><?= number_format($prod['prix'], 2, ',', ' ') ?> €</div>
                        <div class="stock">Stock: <?= $prod['stock'] ?></div>
                        <div>Catégorie: <?= ucfirst($prod['categorie']) ?></div>
                        
                        <?php if ($isLoggedIn): ?>
                            <form action="index.php?controller=client&action=addToCart" method="POST">
                                <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?= $prod['stock'] ?>" style="width:60px; margin:5px auto;">
                                <button type="submit" class="btn">➕ Ajouter au panier</button>
                            </form>
                        <?php else: ?>
                            <div class="login-prompt">
                                <small>Connectez-vous pour commander</small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; width:100%;">Aucun produit disponible pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 CakeShop - Pâtisserie artisanale</p>
    </footer>
</body>
</html>