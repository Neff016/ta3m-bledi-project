<?php
declare(strict_types=1);

define('APP_ENTRY', true);

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();

$sessionLifetime = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $sessionLifetime) {
    $_SESSION = [];
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'");

require_once __DIR__ . '/controllers/PlantationController.php';
require_once __DIR__ . '/controllers/SuiviController.php';

$action = $_GET['action'] ?? 'home';
switch ($action) {
    case 'test':
        http_response_code(404);
        echo '404 - Page not found';
        break;

    case 'dashboard':
        $plantationController = new PlantationController();
        $plantationSummary = $plantationController->summary();
        require __DIR__ . '/views/user_space.php';
        break;

    case 'plantations':
        $plantationController = new PlantationController();
        $plantationController->index();
        break;

    case 'plantation_show':
        $plantationController = new PlantationController();
        $plantationController->show((int) ($_GET['id'] ?? 0));
        break;

    case 'suivis':
        $suiviController = new SuiviController();
        $suiviController->index();
        break;

    case 'plantation_create':
    case 'plantation_update':
    case 'plantation_delete':
        $plantationController = new PlantationController();
        if ($action === 'plantation_create') {
            $plantationController->form(null);
        } elseif ($action === 'plantation_update') {
            $plantationController->form((int) ($_GET['id'] ?? 0));
        } else {
            $plantationController->delete();
        }
        break;

    case 'suivi_create':
    case 'suivi_update':
    case 'suivi_delete':
        $suiviController = new SuiviController();
        if ($action === 'suivi_create') {
            $suiviController->form(null);
        } elseif ($action === 'suivi_update') {
            $suiviController->form((int) ($_GET['id'] ?? 0));
        } else {
            $suiviController->delete();
        }
        break;

    case 'admin':
        header('Location: index.php?action=admin_plantations');
        exit;
        break;

    case 'admin_plantations':
    case 'admin_plantation_show':
    case 'admin_plantation_delete':
        $plantationController = new PlantationController();
        if ($action === 'admin_plantations') {
            $plantationController->adminIndex();
        } elseif ($action === 'admin_plantation_show') {
            $plantationController->adminShow((int) ($_GET['id'] ?? 0));
        } else {
            $plantationController->adminDeletePlantation();
        }
        break;

    case 'admin_suivis':
    case 'admin_suivi_delete':
        $suiviController = new SuiviController();
        if ($action === 'admin_suivis') {
            $suiviController->adminIndex();
        } else {
            $suiviController->adminDelete();
        }
        break;

    case 'home':
        header('Location: index.php?action=dashboard');
        exit;

    default:
        http_response_code(404);
        echo '404 - Page not found';
        break;
}
