<?php
require_once __DIR__ . '/../templates/client_header.php';
?>
    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .navigation {
            text-align: center;
            margin-bottom: 20px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .orders-table th, .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        .orders-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .orders-table tr:hover {
            background-color: #f8f9fa;
        }
        .products-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .products-list li {
            margin-bottom: 4px;
        }
        .price-cell {
            text-align: right;
            font-weight: 600;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-en_attente { background: #fff3cd; color: #856404; }
        .status-en_cours { background: #d1ecf1; color: #0c5460; }
        .status-terminee { background: #d4edda; color: #155724; }
        .status-supprimee { background: #f8d7da; color: #721c24; }
        .empty-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #f78fb3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #f8a5c2;
        }
        .cancelled-order {
            opacity: 0.7;
            background-color: #fff5f5;
        }
    </style>
</head>
<body>
    <section class="hero" style="padding: 40px 20px;">
        <h2>📋 Historique des Commandes</h2>
        <p>Consultez toutes vos commandes</p>
    </section>

    <div class="container">

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

        <?php if (!empty($orders)): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Produits commandés</th>
                        <th style="text-align: right;">Total</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
                        $lines = $ligneModel->getByCommandeId($order['id']);
                        $total = 0;
                        $isCancelled = $order['statut'] === 'supprimee';
                    ?>
                        <tr <?= $isCancelled ? 'class="cancelled-order"' : '' ?>>
                            <td><?= date('d/m/Y H:i', strtotime($order['date_commande'])) ?></td>
                            <td>
                                <?php if (!empty($lines)): ?>
                                    <ul class="products-list">
                                    <?php foreach ($lines as $line):
                                        $total += $line['quantite'] * $line['prix_unitaire'];
                                        echo '<li>' . htmlspecialchars($line['nom_produit']) . ' <strong>×' . $line['quantite'] . '</strong></li>';
                                    endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <em>Aucun produit</em>
                                <?php endif; ?>
                            </td>
                            <td class="price-cell"><?= number_format($total, 2, ',', ' ') ?> €</td>
                            <td>
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
                            </td>
                            <td>
                                <?php if (!$isCancelled): ?>
                                    <a href="index.php?controller=commandes&action=clientView&id=<?= $order['id'] ?>" 
                                       class="btn">
                                       👁️ Voir détails
                                    </a>
                                <?php else: ?>
                                    <span style="color: #6c757d; font-size: 0.9em;">Commande annulée</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-orders">
                <p>Vous n'avez pas encore passé de commande.</p>
                <a href="index.php?controller=client&action=catalogue" class="btn">Découvrir nos produits</a>
            </div>
        <?php endif; ?>
    </div>