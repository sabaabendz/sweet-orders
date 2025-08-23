<?php
// index.php - Routeur principal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer les paramètres
$controller = $_GET['controller'] ?? 'home';
$action = $_GET['action'] ?? 'index';

try {
    switch ($controller) {
        case 'auth':
            require_once __DIR__ . '/controller/AuthController.php';
            $controllerInstance = new AuthController();
            switch ($action) {
                case 'login':
                    $controllerInstance->login();
                    break;
                case 'authenticate':
                    $controllerInstance->authenticate();
                    break;
                case 'register':
                    $controllerInstance->register();
                    break;
                case 'store':
                    $controllerInstance->store();
                    break;
                case 'forgot':
                    $controllerInstance->forgot();
                    break;
                case 'forgot_process':
                    $controllerInstance->forgotProcess();
                    break;
                case 'logout':
                    $controllerInstance->logout();
                    break;
                default:
                    $controllerInstance->login();
            }
            break;

        case 'dashboard':
            require_once __DIR__ . '/controller/DashboardController.php';
            $controllerInstance = new DashboardController();
            $controllerInstance->index();
            break;

        case 'users':
        case 'utilisateurs':
            require_once __DIR__ . '/controller/UserController.php';
            $controllerInstance = new UserController();
            switch ($action) {
                case 'index':
                    $controllerInstance->index();
                    break;
                case 'store':
                    $controllerInstance->store();
                    break;
                case 'show':
                    $id = (int)($_GET['id'] ?? 0);
                    $controllerInstance->show($id);
                    break;
                default:
                    $controllerInstance->index();
            }
            break;

        case 'produits':
            // TODO: Créer ProductController
            include __DIR__ . '/view/home.php';
            break;

        case 'commandes':
            // TODO: Créer CommandeController  
            include __DIR__ . '/view/home.php';
            break;

        case 'home':
        default:
            include __DIR__ . '/view/home.php';
            break;
    }

} catch (Exception $e) {
    // En cas d'erreur, rediriger vers l'accueil
    error_log("Erreur dans le routeur: " . $e->getMessage());
    include __DIR__ . '/view/home.php';
}
?>