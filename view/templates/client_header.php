<?php
// view/templates/client_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
$userName = $user ? $user['prenom'] . ' ' . $user['nom'] : 'Utilisateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CakeShop - Catalogue</title>
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
                <li><a href="index.php?controller=client&action=catalogue">Catalogue</a></li>
                <?php if ($user): ?>
                    <li><a href="index.php?controller=client&action=cart">🛒 Panier</a></li>
                    <li><a href="index.php?controller=commandes&action=historique">📦 Mes Commandes</a></li>
                    <?php if ($user['role'] === 'admin' || $user['role'] === 'preparateur'): ?>
                        <li><a href="index.php?controller=dashboard">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="index.php?controller=auth&action=logout">Déconnexion (<?= htmlspecialchars($user['prenom']) ?>)</a></li>
                <?php else: ?>
                    <li><a href="index.php?controller=auth&action=login">Connexion</a></li>
                    <li><a href="index.php?controller=auth&action=register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container" style="padding: 20px;">
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
