<?php
/*
 * Changer la langue : langue.php?l=ar&retour=mesTerrains.php
 */
require_once __DIR__ . '/../../Controller/Langue.php';

$l = $_GET['l'] ?? 'fr';
if (isset(LANGUES[$l])) {
    langue();                                            // démarre la session
    $_SESSION['langue'] = $l;
    setcookie('langue', $l, ['expires' => time() + 30 * 86400, 'path' => '/', 'samesite' => 'Lax', 'httponly' => true]);
}

// Retour seulement vers une page du site (jamais vers un autre site)
$retour = (string) ($_GET['retour'] ?? 'tableauDeBord.php');
if (!preg_match('/^[A-Za-z0-9_]+\.php(\?[A-Za-z0-9_=&%.-]*)?$/', $retour)) {
    $retour = 'tableauDeBord.php';
}
header('Location: ' . $retour);
exit;
