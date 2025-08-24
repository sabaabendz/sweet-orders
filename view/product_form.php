<?php
// view/product_form.php
require_once __DIR__ . '/../controller/AuthController.php';
AuthController::requireAdmin();

$isEdit = isset($product);
$formAction = $isEdit ? 'update' : 'store';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Modifier' : 'Ajouter' ?> un produit - CakeShop</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body>
<header>
    <h2><?= $isEdit ? 'Modifier' : 'Ajouter' ?> un produit</h2>
    <a href="index.php?controller=produits&action=index">⬅️ Retour à la liste</a>
</header>

<form action="index.php?controller=produits&action=<?= $formAction ?>" method="post" style="margin-top: 20px;">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $product['id'] ?>">
    <?php endif; ?>

    <div>
        <label>Nom :</label><br>
        <input type="text" name="nom" value="<?= htmlspecialchars($product['nom'] ?? '') ?>" required>
    </div>

    <div>
        <label>Description :</label><br>
        <textarea name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
    </div>

    <div>
        <label>Prix (€) :</label><br>
        <input type="number" step="0.01" name="prix" value="<?= $product['prix'] ?? 0 ?>" required>
    </div>

    <div>
        <label>Stock :</label><br>
        <input type="number" name="stock" value="<?= $product['stock'] ?? 0 ?>" required>
    </div>

    <div>
        <label>Catégorie :</label><br>
        <select name="categorie" required>
            <?php 
            $categories = ['gateau', 'viennoiserie', 'autre'];
            foreach ($categories as $cat): ?>
                <option value="<?= $cat ?>" <?= isset($product['categorie']) && $product['categorie'] === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="margin-top: 15px;">
        <button type="submit"><?= $isEdit ? 'Mettre à jour' : 'Ajouter' ?></button>
    </div>
</form>
</body>
</html>
