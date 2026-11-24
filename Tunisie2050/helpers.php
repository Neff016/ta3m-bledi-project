<?php
// Escape text before printing it in HTML
function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Same thing, short name used by the back-office views
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Build a URL: url(['page' => 'tounes', 'id' => 2])
function url(array $params = []): string
{
    return BASE_URL . 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

// Back-office URL: admin_url(['page' => 'regions', 'id' => 2]) => index.php?page=admin-regions&id=2
function admin_url(array $params = []): string
{
    if (isset($params['page'])) {
        $params['page'] = 'admin-' . $params['page'];
    }
    return url($params);
}

// Allowed values of Projection.stressHydrique (used by the admin forms)
const STRESS = ['Faible', 'Modéré', 'Élevé', 'Critique'];

// ---------- CSRF protection ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Hidden input to put in every POST form
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

// +2,1 / −5
function signed(float $v, int $dec = 1): string
{
    return ($v >= 0 ? '+' : '−') . number_format(abs($v), $dec, ',', ' ');
}

// URL of a region photo, or null if the file doesn't exist
function regionImage(?string $file): ?string
{
    $file = basename((string)$file);
    if ($file === '' || !is_file(FO_PATH . 'assets/img/regions/' . $file)) {
        return null;
    }
    return FO_URL . 'assets/img/regions/' . rawurlencode($file);
}
