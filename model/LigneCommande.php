<?php
// model/LigneCommande.php
require_once __DIR__ . '/Database.php';

class LigneCommande {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Get all lines for a specific order
    public function getByCommandeId(int $commandeId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT lc.*, p.nom as nom_produit
                FROM lignes_commande lc
                LEFT JOIN produits p ON lc.id_produit = p.id
                WHERE lc.id_commande = ?
                ORDER BY lc.id
            ");
            $stmt->execute([$commandeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in LigneCommande::getByCommandeId: " . $e->getMessage());
            return [];
        }
    }

    // Create a new order line
    public function create(int $idCommande, int $idProduit, int $quantite, float $prixUnitaire): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO lignes_commande (id_commande, id_produit, quantite, prix_unitaire) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$idCommande, $idProduit, $quantite, $prixUnitaire]);
        } catch (Exception $e) {
            error_log("Error in LigneCommande::create: " . $e->getMessage());
            throw new Exception("Erreur lors de la création de la ligne de commande");
        }
    }

    // Update a line
    public function update(int $id, array $data): bool {
        try {
            $fields = [];
            $values = [];
            
            if (isset($data['quantite'])) {
                $fields[] = "quantite = ?";
                $values[] = (int)$data['quantite'];
            }
            
            if (isset($data['prix_unitaire'])) {
                $fields[] = "prix_unitaire = ?";
                $values[] = (float)$data['prix_unitaire'];
            }

            if (empty($fields)) {
                return false;
            }

            $values[] = $id;
            $sql = "UPDATE lignes_commande SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("Error in LigneCommande::update: " . $e->getMessage());
            return false;
        }
    }

    // Delete a line
    public function delete(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM lignes_commande WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error in LigneCommande::delete: " . $e->getMessage());
            return false;
        }
    }

    // Delete all lines for a specific order
    public function deleteByCommandeId(int $commandeId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM lignes_commande WHERE id_commande = ?");
            return $stmt->execute([$commandeId]);
        } catch (Exception $e) {
            error_log("Error in LigneCommande::deleteByCommandeId: " . $e->getMessage());
            return false;
        }
    }

    // Get total for an order
    public function getTotalByCommandeId(int $commandeId): float {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(quantite * prix_unitaire) as total 
                FROM lignes_commande 
                WHERE id_commande = ?
            ");
            $stmt->execute([$commandeId]);
            $result = $stmt->fetchColumn();
            return $result ? (float)$result : 0.0;
        } catch (Exception $e) {
            error_log("Error in LigneCommande::getTotalByCommandeId: " . $e->getMessage());
            return 0.0;
        }
    }
}
?>