<?php
/*
 * Action : déconnexion (Front et Back Office)
 * Vide la session puis renvoie vers la page de connexion.
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/UtilisateurController.php';

// Le jeton de session est effacé de la base
if (!empty($_SESSION['user_id'])) {
    (new UtilisateurController())->fermerSession((int) $_SESSION['user_id'], (string) ($_SESSION['session_token'] ?? ''));
}

$_SESSION = [];
session_regenerate_id(true);
setFlash('Vous êtes déconnecté.');

header('Location: connexion.php');
exit;
