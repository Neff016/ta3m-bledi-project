<?php
// Front controller routing: index.php?page=<key>
require_once __DIR__ . '/Controller/VilleController.php';
require_once __DIR__ . '/Controller/AuthController.php';
require_once __DIR__ . '/Controller/DashboardController.php';
require_once __DIR__ . '/Controller/RegionsController.php';
require_once __DIR__ . '/Controller/ProjectionsController.php';
require_once __DIR__ . '/Controller/UsersController.php';

$page = $_GET['page'] ?? 'tounes';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ---------- Back office: page => [Controller, method] ----------
$adminRoutes = [
    'admin-login'             => ['AuthController', 'login'],
    'admin-logout'            => ['AuthController', 'logout'],
    'admin-dashboard'         => ['DashboardController', 'index'],
    'admin-reset'             => ['DashboardController', 'reset'],
    'admin-users'             => ['UsersController', 'index'],
    'admin-user-form'         => ['UsersController', 'form'],
    'admin-user-save'         => ['UsersController', 'save'],
    'admin-user-toggle'       => ['UsersController', 'toggle'],
    'admin-user-delete'       => ['UsersController', 'delete'],
    'admin-regions'           => ['RegionsController', 'index'],
    'admin-region-form'       => ['RegionsController', 'form'],
    'admin-region-save'       => ['RegionsController', 'save'],
    'admin-region-delete'     => ['RegionsController', 'delete'],
    'admin-projections'       => ['ProjectionsController', 'index'],
    'admin-projection-form'   => ['ProjectionsController', 'form'],
    'admin-projection-save'   => ['ProjectionsController', 'save'],
    'admin-projection-delete' => ['ProjectionsController', 'delete'],
];

if (isset($adminRoutes[$page])) {
    [$class, $method] = $adminRoutes[$page];
    (new $class())->$method();
    return;
}

// ---------- Front office ----------
$controller = new VilleController();

switch ($page) {
    case 'tounes':
        $id ? $controller->show($id) : $controller->index();
        break;
    case 'cultures':
        $controller->cultures();
        break;
    case 'explication':
        $controller->explication();
        break;
    default:
        $controller->notFound();
}