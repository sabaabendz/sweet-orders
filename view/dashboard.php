<?php
// view/dashboard.php
require_once __DIR__ . '/../controller/AuthController.php';
AuthController::requireStaff();

$user = $_SESSION['user'] ?? null;
$userName = $user ? $user['prenom'] . ' ' . $user['nom'] : 'Utilisateur';
$section = $_GET['section'] ?? 'home';
$action = $_GET['action'] ?? 'index';
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
                <a href="index.php?controller=dashboard&section=home" 
                   <?= $section === 'home' ? 'style="background: #f8a5c2;"' : '' ?>>
                   📊 Dashboard
                </a>
            </li>
            <?php if ($user['role'] === 'admin'): ?>
            <li>
                <a href="index.php?controller=dashboard&section=utilisateurs" 
                   <?= $section === 'utilisateurs' ? 'style="background: #f8a5c2;"' : '' ?>>
                   👤 Utilisateurs
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="index.php?controller=dashboard&section=produits" 
                   <?= $section === 'produits' ? 'style="background: #f8a5c2;"' : '' ?>>
                   🧁 Produits
                </a>
            </li>
            <li>
                <a href="index.php?controller=dashboard&section=commandes" 
                   <?= $section === 'commandes' ? 'style="background: #f8a5c2;"' : '' ?>>
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

        <?php if ($section === 'home'): ?>
            <!-- HOME SECTION -->
            <h3>Bienvenue <?= htmlspecialchars($user['prenom']) ?> 👋</h3>
            <h4>Statistiques</h4>
            <div class="stats-container">
                <div class="stat-box stat-pending">
                    <h5>Commandes en attente</h5>
                    <p class="stat-value"><?= $stats['commandes_en_attente'] ?? 0 ?></p>
                </div>
                <div class="stat-box stat-stock">
                    <h5>Produits en stock</h5>
                    <p class="stat-value"><?= $stats['produits_en_stock'] ?? 0 ?></p>
                </div>
                <div class="stat-box stat-clients">
                    <h5>Clients inscrits</h5>
                    <p class="stat-value"><?= $stats['clients_inscrits'] ?? 0 ?></p>
                </div>
            </div>

        <?php elseif ($section === 'produits'): ?>
            <!-- PRODUCTS SECTION -->
            <?php if ($action === 'form'): ?>
                <!-- PRODUCT FORM -->
                <?php 
                $isEdit = isset($product) && $product !== null;
                $formAction = $isEdit ? 'update' : 'store';
                ?>
                <div class="table-actions">
                    <h3><?= $isEdit ? 'Modifier' : 'Ajouter' ?> un produit</h3>
                    <a href="index.php?controller=dashboard&section=produits" class="btn">← Retour</a>
                </div>
                
                <div class="table-container">
                    <form method="POST" action="index.php?controller=dashboard&section=produits&action=<?= $formAction ?>">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Nom :</label>
                            <input type="text" name="nom" value="<?= htmlspecialchars($product['nom'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Description :</label>
                            <textarea name="description" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>

                        <div style="display: flex; gap: 20px;">
                            <div class="form-group" style="flex: 1;">
                                <label>Prix (€) :</label>
                                <input type="number" step="0.01" name="prix" value="<?= $product['prix'] ?? 0 ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Stock :</label>
                                <input type="number" name="stock" value="<?= $product['stock'] ?? 0 ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Catégorie :</label>
                            <select name="categorie" required>
                                <option value="gateau" <?= isset($product['categorie']) && $product['categorie'] === 'gateau' ? 'selected' : '' ?>>Gâteau</option>
                                <option value="viennoiserie" <?= isset($product['categorie']) && $product['categorie'] === 'viennoiserie' ? 'selected' : '' ?>>Viennoiserie</option>
                                <option value="autre" <?= isset($product['categorie']) && $product['categorie'] === 'autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn"><?= $isEdit ? 'Mettre à jour' : 'Ajouter' ?></button>
                            <a href="index.php?controller=dashboard&section=produits" class="btn" style="background: #6c757d; color: white;">Annuler</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- PRODUCT LIST -->
                <div class="table-actions">
                    <h3>Liste des produits</h3>
                    <a href="index.php?controller=dashboard&section=produits&action=form" class="btn">➕ Ajouter</a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th><th>Nom</th><th>Description</th><th>Prix</th><th>Stock</th><th>Catégorie</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td><?= $prod['id'] ?></td>
                                    <td><?= htmlspecialchars($prod['nom']) ?></td>
                                    <td><?= htmlspecialchars(substr($prod['description'], 0, 30)) ?><?= strlen($prod['description']) > 30 ? '...' : '' ?></td>
                                    <td><?= number_format($prod['prix'], 2, ',', ' ') ?> €</td>
                                    <td><?= $prod['stock'] ?></td>
                                    <td><?= ucfirst($prod['categorie']) ?></td>
                                    <td>
                                        <a href="index.php?controller=dashboard&section=produits&action=form&id=<?= $prod['id'] ?>">✏️</a>
                                        <a href="index.php?controller=dashboard&section=produits&action=delete&id=<?= $prod['id'] ?>" 
                                           onclick="return confirm('Supprimer ce produit ?')">🗑️</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align: center; padding: 20px;">Aucun produit trouvé</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php elseif ($section === 'utilisateurs' && $user['role'] === 'admin'): ?>
            <!-- USERS SECTION -->
            <div class="table-actions">
                <h3>Liste des utilisateurs</h3>
                <a href="index.php?controller=users&action=form" class="btn">➕ Ajouter</a>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $usr): ?>
                            <tr>
                                <td><?= $usr['id'] ?></td>
                                <td><?= htmlspecialchars($usr['nom']) ?></td>
                                <td><?= htmlspecialchars($usr['prenom']) ?></td>
                                <td><?= htmlspecialchars($usr['email']) ?></td>
                                <td><?= ucfirst($usr['role']) ?></td>
                                <td>
                                    <a href="index.php?controller=users&action=form&id=<?= $usr['id'] ?>">✏️</a>
                                    <a href="index.php?controller=users&action=delete&id=<?= $usr['id'] ?>" 
                                       onclick="return confirm('Supprimer cet utilisateur ?')">🗑️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 20px;">Aucun utilisateur trouvé</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'commandes'): ?>
            <!-- ORDERS SECTION -->
            <div class="table-actions">
                <h3>Gestion des commandes</h3>
            </div>
            <div class="table-container">
                <p style="text-align: center; padding: 40px; color: #666;">📦 La gestion des commandes sera bientôt disponible.</p>
            </div>

        <?php endif; ?>
    </main>
</div>

</body>
</html>