<?php
/*
 * Action : déconnexion (Front et Back Office)
 * Vide la session puis renvoie vers la page de connexion.
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';

$_SESSION = [];
session_regenerate_id(true);
setFlash('Vous êtes déconnecté.');

header('Location: connexion.php');
exit;
