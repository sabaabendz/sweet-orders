<?php
// model/Product.php - FIXED VERSION
require_once __DIR__ . '/Database.php';

class Product {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Get all products
    public function getAll(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM produits ORDER BY date_ajout DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in Product::getAll: " . $e->getMessage());
            return [];
        }
    }

    // Get products with stock > 0 (for client catalogue)
    public function getAllInStock(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM produits WHERE stock > 0 ORDER BY date_ajout DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in Product::getAllInStock: " . $e->getMessage());
            return [];
        }
    }

    // Get product by ID
    public function getById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM produits WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error in Product::getById: " . $e->getMessage());
            return null;
        }
    }

    // Create new product
    public function create(array $data): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO produits (nom, description, prix, stock, categorie, date_ajout) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $data['nom'],
                $data['description'] ?? '',
                $data['prix'],
                $data['stock'] ?? 0,
                $data['categorie'] ?? 'autre'
            ]);
            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error in Product::create: " . $e->getMessage());
            throw new Exception("Erreur lors de la création du produit");
        }
    }

    // Update product
    public function update(int $id, array $data): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE produits 
                SET nom = ?, description = ?, prix = ?, stock = ?, categorie = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $data['nom'],
                $data['description'] ?? '',
                $data['prix'],
                $data['stock'] ?? 0,
                $data['categorie'] ?? 'autre',
                $id
            ]);
        } catch (Exception $e) {
            error_log("Error in Product::update: " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour du produit");
        }
    }

    // Delete product
    public function delete(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM produits WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error in Product::delete: " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression du produit");
        }
    }

    // Update stock
    public function updateStock(int $id, int $newStock): bool {
        try {
            $stmt = $this->db->prepare("UPDATE produits SET stock = ? WHERE id = ?");
            return $stmt->execute([$newStock, $id]);
        } catch (Exception $e) {
            error_log("Error in Product::updateStock: " . $e->getMessage());
            return false;
        }
    }

    // Search products
    public function search(string $query): array {
        try {
            $searchTerm = "%$query%";
            $stmt = $this->db->prepare("
                SELECT * FROM produits 
                WHERE nom LIKE ? OR description LIKE ? OR categorie LIKE ?
                ORDER BY date_ajout DESC
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in Product::search: " . $e->getMessage());
            return [];
        }
    }

    // Get products by category
    public function getByCategory(string $category): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM produits WHERE categorie = ? ORDER BY date_ajout DESC");
            $stmt->execute([$category]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in Product::getByCategory: " . $e->getMessage());
            return [];
        }
    }
}
?>