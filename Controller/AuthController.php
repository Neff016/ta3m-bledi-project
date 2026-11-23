<?php
/**
 * CONTROLLER — AuthController.php
 * Gère la connexion (admin / agriculteur), l'inscription agriculteur, et le 1er Admin.
 */
require_once __DIR__ . '/../model/UserModel.php';

class AuthController
{
    private UserModel $userModel;

    public function __construct(PDO $pdo)
    {
        $this->userModel = new UserModel($pdo);
    }

    public function showLogin(): void
    {
        $needsAdmin = !$this->userModel->hasAdmin();
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        require __DIR__ . '/../view/login.php';
    }

    public function login(): void
    {
        verifyCsrf();
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'E-mail ou mot de passe incorrect.';
            redirect('login');
        }

        if ($user['role'] !== 'admin' && $user['role'] !== 'farmer') {
            $_SESSION['flash_error'] = 'Accès refusé : réservé aux administrateurs et agriculteurs.';
            redirect('login');
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        redirect('dashboard');
    }

    public function register(): void
    {
        verifyCsrf();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = ($_POST['role'] ?? '') === 'user' ? 'user' : 'farmer';
        $password = $_POST['password'] ?? '';

        if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Veuillez remplir correctement tous les champs (mot de passe 6+ car.).';
            redirect('login');
        }

        if ($this->userModel->findByEmail($email)) {
            $_SESSION['flash_error'] = 'Un compte existe déjà avec cet e-mail.';
            redirect('login');
        }

        $this->userModel->create($name, $email, $role, $password);

        if ($role === 'user') {
            $_SESSION['flash_success'] = 'Compte simple utilisateur créé, mais sans accès au backoffice.';
            redirect('login');
        }

        $created = $this->userModel->findByEmail($email);
        $_SESSION['user'] = [
            'id' => $created['id'],
            'name' => $created['name'],
            'email' => $created['email'],
            'role' => 'farmer',
        ];
        redirect('dashboard');
    }

    public function setupAdmin(): void
    {
        verifyCsrf();
        if ($this->userModel->hasAdmin()) {
            $_SESSION['flash_error'] = 'Un administrateur existe déjà.';
            redirect('login');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Champs invalides pour le compte Administrateur.';
            redirect('login');
        }

        $this->userModel->create($name, $email, 'admin', $password);
        $admin = $this->userModel->findByEmail($email);
        $_SESSION['user'] = [
            'id' => $admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'role' => 'admin',
        ];
        redirect('dashboard');
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: index.php?page=login');
        exit;
    }
}
