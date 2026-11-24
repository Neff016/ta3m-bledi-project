<?php
require_once __DIR__ . '/AdminController.php';

class AuthController extends AdminController
{
    private const MAX_FAILS = 5;
    private const LOCK_SECONDS = 60;

    public function login(): void
    {
        // Already logged in as admin: go straight to the dashboard
        if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
            $this->redirect('dashboard');
        }

        $error = null;
        $email = '';
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        if ($this->isPost()) {
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $pass  = (string)($_POST['pass'] ?? '');

            if (!csrf_valid()) {
                $error = 'Formulaire expiré, veuillez réessayer.';
            } elseif (($_SESSION['lock_until'] ?? 0) > time()) {
                $error = 'Trop de tentatives. Réessayez dans ' . ($_SESSION['lock_until'] - time()) . ' s.';
            } elseif ($email === '' || $pass === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Saisissez une adresse e-mail valide et votre mot de passe.';
            } else {
                $u = Utilisateur::findByEmail($email);
                if (!$u || !password_verify($pass, $u['motDePasse'])) {
                    $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
                    if ($_SESSION['fails'] >= self::MAX_FAILS) {
                        $_SESSION['lock_until'] = time() + self::LOCK_SECONDS;
                        $_SESSION['fails'] = 0;
                    }
                    $error = 'Identifiants incorrects.';
                } elseif ($u['statut'] !== 'actif') {
                    $error = 'Ce compte est bloqué.';
                } elseif ($u['role'] !== 'admin') {
                    $error = 'Accès réservé aux administrateurs.';
                } else {
                    // New session id after login: protects against session fixation
                    session_regenerate_id(true);
                    $_SESSION['user_id']       = (int)$u['idUtilisateur'];
                    $_SESSION['role']          = $u['role'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['fails']         = 0;
                    unset($_SESSION['lock_until']);

                    if (password_needs_rehash($u['motDePasse'], PASSWORD_DEFAULT)) {
                        $u['motDePasse'] = password_hash($pass, PASSWORD_DEFAULT);
                        Utilisateur::save($u);
                    }
                    $this->redirect('dashboard');
                }
            }
        }
        // The login page is standalone (no sidebar), like signin.html in the template
        require __DIR__ . '/../View/BackOffice/pages/login.php';
    }

    public function logout(): void
    {
        if ($this->isPost() && csrf_valid()) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
            session_start();
        }
        $this->redirect('login');
    }
}
