<?php
// view/users.php
require_once __DIR__ . '/../controller/AuthController.php';
AuthController::requireAdmin();

$user = $_SESSION['user'] ?? null;
$userName = $user ? $user['prenom'] . ' ' . $user['nom'] : 'Utilisateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CakeShop - Gestion des Utilisateurs</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <div class="logo-container">
            <img src="public/assets/images/sweetorderlogo.png" alt="CakeShop Logo" class="logo">
            <h2>Gestion des Utilisateurs</h2>
        </div>
        <div class="user-status">
            Connecté en tant que: <strong><?= htmlspecialchars($userName) ?> (<?= htmlspecialchars($user['role'] ?? 'Inconnu') ?>)</strong>
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
            <h3>Bienvenue <?= htmlspecialchars($user['prenom'] ?? 'Utilisateur') ?> 👋</h3>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($content) && $content === 'list'): ?>
                <div class="table-container">
                    <div class="table-actions">
                        <a href="index.php?controller=users&action=form" class="btn">➕ Ajouter un utilisateur</a>
                    </div>
                    
                    <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): foreach ($users as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><?= htmlspecialchars($u['nom']) ?></td>
                                <td><?= htmlspecialchars($u['prenom']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['role']) ?></td>
                                <td>
                                    <a href="index.php?controller=users&action=show&id=<?= $u['id'] ?>">Voir</a> |
                                    <a href="index.php?controller=users&action=form&id=<?= $u['id'] ?>">Modifier</a> |
                                    <a href="index.php?controller=users&action=delete&id=<?= $u['id'] ?>" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                        Supprimer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="6">Aucun utilisateur trouvé</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif (isset($content) && $content === 'show' && isset($user_detail)): ?>
                <h3>Détails de l'utilisateur</h3>
                <div class="user-details">
                    <p><strong>ID:</strong> <?= $user_detail['id'] ?></p>
                    <p><strong>Nom:</strong> <?= htmlspecialchars($user_detail['nom']) ?></p>
                    <p><strong>Prénom:</strong> <?= htmlspecialchars($user_detail['prenom']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user_detail['email']) ?></p>
                    <p><strong>Rôle:</strong> <?= htmlspecialchars($user_detail['role']) ?></p>
                </div>
                <div class="actions">
                    <a href="index.php?controller=users&action=form&id=<?= $user_detail['id'] ?>" class="btn">Modifier</a>
                    <a href="index.php?controller=users&action=index" class="btn">Retour à la liste</a>
                </div>

            <?php elseif (isset($content) && $content === 'form'): ?>
                <h3><?= isset($user_detail) ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' ?></h3>
                <?php if (isset($_SESSION['form_errors'])): ?>
                    <div class="error-container">
                        <?php foreach ($_SESSION['form_errors'] as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php unset($_SESSION['form_errors']); ?>
                <?php endif; ?>
                <form action="index.php?controller=users&action=<?= isset($user_detail) ? 'update' : 'store' ?>" method="post">
                    <?php if (isset($user_detail)): ?>
                        <input type="hidden" name="id" value="<?= $user_detail['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Nom:</label>
                        <input type="text" name="nom" value="<?= $user_detail['nom'] ?? '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Prénom:</label>
                        <input type="text" name="prenom" value="<?= $user_detail['prenom'] ?? '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" value="<?= $user_detail['email'] ?? '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Mot de passe:<?= isset($user_detail) ? ' (laisser vide pour ne pas changer)' : '' ?></label>
                        <input type="password" name="mdp" <?= isset($user_detail) ? '' : 'required' ?>>
                    </div>
                    
                    <div class="form-group">
                        <label>Rôle:</label>
                        <select name="role">
                            <option value="client" <?= ($user_detail['role'] ?? '') === 'client' ? 'selected' : '' ?>>Client</option>
                            <option value="preparateur" <?= ($user_detail['role'] ?? '') === 'preparateur' ? 'selected' : '' ?>>Préparateur</option>
                            <option value="admin" <?= ($user_detail['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn"><?= isset($user_detail) ? 'Modifier' : 'Ajouter' ?></button>
                        <a href="index.php?controller=users&action=index" class="btn">Annuler</a>
                    </div>
                </form>
            <?php else: ?>
                <p>Aucun contenu sélectionné. Veuillez choisir une action.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>