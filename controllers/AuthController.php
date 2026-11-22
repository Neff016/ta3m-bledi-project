<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnexion();
    }

    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    private function existsByEmailOrUsername(string $email, string $username): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'username' => $username]);

        return (bool) $stmt->fetch();
    }

    private function insertUser(UserModel $user): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)'
        );

        return $stmt->execute([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'role' => $user->getRole(),
        ]);
    }

    public function register(array $post): array
    {
        $errors = [];

        $username = trim($post['username'] ?? '');
        $email = trim($post['email'] ?? '');
        $password = $post['password'] ?? '';
        $passwordConfirm = $post['password_confirm'] ?? '';

        if (!$this->verifyCsrfToken($post['csrf_token'] ?? null)) {
            $errors[] = 'Invalid security token. Please try again.';
            return $errors;
        }

        if ($username === '' || $email === '' || $password === '') {
            $errors[] = 'All fields are required.';
        }
        if (!preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3 to 50 characters (letters, digits, _ or -).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }
        if (!empty($errors)) {
            return $errors;
        }
        if ($this->existsByEmailOrUsername($email, $username)) {
            $errors[] = 'This email or username is already taken.';
            return $errors;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $user = new UserModel();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($hashedPassword);
        $user->setRole('user');

        if (!$this->insertUser($user)) {
            $errors[] = 'An error occurred while creating your account.';
        }

        return $errors;
    }

    private function findByEmail(string $email): ?UserModel
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? UserModel::fromArray($row) : null;
    }

    public function login(array $post): array
    {
        $errors = [];

        if (!$this->verifyCsrfToken($post['csrf_token'] ?? null)) {
            $errors[] = 'Invalid security token. Please try again.';
            return $errors;
        }

        $email = trim($post['email'] ?? '');
        $password = $post['password'] ?? '';

        if ($email === '' || $password === '') {
            $errors[] = 'Please enter your email and password.';
            return $errors;
        }

        $user = $this->findByEmail($email);

        if (!$user || !password_verify($password, $user->getPassword())) {
            $errors[] = 'Incorrect email or password.';
            return $errors;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['last_activity'] = time();

        return [];
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public function findById(int $id): ?UserModel
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? UserModel::fromArray($row) : null;
    }

    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: index.php?action=login');
            exit;
        }
    }

    public static function hasRole(string $role): bool
    {
        return self::isAuthenticated() && ($_SESSION['role'] ?? null) === $role;
    }

    public static function requireRole(string $role): void
    {
        self::requireAuth();
        if (($_SESSION['role'] ?? null) !== $role) {
            http_response_code(403);
            die('Access denied: you do not have the required permissions.');
        }
    }

    public function getAllUsers(): array
    {
        $stmt = $this->db->query('SELECT * FROM users ORDER BY id ASC');
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row) => UserModel::fromArray($row), $rows);
    }

    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);

        return (int) $stmt->fetchColumn();
    }

    public function countAllUsers(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    private function verifyCsrfToken(?string $token): bool
    {
        if (!$token || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function createUser(array $post): array
    {
        self::requireRole('admin');
        $errors = [];
        if (!$this->verifyCsrfToken($post['csrf_token'] ?? null)) {
            $errors[] = 'Invalid security token. Please try again.';
            return $errors;
        }

        $username = trim($post['username'] ?? '');
        $email = trim($post['email'] ?? '');
        $password = $post['password'] ?? '';
        $role = ($post['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

        if ($username === '' || $email === '' || $password === '') {
            $errors[] = 'All fields are required.';
        }
        if (!preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3 to 50 characters (letters, digits, _ or -).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (!empty($errors)) {
            return $errors;
        }
        if ($this->existsByEmailOrUsername($email, $username)) {
            $errors[] = 'This email or username is already taken.';
            return $errors;
        }

        $user = new UserModel();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $user->setRole($role);

        if (!$this->insertUser($user)) {
            $errors[] = 'An error occurred while creating your account.';
        }

        return $errors;
    }

    public function updateUser(array $post): array
    {
        self::requireRole('admin');
        $errors = [];
        if (!$this->verifyCsrfToken($post['csrf_token'] ?? null)) {
            $errors[] = 'Invalid security token. Please try again.';
            return $errors;
        }

        $id = (int) ($post['id'] ?? 0);
        $username = trim($post['username'] ?? '');
        $email = trim($post['email'] ?? '');
        $password = $post['password'] ?? '';
        $role = ($post['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

        if ($id <= 0 || $username === '' || $email === '') {
            $errors[] = 'All fields are required.';
        }
        if (!preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3 to 50 characters (letters, digits, _ or -).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }
        if ($password !== '' && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (!empty($errors)) {
            return $errors;
        }

        $existing = $this->findById($id);
        if (!$existing) {
            $errors[] = 'User not found.';
            return $errors;
        }

        $stmt = $this->db->prepare(
            'SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'username' => $username, 'id' => $id]);
        if ($stmt->fetch()) {
            $errors[] = 'This email or username is already taken.';
            return $errors;
        }

        if ($password !== '') {
            $stmt = $this->db->prepare(
                'UPDATE users SET username = :username, email = :email, password = :password, role = :role WHERE id = :id'
            );
            $success = $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'id' => $id,
            ]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id'
            );
            $success = $stmt->execute([
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'id' => $id,
            ]);
        }

        if (!$success) {
            $errors[] = 'An error occurred while updating the user.';
        }

        return $errors;
    }

    public function deleteUser(array $post): array
    {
        self::requireRole('admin');
        $errors = [];
        if (!$this->verifyCsrfToken($post['csrf_token'] ?? null)) {
            $errors[] = 'Invalid security token. Please try again.';
            return $errors;
        }

        $id = (int) ($post['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'User not found.';
            return $errors;
        }
        if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
            $errors[] = 'You cannot delete your own account.';
            return $errors;
        }

        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        if (!$stmt->execute(['id' => $id])) {
            $errors[] = 'An error occurred while deleting the user.';
        }

        return $errors;
    }
}
