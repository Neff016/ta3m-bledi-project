<?php
declare(strict_types=1);
// Session distincte : le Front Office ne récupère jamais la session Admin.
session_name('TA3M_FRONT_SESSION');
session_start();
define('APP_AREA', 'frontoffice');
define('APP_BASE', '../Shared/');
define('ASSET_BASE', '../public/');
// URLs absolues des deux espaces (dossiers frères dans htdocs).
$projectsRootUrl = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))), '/\\');
if ($projectsRootUrl === '.') { $projectsRootUrl = ''; }
if (!defined('FRONT_OFFICE_URL')) define('FRONT_OFFICE_URL', $projectsRootUrl . '/ta3m_bledi_front/Front_Office/index.php');
if (!defined('BACK_OFFICE_URL')) define('BACK_OFFICE_URL', $projectsRootUrl . '/ta3m_bledi_back/Back_Office/index.php');
require_once __DIR__ . '/../Shared/app/bootstrap.php';

$routes = [
    'GET ateliers' => [WorkshopController::class, 'index'],
    'GET login' => [AuthController::class, 'loginForm'],
    'POST login' => [AuthController::class, 'login'],
    'GET register' => [AuthController::class, 'registerForm'],
    'POST register' => [AuthController::class, 'register'],
    'GET logout' => [AuthController::class, 'logout'],
    'GET mes-inscriptions' => [RegistrationController::class, 'mine'],
    'POST inscription' => [RegistrationController::class, 'store'],
    'POST annuler-inscription' => [RegistrationController::class, 'cancel'],
];
$key = ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' ' . ($_GET['page'] ?? 'ateliers');
if (!isset($routes[$key])) {
    http_response_code(404);
    (new WorkshopController())->notFound();
    exit;
}
[$class, $action] = $routes[$key];
$controller = new $class();
$controller->$action();
