<?php
// view/dashboard_with_content.php - Dashboard template for order view
require_once __DIR__ . '/../controller/AuthController.php';
AuthController::requireStaff();

$user = $_SESSION['user'] ?? null;
$userName = $user ? $user['prenom'] . ' ' . $user['nom'] : 'Utilisateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CakeShop - Dashboard</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body>

<header class="dashboard-header">
    <div class="logo-container">
        <img src="public/assets/images/sweetorderlogo.png" alt="CakeShop Logo" class="logo">
        <h2>Dashboard CakeShop</h2>
    </div>
    <div class="user-status">
        Connecté en tant que: <strong><?= htmlspecialchars($userName) ?> (<?= htmlspecialchars($user['role']) ?>)</strong>
    </div>
</header>

<div class="dashboard-container">
    <aside class="sidebar">
        <ul>
            <li>
                <a href="index.php?controller=dashboard&section=home">
                   🏠 Dashboard
                </a>
            </li>
            <?php if ($user['role'] === 'admin'): ?>
            <li>
                <a href="index.php?controller=dashboard&section=utilisateurs">
                   👤 Utilisateurs
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="index.php?controller=dashboard&section=produits">
                   🧁 Produits
                </a>
            </li>
            <li>
                <a href="index.php?controller=dashboard&section=commandes" style="background: #f8a5c2;">
                   📦 Commandes
                </a>
            </li>
            <li><a href="index.php?controller=auth&action=logout">🚪 Déconnexion</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
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

        <?= $orderViewContent ?? '' ?>
    </main>
</div>

</body>
</html>