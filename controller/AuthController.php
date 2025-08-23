<?php
// controller/AuthController.php
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../model/Database.php';

final class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Afficher le formulaire de connexion
    public function login(): void {
        // Si déjà connecté, rediriger selon le rôle
        if (isset($_SESSION['user'])) {
            $this->redirectByRole($_SESSION['user']['role']);
            return;
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        
        $success = $_SESSION['login_success'] ?? null;
        unset($_SESSION['login_success']);
        
        include __DIR__ . '/../view/login.php';
    }

    // Traiter la connexion
    public function authenticate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Veuillez remplir tous les champs.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $user = $this->userModel->authenticate($email, $password);
        
        if ($user) {
            $_SESSION['user'] = $user;
            $_SESSION['login_success'] = 'Connexion réussie !';
            $this->redirectByRole($user['role']);
        } else {
            $_SESSION['login_error'] = 'Email ou mot de passe incorrect.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    // Afficher le formulaire d'inscription
    public function register(): void {
        // Si déjà connecté, rediriger
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

    // Traiter l'inscription
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=register');
            exit;
        }

        // Validation côté serveur
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
            'role' => 'client' // Tous les nouveaux comptes sont clients par défaut
        ];

        try {
            $userId = $this->userModel->create($data);
            $_SESSION['register_success'] = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        } catch (InvalidArgumentException $e) {
            $errorData = json_decode($e->getMessage(), true);
            if (is_array($errorData)) {
                $errors = $errorData;
            } else {
                $errors = ['global' => 'Une erreur est survenue lors de la création du compte.'];
            }
            $_SESSION['register_errors'] = $errors;
            header('Location: index.php?controller=auth&action=register');
            exit;
        }
    }

    // Déconnexion
    public function logout(): void {
        session_start();
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit;
    }

    // Rediriger selon le rôle
    private function redirectByRole(string $role): void {
        switch ($role) {
            case 'admin':
                header('Location: index.php?controller=dashboard&action=admin');
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

    // Validation des données d'inscription
    private function validateRegistration(array $data): array {
        $errors = [];

        // Nom
        $nom = trim($data['nom'] ?? '');
        if (empty($nom)) {
            $errors['nom'] = 'Le nom est requis.';
        } elseif (strlen($nom) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caractères.';
        }

        // Prénom
        $prenom = trim($data['prenom'] ?? '');
        if (empty($prenom)) {
            $errors['prenom'] = 'Le prénom est requis.';
        } elseif (strlen($prenom) < 2) {
            $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères.';
        }

        // Email
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide.';
        } elseif ($this->checkEmailExists($email)) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }

        // Mot de passe
        $password = $data['mot_de_passe'] ?? '';
        if (empty($password)) {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }

        // Confirmation mot de passe
        $passwordConfirm = $data['password_confirm'] ?? '';
        if (empty($passwordConfirm)) {
            $errors['password_confirm'] = 'Veuillez confirmer le mot de passe.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }

        return $errors;
    }

    // Vérifier si l'email existe
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

    // Méthodes statiques pour la gestion des sessions et autorisations

    // Vérifier si l'utilisateur est connecté
    public static function isLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user']);
    }

    // Vérifier si l'utilisateur a le bon rôle
    public static function hasRole(string $role): bool {
        if (!self::isLoggedIn()) {
            return false;
        }
        return $_SESSION['user']['role'] === $role;
    }

    // Vérifier si l'utilisateur a l'un des rôles spécifiés
    public static function hasAnyRole(array $roles): bool {
        if (!self::isLoggedIn()) {
            return false;
        }
        return in_array($_SESSION['user']['role'], $roles);
    }

    // Middleware pour protéger les pages admin
    public static function requireAdmin(): void {
        if (!self::hasRole('admin')) {
            $_SESSION['login_error'] = 'Accès non autorisé. Veuillez vous connecter en tant qu\'administrateur.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    // Middleware pour protéger les pages admin/préparateur
    public static function requireStaff(): void {
        if (!self::hasAnyRole(['admin', 'preparateur'])) {
            $_SESSION['login_error'] = 'Accès non autorisé. Veuillez vous connecter avec un compte autorisé.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    // Middleware pour protéger les pages client
    public static function requireClient(): void {
        if (!self::isLoggedIn()) {
            $_SESSION['login_error'] = 'Veuillez vous connecter pour accéder à cette page.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    // Récupérer l'utilisateur connecté
    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) {
            return null;
        }
        return $_SESSION['user'];
    }

    // Récupérer le rôle de l'utilisateur connecté
    public static function getCurrentUserRole(): ?string {
        $user = self::getCurrentUser();
        return $user['role'] ?? null;
    }

    // Récupérer l'ID de l'utilisateur connecté
    public static function getCurrentUserId(): ?int {
        $user = self::getCurrentUser();
        return $user['id'] ?? null;
    }
}
?>