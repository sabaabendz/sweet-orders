<?php
// index.php - Fixed Router with cancel action
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get parameters
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
                case 'show':
                    $id = (int)($_GET['id'] ?? 0);
                    $controllerInstance->show($id);
                    break;
                case 'form':
                    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
                    $controllerInstance->form($id);
                    break;
                case 'store':
                    $controllerInstance->store();
                    break;
                case 'update':
                    $controllerInstance->update();
                    break;
                case 'delete':
                    $id = (int)($_GET['id'] ?? 0);
                    $controllerInstance->delete($id);
                    break;
                default:
                    $controllerInstance->index();
            }
            break;

        case 'commandes':
        case 'orders':
            require_once __DIR__ . '/controller/OrdersController.php';
            $controllerInstance = new OrdersController();
            switch ($action) {
                case 'index':
                case 'list':
                    $controllerInstance->index();
                    break;
                case 'view':
                case 'show':
                    $controllerInstance->view();
                    break;
                case 'clientView':
                    $controllerInstance->clientView();
                    break;
                case 'updateStatus':
                    $controllerInstance->updateStatus();
                    break;
                case 'create':
                    $controllerInstance->create();
                    break;
                case 'delete':
                    $controllerInstance->delete();
                    break;
                case 'cancel':
                    // ADDED: Handle cancel action properly
                    $controllerInstance->cancel();
                    break;
                case 'historique':
                    $controllerInstance->historique();
                    break;
                default:
                    // Check if user is client, show history, otherwise show all orders
                    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'client') {
                        $controllerInstance->historique();
                    } else {
                        $controllerInstance->index();
                    }
            }
            break;

        case 'client':
            require_once __DIR__ . '/controller/ClientController.php';
            $controllerInstance = new ClientController();
            switch ($action) {
                case 'catalogue':
                    $controllerInstance->catalogue();
                    break;
                case 'cart':
                    $controllerInstance->cart();
                    break;
                case 'addToCart':
                    $controllerInstance->addToCart();
                    break;
                case 'checkout':
                    $controllerInstance->checkout();
                    break;
                default:
                    $controllerInstance->catalogue();
            }
            break;

        case 'home':
        default:
            include __DIR__ . '/view/home.php';
            break;
    }

} catch (Exception $e) {
    // In case of error, redirect to home
    error_log("Router error: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur s'est produite: " . $e->getMessage();
    include __DIR__ . '/view/home.php';
}
?>