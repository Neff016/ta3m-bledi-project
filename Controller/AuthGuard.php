<?php
/*
 * Contrôle d'accès + petits outils de sécurité
 * À inclure tout en haut de chaque page du module.
 *
 * ⚠️ À vérifier avec le membre qui fait la connexion (login) :
 *    - le nom des variables de session : $_SESSION['user_id'] et $_SESSION['role']
 *    - les valeurs du rôle : 'ADMIN' / 'AGRICULTEUR'
 *    - le chemin de la page de connexion (LOGIN_PAGE)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chemins (à adapter si le dossier du projet ou la page de connexion ont un autre nom)
const LOGIN_PAGE = '/ta3m-bledi-project/View/FrontOffice/connexion.php';
const HOME_PAGE  = '/ta3m-bledi-project/View/FrontOffice/index.html';

/**
 * Page réservée aux utilisateurs connectés.
 * Pas de session → redirection vers la connexion.
 * Retourne l'id de l'utilisateur connecté.
 */
function requireLogin(): int
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . LOGIN_PAGE);
        exit;
    }
    return (int) $_SESSION['user_id'];
}

/**
 * Page réservée à l'administrateur (Back Office).
 */
function requireAdmin(): int
{
    $id = requireLogin();
    if (($_SESSION['role'] ?? '') !== 'ADMIN') {
        header('Location: ' . HOME_PAGE);
        exit;
    }
    return $id;
}

/**
 * Affichage sécurisé (protection XSS) : à utiliser pour TOUT ce qui vient de la base ou de l'utilisateur.
 * Exemple : <?= e($terrain['nom']) ?>
 */
function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

/**
 * Message de confirmation affiché une seule fois (après ajout / modification / suppression).
 */
function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
