<?php
// view/dashboard.php
require_once __DIR__ . '/../controller/AuthController.php';
AuthController::requireAdmin();

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
    <div style="color: white;">
        Connecté en tant que: <strong><?= htmlspecialchars($userName) ?> (<?= htmlspecialchars($user['role']) ?>)</strong>
    </div>
</header>

<div class="dashboard-container">
    <aside class="sidebar">
        <ul>
            <li><a href="index.php?controller=users&action=index">👤 Utilisateurs</a></li>
            <li><a href="index.php?controller=produits&action=index">🍩 Produits</a></li>
            <li><a href="index.php?controller=commandes&action=index">📦 Commandes</a></li>
            <li><a href="index.php?controller=auth&action=logout">🚪 Déconnexion</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <h3>Bienvenue <?= htmlspecialchars($user['prenom']) ?> 👋</h3>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: #00b894; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div style="background: #d63031; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <h4>Statistiques</h4>
        <div class="stats-container" style="display: flex; gap: 20px; margin-top: 20px;">
            <div class="stat-box" style="background: #f0f0f0; padding: 20px; border-radius: 8px;">
                <h5>Commandes en attente</h5>
                <p style="font-size: 24px; color: #d63031;"><?= $stats['commandes_en_attente'] ?? 0 ?></p>
            </div>
            <div class="stat-box" style="background: #f0f0f0; padding: 20px; border-radius: 8px;">
                <h5>Produits en stock</h5>
                <p style="font-size: 24px; color: #00b894;"><?= $stats['produits_en_stock'] ?? 0 ?></p>
            </div>
            <div class="stat-box" style="background: #f0f0f0; padding: 20px; border-radius: 8px;">
                <h5>Clients inscrits</h5>
                <p style="font-size: 24px; color: #0984e3;"><?= $stats['clients_inscrits'] ?? 0 ?></p>
            </div>
        </div>
    </main>
</div>
</body>
</html>
