<?php
// Set APP_DEBUG=1 in the environment to display errors while developing
$debug = getenv('APP_DEBUG') === '1';
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Session hardening: cookie not readable by JS, not sent cross-site, ids only accepted if issued by us
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
// OpenStreetMap tiles are refused (403) when no referer is sent, so keep the origin for cross-site requests
header('Referrer-Policy: strict-origin-when-cross-origin');

// Works wherever the folder is located
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');
define('FO_URL', BASE_URL . 'View/FrontOffice/');
define('FO_PATH', __DIR__ . '/View/FrontOffice/');
define('BO_URL', BASE_URL . 'View/BackOffice/');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/routes.php';
