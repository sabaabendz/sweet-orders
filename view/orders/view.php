<!-- view/orders/view.php -->
<h2>📦 Détails de la commande #<?= $order['id'] ?></h2>

<p><strong>Client:</strong> <?= htmlspecialchars($order['nom_client'] ?? 'Inconnu') ?></p>
<p><strong>Date:</strong> <?= $order['date_commande'] ?></p>
<p><strong>Statut:</strong> <?= $order['statut'] ?></p>

<h3>Lignes de commande</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>Produit</th>
            <th>Quantité</th>
            <th>Prix Unitaire</th>
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
            <tr><td colspan="4" style="text-align:center;">Aucune ligne de commande</td></tr>
        <?php endif; ?>
        <tr>
            <td colspan="3" style="text-align:right;"><strong>Total:</strong></td>
            <td><strong><?= number_format($total, 2, ',', ' ') ?> €</strong></td>
        </tr>
    </tbody>
</table>

<a href="index.php?controller=orders">← Retour aux commandes</a>
