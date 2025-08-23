<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CakeShop - Accueil</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
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
                <li><a href="index.php?controller=produits&action=catalogue">Catalogue</a></li>
                
                <?php if ($isLoggedIn): ?>
                    <?php if ($user['role'] === 'admin' || $user['role'] === 'preparateur'): ?>
                        <li><a href="index.php?controller=dashboard">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="index.php?controller=commandes&action=historique">Mes commandes</a></li>
                    <li><a href="index.php?controller=auth&action=logout">Déconnexion (<?= htmlspecialchars($user['prenom']) ?>)</a></li>
                <?php else: ?>
                    <li><a href="index.php?controller=auth&action=login">Connexion</a></li>
                    <li><a href="index.php?controller=auth&action=register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <h2>Bienvenue chez <span>CakeShop</span> 🍰</h2>
        <p>Dégustez des créations sucrées artisanales, faites avec amour et passion.</p>
        
        <?php if ($isLoggedIn): ?>
            <p style="margin-top: 20px; font-size: 18px;">
                Bonjour <strong><?= htmlspecialchars($user['prenom']) ?></strong> ! 
                Découvrez nos dernières créations.
            </p>
        <?php endif; ?>
        
        <a href="index.php?controller=produits&action=catalogue" class="btn">Voir le catalogue</a>
        
        <?php if (!$isLoggedIn): ?>
            <br><br>
            <a href="index.php?controller=auth&action=login" class="btn" style="background: #786fa6; margin-right: 10px;">Se connecter</a>
            <a href="index.php?controller=auth&action=register" class="btn" style="background: #00b894;">Créer un compte</a>
        <?php endif; ?>
    </section>

    <section class="features">
        <div class="card">
            <h3>🎂 Gâteaux</h3>
            <p>Des gâteaux délicieux pour toutes vos occasions.</p>
        </div>
        <div class="card">
            <h3>🥐 Viennoiseries</h3>
            <p>Des croissants et brioches tout juste sortis du four.</p>
        </div>
        <div class="card">
            <h3>☕ Pause Douceur</h3>
            <p>Un espace convivial pour savourer vos douceurs préférées.</p>
        </div>
    </section>

    <footer>
        <p>&copy; 2025 CakeShop - Pâtisserie artisanale</p>
    </footer>
</body>
</html>