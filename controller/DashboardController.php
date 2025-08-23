<?php
// controller/DashboardController.php
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../model/User.php';

final class DashboardController {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index(): void {
        // Vérifier que l'utilisateur est admin ou préparateur
        AuthController::requireAdmin();
        
        // Récupérer les statistiques pour le dashboard
        $stats = $this->getStats();
        
        include __DIR__ . '/../view/dashboard.php';
    }

    private function getStats(): array {
        $db = Database::getConnection();
        
        // Nombre de commandes en attente
        $stmt = $db->query("SELECT COUNT(*) FROM COMMANDES WHERE statut = 'en_attente'");
        $commandesEnAttente = (int)$stmt->fetchColumn();
        
        // Nombre de produits en stock
        $stmt = $db->query("SELECT COUNT(*) FROM PRODUITS WHERE stock > 0");
        $produitsEnStock = (int)$stmt->fetchColumn();
        
        // Nombre de clients inscrits
        $stmt = $db->query("SELECT COUNT(*) FROM UTILISATEURS WHERE role = 'client'");
        $clientsInscrits = (int)$stmt->fetchColumn();
        
        return [
            'commandes_en_attente' => $commandesEnAttente,
            'produits_en_stock' => $produitsEnStock,
            'clients_inscrits' => $clientsInscrits
        ];
    }
}
?>