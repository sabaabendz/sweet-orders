<!-- view/orders/list.php - FIXED VERSION -->
<h2>📦 Liste des commandes</h2>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Date</th>
                <th>Total</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['nom_client'] ?? 'Client ID: ' . $order['id_client']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($order['date_commande'])) ?></td>
                        <td><?= number_format($order['total'] ?? 0, 2, ',', ' ') ?> €</td>
                        <td>
                            <span class="status-badge status-<?= str_replace(['é', 'è'], ['e', 'e'], $order['statut']) ?>">
                                <?= ucfirst($order['statut']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="index.php?controller=commandes&action=view&id=<?= $order['id'] ?>" class="btn-small">👁️ Voir</a>
                            
                            <?php if ($_SESSION['user']['role'] !== 'client'): ?>
                                <!-- FIXED: Add hidden field to track source -->
                                <form action="index.php?controller=commandes&action=updateStatus" method="POST" style="display:inline;" onsubmit="return confirm('Confirmer le changement de statut ?');">
                                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                    <!-- FIXED: Add from_dashboard field if we're in dashboard -->
                                    <?php if (isset($_GET['controller']) && $_GET['controller'] === 'dashboard'): ?>
                                        <input type="hidden" name="from_dashboard" value="1">
                                    <?php endif; ?>
                                    <select name="statut" onchange="this.form.submit();" style="padding: 4px; margin: 0 5px;">
                                        <option value="en_attente" <?= $order['statut'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                        <option value="en_cours" <?= $order['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                        <option value="terminée" <?= $order['statut'] === 'terminée' ? 'selected' : '' ?>>Terminée</option>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">Aucune commande trouvée</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: bold;
}
.status-en_attente { background: #fff3cd; color: #856404; }
.status-en_cours { background: #d1ecf1; color: #0c5460; }
.status-terminee { background: #d4edda; color: #155724; }

.btn-small {
    padding: 4px 8px;
    font-size: 0.9em;
    text-decoration: none;
    border-radius: 3px;
    background: #007bff;
    color: white;
    margin: 0 2px;
}
.btn-small:hover {
    background: #0056b3;
    color: white;
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
</style>