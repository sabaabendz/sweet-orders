<?php
// model/Commande.php - FIXED VERSION
require_once __DIR__ . '/Database.php';

class Commande {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Get all orders with client names - FIXED to exclude deleted orders for staff
    public function getAll(string $search = '', string $status = ''): array {
        try {
            $sql = "
                SELECT c.*, CONCAT(u.prenom, ' ', u.nom) AS nom_client
                FROM commandes c
                LEFT JOIN utilisateurs u ON c.id_client = u.id
                WHERE c.statut != 'supprimee'
            ";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if (!empty($status)) {
                $sql .= " AND c.statut = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY c.date_commande DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in Commande::getAll: " . $e->getMessage());
            return [];
        }
    }

    // Get order by ID with client info
    public function getById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, CONCAT(u.prenom, ' ', u.nom) AS nom_client
                FROM commandes c
                LEFT JOIN utilisateurs u ON c.id_client = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error in Commande::getById: " . $e->getMessage());
            return null;
        }
    }

    // Get orders by client ID - FIXED to show ALL orders including cancelled ones
    public function getByClientId(int $clientId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM commandes 
                WHERE id_client = ? 
                ORDER BY date_commande DESC
            ");
            $stmt->execute([$clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in Commande::getByClientId: " . $e->getMessage());
            return [];
        }
    }

    // Create new order
    public function create(int $idClient): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO commandes (id_client, date_commande, statut) 
                VALUES (?, NOW(), 'en_attente')
            ");
            $stmt->execute([$idClient]);
            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error in Commande::create: " . $e->getMessage());
            throw new Exception("Erreur lors de la création de la commande");
        }
    }

    // Update order status - FIXED to include new statuses
    public function updateStatus(int $id, string $statut): bool {
        try {
            $allowedStatuses = ['en_attente', 'en_cours', 'terminee', 'supprimee'];
            if (!in_array($statut, $allowedStatuses)) {
                throw new InvalidArgumentException("Statut invalide");
            }

            $stmt = $this->db->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
            return $stmt->execute([$statut, $id]);
        } catch (Exception $e) {
            error_log("Error in Commande::updateStatus: " . $e->getMessage());
            return false;
        }
    }

    // Delete order (and cascade to lines) - KEEP for hard delete if needed
    public function delete(int $id): bool {
        try {
            $this->db->beginTransaction();
            
            // First delete order lines
            $stmt = $this->db->prepare("DELETE FROM lignes_commande WHERE id_commande = ?");
            $stmt->execute([$id]);
            
            // Then delete the order
            $stmt = $this->db->prepare("DELETE FROM commandes WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in Commande::delete: " . $e->getMessage());
            return false;
        }
    }

    // Get orders by status - FIXED to exclude deleted for staff view
    public function getByStatus(string $status): array {
        try {
            $sql = "
                SELECT c.*, CONCAT(u.prenom, ' ', u.nom) AS nom_client
                FROM commandes c
                LEFT JOIN utilisateurs u ON c.id_client = u.id
                WHERE c.statut = ?
            ";
            
            // Don't show deleted orders in regular status filtering
            if ($status !== 'supprimee') {
                $sql .= " AND c.statut != 'supprimee'";
            }
            
            $sql .= " ORDER BY c.date_commande DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in Commande::getByStatus: " . $e->getMessage());
            return [];
        }
    }

    // Get order statistics - FIXED to handle new status
    public function getStats(): array {
        try {
            $stats = [];
            
            // Count by status (excluding deleted)
            $stmt = $this->db->query("SELECT statut, COUNT(*) as count FROM commandes WHERE statut != 'supprimee' GROUP BY statut");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats[$row['statut']] = (int)$row['count'];
            }
            
            // Total orders (excluding deleted)
            $stmt = $this->db->query("SELECT COUNT(*) FROM commandes WHERE statut != 'supprimee'");
            $stats['total'] = (int)$stmt->fetchColumn();
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error in Commande::getStats: " . $e->getMessage());
            return [];
        }
    }

    // NEW: Method to cancel order (soft delete)
    public function cancel(int $id): bool {
        return $this->updateStatus($id, 'supprimee');
    }

    // NEW: Method to restore cancelled order
    public function restore(int $id): bool {
        return $this->updateStatus($id, 'en_attente');
    }
}
?>