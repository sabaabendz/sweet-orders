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
    .navigation a {
        margin: 0 10px;
        color: #f78fb3;
        text-decoration: none;
        font-weight: bold;
    }
    .navigation a:hover {
        text-decoration: underline;
    }
    .order-details {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .order-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
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
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .items-table th, .items-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    .items-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .total-row {
        font-weight: bold;
        background-color: #f8f9fa;
    }
</style>

<div class="container">
    <div class="navigation">
        <a href="index.php?controller=commandes&action=historique">← Retour à mes commandes</a>
    </div>

    <div class="order-details">
        <h1>Détails de votre commande</h1>
        
        <div class="order-info">
            <div>
                <h3>Informations générales</h3>
                <p><strong>Date:</strong> <?= date('d/m/Y à H:i', strtotime($order['date_commande'])) ?></p>
            </div>
            <div>
                <h3>Statut</h3>
                <span class="status-badge status-<?= str_replace(['é', 'è'], ['e', 'e'], $order['statut']) ?>">
                    <?= ucfirst($order['statut']) ?>
                </span>
            </div>
        </div>

        <h3>Articles commandés</h3>
        <table class="items-table">
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
                        <td><?= htmlspecialchars($line['nom_produit'] ?? 'Produit inconnu') ?></td>
                        <td><?= $line['quantite'] ?></td>
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
</div>
