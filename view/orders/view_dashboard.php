<!-- view/orders/view_dashboard.php - Order details view for dashboard -->
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
                <?= ucfirst($order['statut']) ?>
            </span>
        </div>
    </div>

    <h4>Articles commandés</h4>
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

    <div class="order-actions">
        <form action="index.php?controller=commandes&action=updateStatus" method="POST" style="display:inline;">
            <input type="hidden" name="id" value="<?= $order['id'] ?>">
            <input type="hidden" name="from_dashboard" value="1">
            <label>Changer le statut:</label>
            <select name="statut" onchange="if(confirm('Confirmer le changement de statut ?')) this.form.submit();">
                <option value="en_attente" <?= $order['statut'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                <option value="en_cours" <?= $order['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                <option value="terminee" <?= $order['statut'] === 'terminee' ? 'selected' : '' ?>>Terminée</option>
            </select>
        </form>

        <a href="index.php?controller=commandes&action=delete&id=<?= $order['id'] ?>" 
           class="btn btn-danger"
           onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ?')"
           style="background: #dc3545; color: white; margin-left: 20px;">
           Annuler la commande
        </a>
    </div>
</div>

<style>
.order-details-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

.order-actions select {
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
</style>