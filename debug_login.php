<?php
// debug_login.php - Script pour déboguer les problèmes de connexion
require_once __DIR__ . '/model/Database.php';
require_once __DIR__ . '/model/User.php';

session_start();

echo "<h2>🔍 Debug Login System</h2>";

// Tester la connexion à la base
try {
    $db = Database::getConnection();
    echo "✅ Connexion à la base de données: OK<br><br>";
} catch (Exception $e) {
    echo "❌ Erreur de connexion à la base: " . $e->getMessage() . "<br>";
    exit;
}

// Vérifier les utilisateurs existants
echo "<strong>👥 Utilisateurs dans la base:</strong><br>";
try {
    $stmt = $db->query("SELECT id, nom, prenom, email, role, date_creation FROM UTILISATEURS");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ Aucun utilisateur trouvé dans la base!<br>";
        echo "➡️ <a href='create_admin.php'>Créer les comptes de test</a><br><br>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Date création</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['nom'] . "</td>";
            echo "<td>" . $user['prenom'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td><strong>" . $user['role'] . "</strong></td>";
            echo "<td>" . $user['date_creation'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Erreur lors de la récupération des utilisateurs: " . $e->getMessage() . "<br>";
}

// Tester l'authentification
echo "<strong>🔐 Test d'authentification:</strong><br>";

if ($_POST['email'] ?? false) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    echo "Email testé: <strong>$email</strong><br>";
    echo "Mot de passe testé: <strong>$password</strong><br><br>";
    
    try {
        $userModel = new User();
        $result = $userModel->authenticate($email, $password);
        
        if ($result) {
            echo "✅ <strong>Authentification réussie!</strong><br>";
            echo "Données utilisateur:<br>";
            echo "<pre>" . print_r($result, true) . "</pre>";
            
            // Tester la session
            $_SESSION['user'] = $result;
            echo "✅ Session créée!<br>";
            echo "➡️ <a href='index.php?controller=dashboard'>Aller au dashboard</a><br>";
            
        } else {
            echo "❌ <strong>Authentification échouée!</strong><br>";
            
            // Vérifier si l'email existe
            $stmt = $db->prepare("SELECT id, nom, prenom, email, role, mot_de_passe FROM UTILISATEURS WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "✅ Email trouvé dans la base<br>";
                echo "Hash stocké: " . substr($user['mot_de_passe'], 0, 20) . "...<br>";
                
                // Tester la vérification du mot de passe
                if (password_verify($password, $user['mot_de_passe'])) {
                    echo "✅ Mot de passe correct<br>";
                    echo "❓ Problème dans la méthode authenticate()<br>";
                } else {
                    echo "❌ Mot de passe incorrect<br>";
                    echo "➡️ Le hash du mot de passe ne correspond pas<br>";
                }
            } else {
                echo "❌ Email non trouvé dans la base<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur lors de l'authentification: " . $e->getMessage() . "<br>";
    }
}

// Formulaire de test
echo "<br><strong>🧪 Formulaire de test:</strong><br>";
echo "<form method='POST'>";
echo "Email: <input type='text' name='email' value='admin@cakeshop.com' style='margin: 5px;'><br>";
echo "Mot de passe: <input type='text' name='password' value='123456' style='margin: 5px;'><br>";
echo "<input type='submit' value='Tester la connexion' style='margin: 5px; padding: 5px 10px;'>";
echo "</form>";

// Afficher l'état de la session
echo "<br><strong>🎯 État de la session:</strong><br>";
if (isset($_SESSION['user'])) {
    echo "✅ Utilisateur connecté: <strong>" . $_SESSION['user']['email'] . "</strong> (Rôle: " . $_SESSION['user']['role'] . ")<br>";
    echo "➡️ <a href='index.php?controller=dashboard'>Aller au dashboard</a><br>";
    echo "➡️ <a href='index.php?controller=auth&action=logout'>Se déconnecter</a><br>";
} else {
    echo "❌ Aucun utilisateur connecté<br>";
}

echo "<br><strong>🔗 Liens utiles:</strong><br>";
echo "➡️ <a href='create_admin.php'>Créer les comptes de test</a><br>";
echo "➡️ <a href='index.php?controller=auth&action=login'>Page de connexion</a><br>";
echo "➡️ <a href='index.php'>Accueil</a><br>";
?>