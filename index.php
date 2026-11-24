<?php
declare(strict_types=1);

define('APP_ENTRY', true);

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$sessionLifetime = 1800;
if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $sessionLifetime) {
    $_SESSION = [];
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$baseUrl = rtrim($scriptDirectory, '/');
define('APP_BASE_URL', ($baseUrl === '' ? '' : $baseUrl) . '/');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com");

require_once __DIR__ . '/Controller/AuthGuard.php';
require_once __DIR__ . '/Controller/PlantationController.php';
require_once __DIR__ . '/Controller/SuiviController.php';

$action = (string) ($_GET['action'] ?? 'home');

switch ($action) {
    case 'dashboard':
        $userId = requireLogin();
        (new PlantationController())->dashboard($userId);
        break;

    case 'plantations':
        $userId = requireLogin();
        (new PlantationController())->index($userId);
        break;

    case 'plantation_show':
        $userId = requireLogin();
        (new PlantationController())->show((int) ($_GET['id'] ?? 0), $userId);
        break;

    case 'suivis':
        $userId = requireLogin();
        (new SuiviController())->index($userId);
        break;

    case 'plantation_create':
        $userId = requireLogin();
        (new PlantationController())->form(null, $userId);
        break;

    case 'plantation_update':
        $userId = requireLogin();
        (new PlantationController())->form((int) ($_GET['id'] ?? 0), $userId);
        break;

    case 'plantation_delete':
        $userId = requireLogin();
        (new PlantationController())->delete($userId);
        break;

    case 'suivi_create':
        $userId = requireLogin();
        (new SuiviController())->form(null, $userId);
        break;

    case 'suivi_update':
        $userId = requireLogin();
        (new SuiviController())->form((int) ($_GET['id'] ?? 0), $userId);
        break;

    case 'suivi_delete':
        $userId = requireLogin();
        (new SuiviController())->delete($userId);
        break;

    case 'admin':
        requireAdmin();
        header('Location: ' . APP_BASE_URL . 'index.php?action=admin_plantations');
        exit;

    case 'admin_plantations':
        requireAdmin();
        (new PlantationController())->adminIndex();
        break;

    case 'admin_plantation_show':
        requireAdmin();
        (new PlantationController())->adminShow((int) ($_GET['id'] ?? 0));
        break;

    case 'admin_plantation_delete':
        requireAdmin();
        (new PlantationController())->adminDeletePlantation();
        break;

    case 'admin_suivis':
        requireAdmin();
        (new SuiviController())->adminIndex();
        break;

    case 'admin_suivi_delete':
        requireAdmin();
        (new SuiviController())->adminDelete();
        break;

    case 'home':
        header('Location: ' . APP_BASE_URL . 'index.php?action=dashboard');
        exit;

    default:
        http_response_code(404);
        echo '404 - Page not found';
}