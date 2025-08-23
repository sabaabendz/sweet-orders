<?php
// model/User.php
require_once __DIR__ . '/Database.php';

final class User
{
    public const ROLES = ['admin', 'preparateur', 'client'];
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /* =================
       Authentification
       ================= */
    public function authenticate(string $email, string $password): ?array
    {
        // Hardcoded admin / preparateur
        if ($email === 'admin@cakeshop.com' && $password === '123456') {
            return [
                'id' => 1,
                'nom' => 'Admin',
                'prenom' => 'Admin',
                'email' => $email,
                'role' => 'admin'
            ];
        }
        if ($email === 'preparateur@cakeshop.com' && $password === '12345') {
            return [
                'id' => 2,
                'nom' => 'Preparateur',
                'prenom' => 'Preparateur',
                'email' => $email,
                'role' => 'preparateur'
            ];
        }

        // Check database users
        $stmt = $this->db->prepare("SELECT * FROM UTILISATEURS WHERE email = ?");
        $stmt->execute([trim($email)]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            unset($user['mot_de_passe']); // remove hash
            return $user;
        }

        return null;
    }

    /* =======================
       CRUD METHODS
       ======================= */

    private function validateData(array $data, bool $isUpdate = false): array
    {
        $errors = [];
        if (!$isUpdate || isset($data['nom'])) {
            $nom = trim($data['nom'] ?? '');
            if ($nom === '' || mb_strlen($nom) < 2) $errors['nom'] = "Nom trop court.";
        }
        if (!$isUpdate || isset($data['prenom'])) {
            $prenom = trim($data['prenom'] ?? '');
            if ($prenom === '' || mb_strlen($prenom) < 2) $errors['prenom'] = "Prénom trop court.";
        }
        if (!$isUpdate || isset($data['email'])) {
            $email = trim($data['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Email invalide.";
        }
        if (!$isUpdate || isset($data['mot_de_passe'])) {
            $pwd = (string)($data['mot_de_passe'] ?? '');
            if (!$isUpdate && mb_strlen($pwd) < 6) $errors['mot_de_passe'] = "Mot de passe (min 6 caractères).";
            if ($isUpdate && $pwd !== '' && mb_strlen($pwd) < 6) $errors['mot_de_passe'] = "Mot de passe (min 6 caractères).";
        }
        if (!$isUpdate || isset($data['role'])) {
            $role = $data['role'] ?? 'client';
            if (!in_array($role, self::ROLES, true)) $errors['role'] = "Rôle invalide.";
        }
        return $errors;
    }

    private function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE email = ? AND id <> ?");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE email = ?");
            $stmt->execute([$email]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $errors = $this->validateData($data);
        if ($errors) throw new InvalidArgumentException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        if ($this->emailExists($data['email'])) throw new InvalidArgumentException(json_encode(['email'=>'Email déjà utilisé.'], JSON_UNESCAPED_UNICODE));

        $stmt = $this->db->prepare("INSERT INTO UTILISATEURS (nom, prenom, email, mot_de_passe, role) VALUES (:nom,:prenom,:email,:mot_de_passe,:role)");
        $stmt->execute([
            ':nom' => trim($data['nom']),
            ':prenom' => trim($data['prenom']),
            ':email' => trim($data['email']),
            ':mot_de_passe' => password_hash($data['mot_de_passe'], PASSWORD_BCRYPT),
            ':role' => $data['role'] ?? 'client'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, nom, prenom, email, role, date_creation FROM UTILISATEURS WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(?string $role = null): array
    {
        if ($role && in_array($role, self::ROLES, true)) {
            $stmt = $this->db->prepare("SELECT id, nom, prenom, email, role, date_creation FROM UTILISATEURS WHERE role=? ORDER BY date_creation DESC");
            $stmt->execute([$role]);
            return $stmt->fetchAll();
        }
        $stmt = $this->db->query("SELECT id, nom, prenom, email, role, date_creation FROM UTILISATEURS ORDER BY date_creation DESC");
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $errors = $this->validateData($data, true);
        if ($errors) throw new InvalidArgumentException(json_encode($errors, JSON_UNESCAPED_UNICODE));

        if (isset($data['email']) && $this->emailExists($data['email'], $id)) throw new InvalidArgumentException(json_encode(['email'=>'Email déjà utilisé.'], JSON_UNESCAPED_UNICODE));

        $fields = []; $params = [':id'=>$id];
        foreach(['nom','prenom','email','role'] as $f) {
            if(isset($data[$f])) { $fields[]="$f=:$f"; $params[":$f"]=trim((string)$data[$f]); }
        }
        if(!empty($data['mot_de_passe'])) { $fields[]="mot_de_passe=:mot_de_passe"; $params[':mot_de_passe']=password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);}
        if(!$fields) return false;
        $stmt=$this->db->prepare("UPDATE UTILISATEURS SET ".implode(', ',$fields)." WHERE id=:id");
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt=$this->db->prepare("DELETE FROM UTILISATEURS WHERE id=?");
        return $stmt->execute([$id]);
    }
}
