<!-- view/orders/list.php - FIXED VERSION -->
<div class="table-actions">
    <h2>📦 Liste des commandes</h2>
    <div class="action-buttons">
        <form action="index.php" method="GET" class="search-form" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="controller" value="dashboard">
            <input type="hidden" name="section" value="commandes">
            
            <div class="search-container">
                <input type="text" name="search" placeholder="🔍 Rechercher par client..." 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
            </div>
            
            <select name="status_filter" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                <option value="">Tous les statuts</option>
                <option value="en_attente" <?= isset($_GET['status_filter']) && $_GET['status_filter'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                <option value="en_cours" <?= isset($_GET['status_filter']) && $_GET['status_filter'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                <option value="terminee" <?= isset($_GET['status_filter']) && $_GET['status_filter'] === 'terminee' ? 'selected' : '' ?>>Terminée</option>
            </select>
            
            <button type="submit" class="btn" style="padding: 8px 16px;">Filtrer</button>
        </form>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<?php if (empty($orders)): ?>
    <div class="no-data">
        <p>Aucune commande trouvée.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['nom_client'] ?? 'Inconnu') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($order['date_commande'])) ?></td>
                    <td>
                        <span class="status-badge status-<?= $order['statut'] ?>">
                            <?php
                            switch($order['statut']) {
                                case 'en_attente': echo 'En attente'; break;
                                case 'en_cours': echo 'En cours'; break;
                                case 'terminee': echo 'Terminée'; break;
                                default: echo ucfirst($order['statut']);
                            }
                            ?>
                        </span>
                    </td>
                    <td><?= number_format($order['total'] ?? 0, 2) ?> €</td>
                    <td class="action-buttons">
                        <a href="index.php?controller=orders&action=view&id=<?= $order['id'] ?>&from=dashboard" 
                           class="btn btn-info" title="Voir détails">Voir</a>
                        
                        <form action="index.php?controller=orders&action=updateStatus" method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="from_dashboard" value="1">
                            <select name="statut" onchange="this.form.submit()" style="padding: 4px; font-size: 12px;">
                                <option value="en_attente" <?= $order['statut'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="en_cours" <?= $order['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="terminee" <?= $order['statut'] === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                            </select>
                        </form>
                        
                        <a href="index.php?controller=orders&action=delete&id=<?= $order['id'] ?>&from=dashboard" 
                           class="btn btn-danger" 
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')"
                           title="Supprimer">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<style>
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.status-en_attente { background-color: #fff3cd; color: #856404; }
.status-en_cours { background-color: #d1ecf1; color: #0c5460; }
.status-terminee { background-color: #d4edda; color: #155724; }

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}
.action-buttons .btn {
    padding: 4px 8px;
    font-size: 12px;
    text-decoration: none;
    border-radius: 3px;
    border: none;
    cursor: pointer;
}
.btn-info { background-color: #eb5fa9ff; color: white; }
.btn-danger { background-color: #eb5fa9ff; color: white; }
.btn-info:hover { background-color: #eb5fa9ff; }
.btn-danger:hover { background-color: #eb5fa9ff; }
</style>