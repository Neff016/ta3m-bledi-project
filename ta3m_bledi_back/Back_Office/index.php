<?php
declare(strict_types=1);
// Session distincte : seul le Back Office utilise la session administrateur.
session_name('TA3M_ADMIN_SESSION');
session_start();
define('APP_AREA', 'backoffice');
define('APP_BASE', '../Shared/');
define('ASSET_BASE', '../public/');
// URLs absolues des deux espaces (dossiers frères dans htdocs).
$projectsRootUrl = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))), '/\\');
if ($projectsRootUrl === '.') { $projectsRootUrl = ''; }
if (!defined('FRONT_OFFICE_URL')) define('FRONT_OFFICE_URL', $projectsRootUrl . '/ta3m_bledi_front/Front_Office/index.php');
if (!defined('BACK_OFFICE_URL')) define('BACK_OFFICE_URL', $projectsRootUrl . '/ta3m_bledi_back/Back_Office/index.php');
require_once __DIR__ . '/../Shared/app/bootstrap.php';

$routes = [
    'GET login' => [AuthController::class, 'loginForm'],
    'POST login' => [AuthController::class, 'login'],
    'GET logout' => [AuthController::class, 'logout'],
    'GET admin' => [AdminController::class, 'dashboard'],
    'GET admin-ateliers' => [AdminController::class, 'workshops'],
    'POST admin-atelier-save' => [AdminController::class, 'saveWorkshop'],
    'POST admin-atelier-delete' => [AdminController::class, 'deleteWorkshop'],
    'GET admin-inscriptions' => [AdminController::class, 'registrations'],
    'POST admin-inscription-delete' => [AdminController::class, 'deleteRegistration'],
];
$key = ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' ' . ($_GET['page'] ?? 'admin');
if (!isset($routes[$key])) {
    http_response_code(404);
    (new WorkshopController())->notFound();
    exit;
}
[$class, $action] = $routes[$key];
$controller = new $class();
$controller->$action();
