<?php
// create_admin.php - Script pour créer un compte admin
require_once __DIR__ . '/model/Database.php';

try {
    $db = Database::getConnection();
    
    // Vérifier si l'admin existe déjà
    $stmt = $db->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE email = ?");
    $stmt->execute(['admin@cakeshop.com']);
    
    if ($stmt->fetchColumn() > 0) {
        echo "✅ Le compte admin existe déjà!<br>";
    } else {
        // Créer le compte admin
        $hashedPassword = password_hash('123456', PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("
            INSERT INTO UTILISATEURS (nom, prenom, email, mot_de_passe, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'Admin',
            'Super',
            'admin@cakeshop.com',
            $hashedPassword,
            'admin'
        ]);
        
        if ($result) {
            echo "✅ Compte admin créé avec succès!<br>";
        } else {
            echo "❌ Erreur lors de la création du compte admin<br>";
        }
    }
    
    // Créer aussi un client de test
    $stmt = $db->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE email = ?");
    $stmt->execute(['client@cakeshop.com']);
    
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('123456', PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("
            INSERT INTO UTILISATEURS (nom, prenom, email, mot_de_passe, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'Dupont',
            'Marie',
            'client@cakeshop.com',
            $hashedPassword,
            'client'
        ]);
        
        if ($result) {
            echo "✅ Compte client créé avec succès!<br>";
        }
    }
    
    // Afficher tous les utilisateurs pour vérifier
    echo "<br><strong>📋 Utilisateurs dans la base:</strong><br>";
    $stmt = $db->query("SELECT id, nom, prenom, email, role FROM UTILISATEURS");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['nom'] . "</td>";
        echo "<td>" . $user['prenom'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td><strong>" . $user['role'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><br>";
    echo "🔐 <strong>Comptes de test créés:</strong><br>";
    echo "👤 <strong>Admin:</strong> admin@cakeshop.com / 123456<br>";
    echo "👥 <strong>Preparateur:</strong> preparateur@cakeshop.com / 123456<br>";
    echo "<br>";
    echo "➡️ <a href='index.php?controller=auth&action=login'>Aller à la page de connexion</a><br>";
    echo "➡️ <a href='index.php'>Aller à l'accueil</a><br>";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>