<?php
require_once __DIR__ . '/model/Database.php';

try {
    $db = Database::getConnection();
    
    echo "<h2>🔍 Vérification des tables de la base de données</h2>";
    
    // Liste toutes les tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<strong>Tables trouvées:</strong><br>";
    foreach ($tables as $table) {
        echo "📋 " . $table . "<br>";
    }
    
    // Test la table PRODUITS/PRODUCTS
    echo "<br><strong>Test de la table des produits:</strong><br>";
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM PRODUITS");
        $count = $stmt->fetchColumn();
        echo "✅ Table PRODUITS existe avec $count produits<br>";
    } catch (Exception $e) {
        echo "❌ Erreur table PRODUITS: " . $e->getMessage() . "<br>";
        
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM products");
            $count = $stmt->fetchColumn();
            echo "✅ Table products existe avec $count produits<br>";
        } catch (Exception $e2) {
            echo "❌ Erreur table products: " . $e2->getMessage() . "<br>";
        }
    }
    
    // Test la table UTILISATEURS
    echo "<br><strong>Test de la table des utilisateurs:</strong><br>";
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM UTILISATEURS");
        $count = $stmt->fetchColumn();
        echo "✅ Table UTILISATEURS existe avec $count utilisateurs<br>";
    } catch (Exception $e) {
        echo "❌ Erreur table UTILISATEURS: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><a href='index.php?controller=dashboard'>Retour au dashboard</a>";
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage();
}
