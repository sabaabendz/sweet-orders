<?php
// view/dashboard_with_content.php - Dashboard template for order view
require_once __DIR__ . '/../controller/AuthController.php';
AuthController::requireStaff();

$user = $_SESSION['user'] ?? null;
$userName = $user ? $user['prenom'] . ' ' . $user['nom'] : 'Utilisateur';
$userRole = $user['role'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CakeShop - Dashboard</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
    <style>
        .order-details-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-section h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .info-section p {
            margin: 5px 0;
        }

        .order-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .order-actions label {
            font-weight: bold;
        }

        .order-actions select, .order-actions button {
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-left: 10px;
        }

        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 1em;
            font-weight: bold;
            display: inline-block;
        }
        .status-en_attente { background: #fff3cd; color: #856404; }
        .status-en_cours { background: #d1ecf1; color: #0c5460; }
        .status-terminee { background: #d4edda; color: #155724; }
        .status-supprimee { background: #f8d7da; color: #721c24; }

        .btn-danger {
            background-color: #dc3545 !important;
            color: white !important;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #c82333 !important;
        }

        .btn-success {
            background-color: #28a745 !important;
            color: white !important;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-success:hover {
            background-color: #218838 !important;
        }

        .preparator-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
    </style>
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
            <li>
                <a href="index.php?controller=dashboard&section=produits">
                   🧁 Produits
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="index.php?controller=dashboard&section=commandes" style="background: #f8a5c2;">
                   📦 <?= $userRole === 'preparateur' ? 'Mes commandes' : 'Commandes' ?>
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

        <!-- ORDER DETAILS CONTENT -->
        <div class="table-actions">
            <h2>📦 Détails de la commande #<?= $order['id'] ?></h2>
            <a href="index.php?controller=dashboard&section=commandes" class="btn">← Retour aux commandes</a>
        </div>

        <div class="order-details-container">
            <div class="order-info-grid">
                <div class="info-section">
                    <h4>Informations générales</h4>
                    <p><strong>Client:</strong> <?= htmlspecialchars($order['nom_client'] ?? 'Client ID: ' . $order['id_client']) ?></p>
                    <p><strong>Date:</strong> <?= date('d/m/Y à H:i', strtotime($order['date_commande'])) ?></p>
                </div>
                <div class="info-section">
                    <h4>Statut</h4>
                    <span class="status-badge status-<?= str_replace(['é', 'è'], ['e', 'e'], $order['statut']) ?>">
                        <?php 
                        switch($order['statut']) {
                            case 'en_attente': echo 'En attente'; break;
                            case 'en_cours': echo 'En cours'; break;
                            case 'terminee': echo 'Terminée'; break;
                            case 'supprimee': echo 'Annulée'; break;
                            default: echo ucfirst($order['statut']); break;
                        }
                        ?>
                    </span>
                </div>
            </div>

            <h4>Articles à préparer</h4>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        if (!empty($lines)): 
                            foreach ($lines as $line):
                                $lineTotal = $line['quantite'] * $line['prix_unitaire'];
                                $total += $lineTotal;
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($line['nom_produit'] ?? 'Produit inconnu') ?></strong>
                                    <?php if ($userRole === 'preparateur'): ?>
                                        <br><small style="color: #666;">À préparer: <?= $line['quantite'] ?> unité(s)</small>
                                    <?php endif; ?>
                                </td>
                                <td><span style="font-size: 1.2em; font-weight: bold; color: #007bff;"><?= $line['quantite'] ?></span></td>
                                <td><?= number_format($line['prix_unitaire'], 2, ',', ' ') ?> €</td>
                                <td><?= number_format($lineTotal, 2, ',', ' ') ?> €</td>
                            </tr>
                        <?php 
                            endforeach;
                        else: ?>
                            <tr><td colspan="4" style="text-align:center;">Aucun article dans cette commande</td></tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($lines)): ?>
                            <tr class="total-row">
                                <td colspan="3" style="text-align:right;"><strong>Total de la commande:</strong></td>
                                <td><strong><?= number_format($total, 2, ',', ' ') ?> €</strong></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ACTION BUTTONS BASED ON ROLE -->
            <div class="order-actions">
                <?php if ($userRole === 'preparateur'): ?>
                    <!-- PREPARATEUR ACTIONS -->
                    <?php if ($order['statut'] === 'en_attente'): ?>
                        <div class="preparator-actions">
                            <span>Actions:</span>
                            <form action="index.php?controller=commandes&action=updateStatus" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                <button type="submit" name="statut" value="en_cours" 
                                        onclick="return confirm('Démarrer la préparation de cette commande ?')"
                                        class="btn-success">
                                    ▶️ Démarrer la préparation
                                </button>
                            </form>
                        </div>
                    <?php elseif ($order['statut'] === 'en_cours'): ?>
                        <div class="preparator-actions">
                            <span>Actions:</span>
                            <form action="index.php?controller=commandes&action=updateStatus" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                <button type="submit" name="statut" value="terminee" 
                                        onclick="return confirm('Marquer cette commande comme terminée ?')"
                                        class="btn-success">
                                    ✅ Terminer la commande
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <span style="color: #28a745; font-weight: bold;">✅ Commande terminée</span>
                    <?php endif; ?>

                <?php elseif ($userRole === 'admin'): ?>
                    <!-- ADMIN ACTIONS -->
                    <?php if ($order['statut'] !== 'supprimee'): ?>
                        <form action="index.php?controller=commandes&action=updateStatus" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="from_dashboard" value="1">
                            <label>Changer le statut:</label>
                            <select name="statut" onchange="if(confirm('Confirmer le changement de statut ?')) this.form.submit();">
                                <option value="">-- Choisir --</option>
                                <option value="en_attente" <?= $order['statut'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="en_cours" <?= $order['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="terminee" <?= $order['statut'] === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                            </select>
                        </form>

                        <a href="index.php?controller=commandes&action=cancel&id=<?= $order['id'] ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ?')">
                           ❌ Annuler la commande
                        </a>
                    <?php else: ?>
                        <span style="color: #dc3545; font-weight: bold;">❌ Commande annulée</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>