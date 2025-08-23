<?php
// controller/UserController.php
require_once __DIR__ . '/../model/User.php';

final class UserController {
    private User $model;

    public function __construct() {
        $this->model = new User();
    }

    public function index(): void {
        $users = $this->model->getAll(); // ou getAll('client')
        include __DIR__ . '/../view/users_list.php';
    }

    public function store(): void {
        // Exemple: données reçues d’un POST (sécurise côté contrôleur)
        $data = [
            'nom'           => $_POST['nom']   ?? '',
            'prenom'        => $_POST['prenom']?? '',
            'email'         => $_POST['email'] ?? '',
            'mot_de_passe'  => $_POST['mdp']   ?? '',
            'role'          => $_POST['role']  ?? 'client',
        ];

        try {
            $id = $this->model->create($data);
            // redirection + flash message
            header("Location: index.php?controller=users&action=show&id=".$id);
            exit;
        } catch (InvalidArgumentException $e) {
            $errors = json_decode($e->getMessage(), true) ?? ['global' => "Données invalides"];
            include __DIR__ . '/../view/user_form.php'; // réaffiche le form avec $errors
        }
    }
}
