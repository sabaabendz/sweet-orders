<?php
// controller/UserController.php
require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../controller/AuthController.php';

final class UserController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        AuthController::requireAdmin(); // Admin only
    }

    // ===============================
    // LISTE DE TOUS LES UTILISATEURS
    // ===============================
    public function index(): void {
        $role = $_GET['role'] ?? null;
        $users = $this->userModel->getAll($role);
        $content = 'list';
        include __DIR__ . '/../view/users.php';
    }

    // ===============================
    // AFFICHER UN UTILISATEUR
    // ===============================
    public function show(int $id): void {
        $user_detail = $this->userModel->getUserById($id);
        if (!$user_detail) {
            $_SESSION['error'] = "Utilisateur introuvable";
            header("Location: index.php?controller=users&action=index");
            exit;
        }
        $content = 'show';
        include __DIR__ . '/../view/users.php';
    }

    // ===============================
    // FORMULAIRE CREATE/EDIT
    // ===============================
    public function form(?int $id = null): void {
        $user_detail = $id ? $this->userModel->getUserById($id) : null;
        $content = 'form';
        include __DIR__ . '/../view/users.php';
    }

    // ===============================
    // CRÉER UN NOUVEL UTILISATEUR
    // ===============================
    public function store(): void {
        AuthController::requireAdmin();
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'prenom' => trim($_POST['prenom'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'mot_de_passe' => $_POST['mdp'] ?? '',
            'role' => $_POST['role'] ?? 'client'
        ];
        $errors = [];
        if (empty($data['nom']) || strlen($data['nom']) < 2) {
            $errors[] = "Le nom doit contenir au moins 2 caractères.";
        }
        if (empty($data['prenom']) || strlen($data['prenom']) < 2) {
            $errors[] = "Le prénom doit contenir au moins 2 caractères.";
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide.";
        }
        if (empty($data['mot_de_passe']) || strlen($data['mot_de_passe']) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        }
        if (!in_array($data['role'], ['client', 'preparateur', 'admin'])) {
            $errors[] = "Rôle invalide.";
        }
        if (empty($errors)) {
            try {
                $id = $this->userModel->create($data);
                $_SESSION['success'] = 'Utilisateur créé avec succès.';
                header('Location: index.php?controller=users&action=index');
                exit;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header('Location: index.php?controller=users&action=form');
            exit;
        }
    }

    // ===============================
    // MODIFIER UN UTILISATEUR
    // ===============================
    public function update(): void {
        AuthController::requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'prenom' => trim($_POST['prenom'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => $_POST['role'] ?? 'client'
        ];
        $errors = [];
        if (empty($data['nom']) || strlen($data['nom']) < 2) {
            $errors[] = "Le nom doit contenir au moins 2 caractères.";
        }
        if (empty($data['prenom']) || strlen($data['prenom']) < 2) {
            $errors[] = "Le prénom doit contenir au moins 2 caractères.";
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide.";
        }
        if (!in_array($data['role'], ['client', 'preparateur', 'admin'])) {
            $errors[] = "Rôle invalide.";
        }
        if (!empty($_POST['mdp']) && strlen($_POST['mdp']) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        }
        if (!empty($_POST['mdp'])) {
            $data['mot_de_passe'] = $_POST['mdp'];
        }
        if (empty($errors)) {
            try {
                $this->userModel->update($id, $data);
                $_SESSION['success'] = 'Utilisateur mis à jour avec succès.';
                header('Location: index.php?controller=users&action=index');
                exit;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header('Location: index.php?controller=users&action=form&id=' . $id);
            exit;
        }
    }

    // ===============================
    // SUPPRIMER UN UTILISATEUR
    // ===============================
    public function delete(int $id): void {
        try {
            $this->userModel->delete($id);
            $_SESSION['success'] = "Utilisateur supprimé avec succès";
        } catch (Exception $e) {
            $_SESSION['error'] = "Impossible de supprimer l'utilisateur";
        }
        header("Location: index.php?controller=users&action=index");
        exit;
    }
}
