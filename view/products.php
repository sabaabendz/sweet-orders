<?php
// view/products.php
require_once __DIR__ . '/../controller/AuthController.php';
AuthController::requireAdmin();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des produits - CakeShop</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body>
<header>
    <h2>Liste des produits</h2>
    <a href="index.php?controller=produits&action=form" style="margin-bottom: 15px; display: inline-block;">➕ Ajouter un produit</a>
</header>

<?php if (isset($_SESSION['success'])): ?>
    <div style="background: #00b894; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
        <?= $_SESSION['success'] ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<table border="1" cellpadding="10" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Description</th>
            <th>Prix</th>
            <th>Stock</th>
            <th>Catégorie</th>
            <th>Date d'ajout</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['nom']) ?></td>
                    <td><?= htmlspecialchars($p['description']) ?></td>
                    <td><?= number_format($p['prix'], 2, ',', ' ') ?> €</td>
                    <td><?= $p['stock'] ?></td>
                    <td><?= ucfirst($p['categorie']) ?></td>
                    <td><?= $p['date_ajout'] ?></td>
                    <td>
                        <a href="index.php?controller=produits&action=form&id=<?= $p['id'] ?>">✏️ Modifier</a>
                        <a href="index.php?controller=produits&action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer ce produit ?')">🗑️ Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">Aucun produit trouvé.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
