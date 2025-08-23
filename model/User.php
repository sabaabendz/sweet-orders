<?php
// model/User.php
require_once __DIR__ . '/Database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Method called by UserController->index()
    public function getAll(?string $role = null): array {
        $query = "SELECT * FROM UTILISATEURS";
        $params = [];
        
        if ($role) {
            $query .= " WHERE role = ?";
            $params[] = $role;
        }
        
        $query .= " ORDER BY date_creation DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Alias for backward compatibility
    public function getAllUsers(): array {
        return $this->getAll();
    }

    public function getUserById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM UTILISATEURS WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function create(array $data): int {
        // Validation
        $errors = $this->validateUserData($data, false);
        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            throw new InvalidArgumentException(json_encode(['email' => 'Cet email est déjà utilisé.']));
        }

        $stmt = $this->db->prepare("
            INSERT INTO UTILISATEURS (nom, prenom, email, mot_de_passe, role, date_creation)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            trim($data['nom']),
            trim($data['prenom']),
            trim($data['email']),
            password_hash($data['mot_de_passe'], PASSWORD_BCRYPT),
            $data['role'] ?? 'client'
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        // Validation
        $errors = $this->validateUserData($data, true);
        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        // Check if email exists for another user
        if ($this->emailExistsForOtherUser($data['email'], $id)) {
            throw new InvalidArgumentException(json_encode(['email' => 'Cet email est déjà utilisé par un autre utilisateur.']));
        }

        $query = "UPDATE UTILISATEURS SET nom = ?, prenom = ?, email = ?, role = ?";
        $params = [
            trim($data['nom']),
            trim($data['prenom']),
            trim($data['email']),
            $data['role']
        ];
        
        // Update password only if provided
        if (!empty($data['mot_de_passe'])) {
            $query .= ", mot_de_passe = ?";
            $params[] = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);
        }
        
        $query .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        // Don't allow deletion of the last admin
        $stmt = $this->db->prepare("SELECT role FROM UTILISATEURS WHERE id = ?");
        $stmt->execute([$id]);
        $userRole = $stmt->fetchColumn();
        
        if ($userRole === 'admin') {
            $stmt = $this->db->query("SELECT COUNT(*) FROM UTILISATEURS WHERE role = 'admin'");
            $adminCount = (int)$stmt->fetchColumn();
            
            if ($adminCount <= 1) {
                throw new InvalidArgumentException("Impossible de supprimer le dernier administrateur.");
            }
        }

        $stmt = $this->db->prepare("DELETE FROM UTILISATEURS WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function authenticate(string $email, string $password): array|false {
        $stmt = $this->db->prepare("SELECT * FROM UTILISATEURS WHERE email = ?");
        $stmt->execute([trim($email)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Remove password from returned data for security
            unset($user['mot_de_passe']);
            return $user;
        }
        
        return false;
    }

    // Statistics methods
    public function countByRole(string $role): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE role = ?");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    public function countClients(): int {
        return $this->countByRole('client');
    }

    public function countAdmins(): int {
        return $this->countByRole('admin');
    }

    public function countPreparateurs(): int {
        return $this->countByRole('preparateur');
    }

    public function getRecentUsers(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT id, nom, prenom, email, role, date_creation 
            FROM UTILISATEURS 
            ORDER BY date_creation DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Private helper methods
    private function validateUserData(array $data, bool $isUpdate = false): array {
        $errors = [];

        // Nom validation
        if (empty(trim($data['nom'] ?? ''))) {
            $errors['nom'] = 'Le nom est requis.';
        } elseif (strlen(trim($data['nom'])) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caractères.';
        } elseif (strlen(trim($data['nom'])) > 100) {
            $errors['nom'] = 'Le nom ne peut pas dépasser 100 caractères.';
        }

        // Prénom validation
        if (empty(trim($data['prenom'] ?? ''))) {
            $errors['prenom'] = 'Le prénom est requis.';
        } elseif (strlen(trim($data['prenom'])) < 2) {
            $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères.';
        } elseif (strlen(trim($data['prenom'])) > 100) {
            $errors['prenom'] = 'Le prénom ne peut pas dépasser 100 caractères.';
        }

        // Email validation
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide.';
        } elseif (strlen($email) > 150) {
            $errors['email'] = 'L\'email ne peut pas dépasser 150 caractères.';
        }

        // Password validation (only for creation or when updating password)
        if (!$isUpdate || !empty($data['mot_de_passe'])) {
            if (empty($data['mot_de_passe'] ?? '')) {
                $errors['mot_de_passe'] = 'Le mot de passe est requis.';
            } elseif (strlen($data['mot_de_passe']) < 6) {
                $errors['mot_de_passe'] = 'Le mot de passe doit contenir au moins 6 caractères.';
            } elseif (strlen($data['mot_de_passe']) > 255) {
                $errors['mot_de_passe'] = 'Le mot de passe est trop long.';
            }
        }

        // Role validation
        $allowedRoles = ['client', 'preparateur', 'admin'];
        if (!empty($data['role']) && !in_array($data['role'], $allowedRoles)) {
            $errors['role'] = 'Rôle invalide.';
        }

        return $errors;
    }

    private function emailExists(string $email): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE email = ?");
        $stmt->execute([trim($email)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function emailExistsForOtherUser(string $email, int $userId): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE email = ? AND id != ?");
        $stmt->execute([trim($email), $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // Method to get user by email (useful for authentication and checks)
    public function getUserByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM UTILISATEURS WHERE email = ?");
        $stmt->execute([trim($email)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    // Method to check if user exists
    public function userExists(int $id): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // Method to get users by role
    public function getUsersByRole(string $role): array {
        return $this->getAll($role);
    }

    // Method to search users
    public function searchUsers(string $search, ?string $role = null): array {
        $query = "SELECT * FROM UTILISATEURS WHERE (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%"];
        
        if ($role) {
            $query .= " AND role = ?";
            $params[] = $role;
        }
        
        $query .= " ORDER BY date_creation DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method to get user statistics
    public function getUserStats(): array {
        $stats = [];
        
        // Count users by role
        $stmt = $this->db->query("
            SELECT role, COUNT(*) as count 
            FROM UTILISATEURS 
            GROUP BY role
        ");
        $roleStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $stats['clients'] = (int)($roleStats['client'] ?? 0);
        $stats['preparateurs'] = (int)($roleStats['preparateur'] ?? 0);
        $stats['admins'] = (int)($roleStats['admin'] ?? 0);
        $stats['total'] = array_sum($roleStats);
        
        // Recent registrations (last 7 days)
        $stmt = $this->db->query("
            SELECT COUNT(*) FROM UTILISATEURS 
            WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stats['recent_registrations'] = (int)$stmt->fetchColumn();
        
        return $stats;
    }
}
?>