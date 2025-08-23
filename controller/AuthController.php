<?php
// controller/AuthController.php
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../model/Database.php';

class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ==========================
    // LOGIN FORM
    // ==========================
    public function login(): void {
        include __DIR__ . '/../view/login.php';
    }

    // ==========================
    // AUTHENTICATE USER
    // ==========================
    public function authenticate(): void {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->authenticate($email, $password);

        if ($user) {
            $_SESSION['user'] = $user;
            $_SESSION['login_success'] = "Connexion réussie !";
            header("Location: index.php?controller=users&action=index");
            exit;
        } else {
            $_SESSION['login_error'] = "Email ou mot de passe incorrect";
            header("Location: index.php?controller=auth&action=login");
            exit;
        }
    }

    // ==========================
    // LOGOUT
    // ==========================
    public function logout(): void {
        session_start();
        session_unset();
        session_destroy();
        header("Location: index.php?controller=auth&action=login");
        exit;
    }

    // ==========================
    // REGISTER FORM
    // ==========================
    public function register(): void {
        if (isset($_SESSION['user'])) {
            $this->redirectByRole($_SESSION['user']['role']);
            return;
        }

        $errors = $_SESSION['register_errors'] ?? [];
        unset($_SESSION['register_errors']);
        
        $success = $_SESSION['register_success'] ?? null;
        unset($_SESSION['register_success']);

        include __DIR__ . '/../view/register.php';
    }

    // ==========================
    // STORE NEW USER
    // ==========================
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=register');
            exit;
        }

        $errors = $this->validateRegistration($_POST);

        if (!empty($errors)) {
            $_SESSION['register_errors'] = $errors;
            header('Location: index.php?controller=auth&action=register');
            exit;
        }

        $data = [
            'nom' => trim($_POST['nom']),
            'prenom' => trim($_POST['prenom']),
            'email' => trim($_POST['email']),
            'mot_de_passe' => $_POST['mot_de_passe'],
            'role' => 'client'
        ];

        try {
            $userId = $this->userModel->create($data);
            $_SESSION['register_success'] = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        } catch (InvalidArgumentException $e) {
            $errorData = json_decode($e->getMessage(), true);
            $errors = is_array($errorData) ? $errorData : ['global' => 'Une erreur est survenue.'];
            $_SESSION['register_errors'] = $errors;
            header('Location: index.php?controller=auth&action=register');
            exit;
        }
    }

    // ==========================
    // HELPER METHODS
    // ==========================
    private function redirectByRole(string $role): void {
        switch ($role) {
            case 'admin':
                header('Location: index.php?controller=users&action=index');
                break;
            case 'preparateur':
                header('Location: index.php?controller=dashboard&action=preparateur');
                break;
            case 'client':
            default:
                header('Location: index.php');
                break;
        }
        exit;
    }

    private function validateRegistration(array $data): array {
        $errors = [];
        // Validation code here (same as your previous)
        return $errors;
    }

    private function checkEmailExists(string $email): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE email = ?");
            $stmt->execute([$email]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function isLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user']);
    }

    public static function hasRole(string $role): bool {
        return self::isLoggedIn() && $_SESSION['user']['role'] === $role;
    }

    public static function hasAnyRole(array $roles): bool {
        return self::isLoggedIn() && in_array($_SESSION['user']['role'], $roles);
    }

    public static function requireAdmin(): void {
        if (!self::hasRole('admin')) {
            $_SESSION['login_error'] = 'Accès non autorisé.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    public static function requireStaff(): void {
        if (!self::hasAnyRole(['admin','preparateur'])) {
            $_SESSION['login_error'] = 'Accès non autorisé.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    public static function requireClient(): void {
        if (!self::isLoggedIn()) {
            $_SESSION['login_error'] = 'Veuillez vous connecter.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    public static function getCurrentUser(): ?array {
        return self::isLoggedIn() ? $_SESSION['user'] : null;
    }
}
?>
