<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Model/Store.php';
require_once __DIR__ . '/../Model/Utilisateur.php';

// Base class of every back-office controller
abstract class AdminController extends Controller
{
    private const IDLE_TIMEOUT = 1800; // 30 min without activity => logged out

    protected ?array $user = null;

    // Only an active admin gets through. The account is re-read at every request,
    // so a blocked or demoted admin loses access immediately.
    protected function requireLogin(): void
    {
        $u = isset($_SESSION['user_id']) ? Utilisateur::findById((int)$_SESSION['user_id']) : null;
        $expired = isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > self::IDLE_TIMEOUT;

        if (!$u || $u['statut'] !== 'actif' || $u['role'] !== 'admin' || $expired) {
            $wasLogged = isset($_SESSION['user_id']);
            unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['last_activity']);
            if ($wasLogged) {
                $this->flash('Session expirée ou accès refusé. Reconnectez-vous.', 'danger');
            }
            $this->redirect('login');
        }
        $_SESSION['last_activity'] = time();
        $this->user = $u;
    }

    // POST + valid CSRF token, otherwise back to the list
    protected function requirePost(string $fallbackPage): void
    {
        if (!$this->isPost()) {
            $this->redirect($fallbackPage);
        }
        if (!csrf_valid()) {
            http_response_code(419);
            $this->flash('Formulaire expiré ou invalide, veuillez réessayer.', 'danger');
            $this->redirect($fallbackPage);
        }
    }

    protected function redirect(string $page, array $query = []): void
    {
        header('Location: ' . admin_url(['page' => $page] + $query));
        exit;
    }

    // Back to a form with the errors and what the user typed
    protected function back(string $page, int $id, array $errors, array $old): void
    {
        $_SESSION['errors'] = $errors;
        $_SESSION['old']    = $old;
        $this->redirect($page, $id ? ['id' => $id] : []);
    }

    protected function takeFormState(): array
    {
        $state = [$_SESSION['errors'] ?? [], $_SESSION['old'] ?? []];
        unset($_SESSION['errors'], $_SESSION['old']);
        return $state;
    }

    protected function flash(string $msg, string $type = 'success'): void
    {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    // Renders View/BackOffice/pages/<view>.php inside View/BackOffice/layout.php
    protected function renderAdmin(string $view, string $currentPage, string $title, array $data = []): void
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $this->render('BackOffice', 'pages/' . $view, $data + [
            'title'       => $title . ' – Tunisie2050 Admin',
            'currentPage' => $currentPage,
            'flash'       => $flash,
            'currentUser' => $this->user,
        ]);
    }

    protected function findRow(array $rows, string $field, int $id): ?array
    {
        foreach ($rows as $r) {
            if ((int)$r[$field] === $id) {
                return $r;
            }
        }
        return null;
    }
}
