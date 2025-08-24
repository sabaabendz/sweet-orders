<?php
require_once __DIR__ . '/Database.php';

class Product {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Lister tous les produits
    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM PRODUITS ORDER BY date_ajout DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un produit par ID
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM PRODUITS WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product ?: null;
    }

    // Créer un produit
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO PRODUITS (nom, description, prix, stock, categorie, date_ajout)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            trim($data['nom']),
            trim($data['description'] ?? ''),
            $data['prix'],
            $data['stock'],
            $data['categorie']
        ]);
        return (int)$this->db->lastInsertId();
    }

    // Mettre à jour un produit
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE PRODUITS
            SET nom = ?, description = ?, prix = ?, stock = ?, categorie = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            trim($data['nom']),
            trim($data['description'] ?? ''),
            $data['prix'],
            $data['stock'],
            $data['categorie'],
            $id
        ]);
    }

    // Supprimer un produit
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM PRODUITS WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
