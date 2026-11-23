<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Paramètres de connexion à la base MySQL (XAMPP / Laragon / WAMP par défaut)
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'taam_bledi';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// 2. Fonctions utilitaires simples pour les vues et contrôleurs

// Protège les textes affichés en HTML contre les failles XSS
function e(?string $text): string
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// Redirige vers une page MVC
function redirect(string $page): void
{
    header("Location: index.php?page=$page");
    exit;
}

// Vérifie si l'utilisateur connecté est Administrateur
function isAdmin(): bool
{
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

// Vérifie que l'utilisateur est connecté en Admin ou Agriculteur
function requireBackendAuth(): void
{
    $role = $_SESSION['user']['role'] ?? '';
    if ($role !== 'admin' && $role !== 'farmer') {
        redirect('login');
    }
}

// Protection simple CSRF pour les formulaires POST
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        die('Jeton de sécurité invalide. Rechargez la page.');
    }
}
