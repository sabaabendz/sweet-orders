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

    
    // LOGIN FORM
    
    public function login(): void {
        include __DIR__ . '/../view/login.php';
    }

    
    // AUTHENTICATE USER
    
    public function authenticate(): void {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->authenticate($email, $password);

        if ($user) {
            $_SESSION['user'] = $user;
            $_SESSION['login_success'] = "Connexion réussie !";
            $this->redirectByRole($user['role']);
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
        if (session_status() === PHP_SESSION_NONE) session_start();
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
            'mot_de_passe' => password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT),
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
                header('Location: index.php?controller=dashboard&action=index');
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
        if (empty($data['nom']) || strlen(trim($data['nom'])) < 2) $errors[] = "Le nom doit contenir au moins 2 caractères.";
        if (empty($data['prenom']) || strlen(trim($data['prenom'])) < 2) $errors[] = "Le prénom doit contenir au moins 2 caractères.";
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
        if (empty($data['mot_de_passe']) || strlen($data['mot_de_passe']) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";

        if ($this->checkEmailExists($data['email'])) $errors[] = "Email déjà utilisé.";

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

    // ==========================
    // SESSION / ROLE CHECKS
    // ==========================
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
