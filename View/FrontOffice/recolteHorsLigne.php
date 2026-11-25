<?php
/*
 * Réception d'une récolte enregistrée HORS LIGNE (envoyée par assets/js/hors-ligne.js au retour d'Internet)
 * Réponse JSON. Mêmes vérifications que le formulaire normal (connexion, jeton CSRF, contrôle de saisie).
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';     // vérifie aussi le jeton CSRF (POST)
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/BadgeAutoController.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erreur' => 'Méthode non autorisée.']);
    exit;
}
$idUtilisateur = requireLogin();
$recolteC      = new RecolteController();

$valeurs = [
    'idTerrain'      => $_POST['idTerrain'] ?? '',
    'culture'        => trim($_POST['culture'] ?? ''),
    'datePlantation' => $_POST['datePlantation'] ?? '',
    'dateRecolte'    => $_POST['dateRecolte'] ?? '',
    'quantite'       => trim($_POST['quantite'] ?? ''),
    'unite'          => $_POST['unite'] ?? '',
];
$erreurs = $recolteC->validateRecolte($valeurs, $idUtilisateur);
if (!empty($erreurs)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erreurs' => $erreurs]);
    exit;
}

$id = $recolteC->addRecolte(new Recolte(
    $valeurs['culture'],
    $valeurs['datePlantation'],
    $valeurs['dateRecolte'],
    (float) str_replace(',', '.', $valeurs['quantite']),
    $valeurs['unite'],
    (int) $valeurs['idTerrain']
));
$badges = (new BadgeAutoController())->verifier($idUtilisateur);
echo json_encode(['ok' => true, 'id' => $id, 'badges' => $badges]);
