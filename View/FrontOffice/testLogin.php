<?php
/*
 * ⚠️ FICHIER DE TEST SEULEMENT : à SUPPRIMER avant la validation,
 *    quand la vraie page de connexion de l'équipe fonctionne.
 *
 * Il simule une connexion pour tester ton module sans attendre le module Utilisateurs :
 *   testLogin.php              → connecté comme Ahmed (agriculteur, id 2)
 *   testLogin.php?id=3         → connecté comme Salma (agriculteur, id 3)
 *   testLogin.php?role=ADMIN   → connecté comme admin (id 1) → Back Office
 *   testLogin.php?logout=1     → déconnexion
 */
session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    echo 'Déconnecté. <a href="mesTerrains.php">Essayer Mes terrains</a> (doit renvoyer vers la connexion)';
    exit;
}

if (($_GET['role'] ?? '') === 'ADMIN') {
    $_SESSION['user_id'] = 1;
    $_SESSION['role']    = 'ADMIN';
    header('Location: ../BackOffice/dashboard.php');
    exit;
}

$_SESSION['user_id'] = (int) ($_GET['id'] ?? 2);
$_SESSION['role']    = 'AGRICULTEUR';
header('Location: mesTerrains.php');
exit;
