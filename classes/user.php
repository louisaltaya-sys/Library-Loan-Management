<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Database.php';

class User
{
    private PDO $db;
    private string $table = "users";

    public ?int $user_id = null;
    public ?string $username = null;
    public ?string $password = null;
    public ?string $full_name = null;
    public ?string $email = null;
    public ?string $role = null;
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $contact_number = null;
    public ?string $gender = null;
    public ?string $address = null;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function register(): int|false
    {
        try {
            $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
            $email = $this->email ?? bin2hex(random_bytes(16)) . '@local.invalid';

            $sql = "INSERT INTO {$this->table}
                        (name, firstname, lastname, username, password, email, contact_number, gender, address)
                    VALUES
                        (:name, :firstname, :lastname, :username, :password, :email, :contact_number, :gender, :address)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':name', $this->full_name ?? $this->username);
            $stmt->bindValue(':firstname', $this->first_name);
            $stmt->bindValue(':lastname', $this->last_name);
            $stmt->bindValue(':username', $this->username);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':contact_number', $this->contact_number);
            $stmt->bindValue(':gender', $this->gender);
            $stmt->bindValue(':address', $this->address);
            $stmt->execute();
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("register failed: " . $e->getMessage());
            return false;
        }
    }

    public function usernameExists(string $username): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE username = :username LIMIT 1");
            $stmt->bindValue(':username', $username);
            $stmt->execute();
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log("usernameExists failed: " . $e->getMessage());
            return false;
        }
    }

    public function emailExists(string $email): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE email = :email LIMIT 1");
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log("emailExists failed: " . $e->getMessage());
            return false;
        }
    }

    public function login(string $username, string $password): array|false
    {
        try {
            $sql = "SELECT id AS user_id, username, password, name AS full_name, 'staff' AS role
                    FROM {$this->table}
                    WHERE username = :username
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("login failed: " . $e->getMessage());
            return false;
        }
    }

    public function getUsers(): array
    {
        try {
            $sql = "SELECT id AS user_id, username, name AS full_name, email, 'staff' AS role
                    FROM {$this->table}
                    ORDER BY name ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getUsers failed: " . $e->getMessage());
            return [];
        }
    }

    public function getUserById(int $id): array|false
    {
        try {
            $sql = "SELECT id AS user_id, username, name AS full_name, email, 'staff' AS role
                    FROM {$this->table}
                    WHERE id = :id
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getUserById failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser(int $id): bool
    {
        try {
            $sql = "UPDATE {$this->table}
                    SET username = :username,
                        name = :full_name,
                        email = :email
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':username', $this->username);
            $stmt->bindValue(':full_name', $this->full_name);
            $stmt->bindValue(':email', $this->email);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("updateUser failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("deleteUser failed: " . $e->getMessage());
            return false;
        }
    }

    public function searchUsers(string $keyword): array
    {
        try {
            $sql = "SELECT id AS user_id, username, name AS full_name, email, 'staff' AS role
                    FROM {$this->table}
                    WHERE username LIKE :kw
                       OR name LIKE :kw
                       OR email LIKE :kw
                    ORDER BY name ASC";

            $stmt = $this->db->prepare($sql);
            $term = "%{$keyword}%";
            $stmt->bindValue(':kw', $term, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("searchUsers failed: " . $e->getMessage());
            return [];
        }
    }
}