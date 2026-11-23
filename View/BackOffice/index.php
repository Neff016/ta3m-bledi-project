<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controller/AuthController.php';
require_once __DIR__ . '/controller/VarietyController.php';
require_once __DIR__ . '/controller/TestimonialController.php';

$page = $_GET['page'] ?? (isset($_SESSION['user']) ? 'dashboard' : 'login');
$action = $_GET['action'] ?? '';

$auth = new AuthController($pdo);
$variety = new VarietyController($pdo);
$testimonial = new TestimonialController($pdo);

// Routage simple des pages et actions
if ($page === 'login') {
    if ($action === 'login') $auth->login();
    elseif ($action === 'register') $auth->register();
    elseif ($action === 'setupAdmin') $auth->setupAdmin();
    else $auth->showLogin();
} elseif ($page === 'logout') {
    $auth->logout();
} elseif ($page === 'varieties') {
    if ($action === 'create') $variety->create();
    elseif ($action === 'update') $variety->update();
    elseif ($action === 'delete') $variety->delete();
    else $variety->list();
} elseif ($page === 'testimonials') {
    $testimonial->list();
} else {
    $variety->dashboard();
}
