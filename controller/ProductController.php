<?php
require_once __DIR__ . '/../model/Product.php';
require_once __DIR__ . '/AuthController.php';

final class ProductController {
    private Product $productModel;

    public function __construct() {
        $this->productModel = new Product();
        if (session_status() === PHP_SESSION_NONE) session_start();
        AuthController::requireAdmin(); // Admin only
    }

    // Lister les produits
    public function index(): void {
        $products = $this->productModel->getAll();
        include __DIR__ . '/../view/products.php';
    }

    // Formulaire create/edit
    public function form(?int $id = null): void {
        $product = $id ? $this->productModel->getById($id) : null;
        include __DIR__ . '/../view/product_form.php';
    }

    // Ajouter un produit
    public function store(): void {
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'prix' => (float)($_POST['prix'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0),
            'categorie' => $_POST['categorie'] ?? 'autre'
        ];
        $this->productModel->create($data);
        $_SESSION['success'] = 'Produit créé avec succès.';
        header('Location: index.php?controller=produits&action=index');
        exit;
    }

    // Mettre à jour un produit
    public function update(): void {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'prix' => (float)($_POST['prix'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0),
            'categorie' => $_POST['categorie'] ?? 'autre'
        ];
        $this->productModel->update($id, $data);
        $_SESSION['success'] = 'Produit mis à jour avec succès.';
        header('Location: index.php?controller=produits&action=index');
        exit;
    }

    // Supprimer un produit
    public function delete(int $id): void {
        $this->productModel->delete($id);
        $_SESSION['success'] = 'Produit supprimé avec succès.';
        header('Location: index.php?controller=produits&action=index');
        exit;
    }
}
