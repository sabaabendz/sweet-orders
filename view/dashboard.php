<?php
// Vérifier l'authentification
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
            
            <?php if (isset($_SESSION['login_success'])): ?>
                <div style="background: #00b894; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?= htmlspecialchars($_SESSION['login_success']) ?>
                </div>
                <?php unset($_SESSION['login_success']); ?>
            <?php endif; ?>

            <div class="cards-container">
                <div class="dash-card">
                    📦 <br> 
                    <?= isset($stats) ? $stats['commandes_en_attente'] : '0' ?> <br>
                    Commandes en attente
                </div>
                <div class="dash-card">
                    🍰 <br> 
                    <?= isset($stats) ? $stats['produits_en_stock'] : '0' ?> <br>
                    Produits en stock
                </div>
                <div class="dash-card">
                    👥 <br> 
                    <?= isset($stats) ? $stats['clients_inscrits'] : '0' ?> <br>
                    Clients inscrits
                </div>
            </div>
        </main>
    </div>
</body>
</html>