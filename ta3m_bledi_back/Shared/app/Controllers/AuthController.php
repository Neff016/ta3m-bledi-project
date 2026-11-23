<?php
declare(strict_types=1);

class AuthController extends Controller
{
    public function loginForm(): void
    {
        // Si le visiteur clique sur "Se connecter" depuis un atelier,
        // on mémorise cet atelier pour finaliser l'inscription après connexion.
        $workshopId = filter_input(INPUT_GET, 'workshop_id', FILTER_VALIDATE_INT);
        if ($workshopId && $workshopId > 0 && APP_AREA === 'frontoffice') {
            $_SESSION['pending_workshop_id'] = $workshopId;
        }
        $this->render('login', [], 'Connexion');
    }

    public function login(): void
    {
        verify_csrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = (new User())->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'E-mail ou mot de passe incorrect.');
            redirect('index.php?page=login');
        }

        $requiredRole = (APP_AREA === 'backoffice') ? 'admin' : 'user';
        if (($user['role'] ?? '') !== $requiredRole) {
            flash('error', $requiredRole === 'admin'
                ? 'Cet espace est réservé à l’administrateur. Connecte-toi depuis le Back Office.'
                : 'Ce compte utilisateur doit se connecter depuis le Front Office.');
            if (($user['role'] ?? '') === 'admin') {
                redirect(BACK_OFFICE_URL . '?page=login');
            }
            redirect(FRONT_OFFICE_URL . '?page=login');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        if ($requiredRole === 'user' && !empty($_SESSION['pending_workshop_id'])) {
            $workshopId = (int) $_SESSION['pending_workshop_id'];
            unset($_SESSION['pending_workshop_id']);
            try {
                (new Registration())->register((int) $user['id'], $workshopId);
                flash('success', 'Bienvenue ' . $user['name'] . ' ! Ton inscription à l’atelier est confirmée.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
            }
            redirect('index.php?page=mes-inscriptions');
        }

        flash('success', 'Bienvenue ' . $user['name'] . ' !');
        redirect($requiredRole === 'admin' ? 'index.php?page=admin' : 'index.php?page=ateliers');
    }

    public function registerForm(): void
    {
        $this->render('register', [], 'Créer un compte');
    }

    public function register(): void
    {
        verify_csrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            flash('error', 'Vérifie les champs. Le mot de passe doit contenir au moins 8 caractères.');
            redirect('index.php?page=register');
        }
        $users = new User();
        if ($users->findByEmail($email)) {
            flash('error', 'Cette adresse e-mail est déjà utilisée.');
            redirect('index.php?page=register');
        }
        $users->create($name, $email, $password);
        flash('success', 'Compte créé. Connecte-toi pour confirmer ton inscription à l’atelier.');
        redirect('index.php?page=login');
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect(APP_AREA === 'backoffice' ? 'index.php?page=login' : 'index.php?page=ateliers');
    }
}
