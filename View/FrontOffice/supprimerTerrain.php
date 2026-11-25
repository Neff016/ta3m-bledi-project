<?php
/*
 * Action : supprimer un terrain (Front Office)
 * Pas de HTML ici : on supprime puis on redirige.
 * Les récoltes et dépenses sont supprimées par la base (ON DELETE CASCADE) ;
 * les photos sont effacées du disque ici.
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/UploadHelper.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();

// Suppression seulement via le bouton (POST), jamais en tapant l'URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mesTerrains.php');
    exit;
}

$id       = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$terrainC = new TerrainController();
$terrain  = $id ? $terrainC->getTerrainOfUser($id, $idUtilisateur) : false;

if ($terrain) {
    // Photos à effacer après la suppression
    $photos = [['terrains', $terrain['photo']]];
    foreach ((new RecolteController())->listRecoltesByTerrain($id) as $r) {
        $photos[] = ['recoltes', $r['photo']];
    }
    if ($terrainC->deleteTerrain($id, $idUtilisateur)) {
        foreach ($photos as [$dossier, $fichier]) {
            supprimerImage($fichier, $dossier);
        }
        setFlash(t('Terrain supprimé avec succès.'));
    }
} else {
    setFlash(t('Impossible de supprimer ce terrain.'), 'danger');
}
header('Location: mesTerrains.php');
exit;
