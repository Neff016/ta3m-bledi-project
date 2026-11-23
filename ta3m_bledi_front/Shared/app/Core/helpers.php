<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session expirée. Recharge la page et réessaie.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return is_logged_in() && (current_user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Connecte-toi pour effectuer cette action.');
        redirect('index.php?page=login');
    }
}

function require_admin(): void
{
    if (!is_logged_in()) {
        flash('error', 'Connecte-toi avec le compte administrateur.');
        redirect('index.php?page=login');
    }
    if (!is_admin()) {
        http_response_code(403);
        exit('403 — Accès réservé à l’administrateur.');
    }
}

function require_user(): void
{
    if (!is_logged_in()) {
        flash('error', 'Connecte-toi pour accéder à tes inscriptions.');
        redirect('index.php?page=login');
    }
    if (is_admin()) {
        http_response_code(403);
        exit('403 — Cette action est réservée aux comptes utilisateurs.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}
