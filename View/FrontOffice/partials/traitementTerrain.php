<?php
/*
 * Traitement commun du formulaire Terrain (ajout ET modification)
 * Variables attendues : $terrainC, $idUtilisateur, $terrain (ligne existante ou null pour un ajout)
 * Remplit $valeurs et $erreurs ; en cas de succès : enregistre et redirige.
 */
require_once __DIR__ . '/../../../Controller/UploadHelper.php';
require_once __DIR__ . '/../../../Controller/BadgeAutoController.php';

$valeurs = [
    'nom'          => $terrain['nom'] ?? '',
    'localisation' => $terrain['localisation'] ?? '',
    'superficie'   => $terrain['superficie'] ?? '',
    'typeSol'      => $terrain['typeSol'] ?? '',
    'latitude'     => $terrain['latitude'] ?? '',
    'longitude'    => $terrain['longitude'] ?? '',
    'polygone'     => $terrain['polygone'] ?? '',
];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valeurs = [
        'nom'          => trim($_POST['nom'] ?? ''),
        'localisation' => $_POST['localisation'] ?? '',
        'superficie'   => trim($_POST['superficie'] ?? ''),
        'typeSol'      => $_POST['typeSol'] ?? '',
        'latitude'     => trim($_POST['latitude'] ?? ''),
        'longitude'    => trim($_POST['longitude'] ?? ''),
        'polygone'     => trim($_POST['polygone'] ?? ''),
    ];
    $erreurs = $terrainC->validateTerrain($valeurs);

    // Parcelle dessinée : le serveur recalcule lui-même la surface et le centre (on ne fait pas confiance au navigateur)
    $points = $valeurs['polygone'] !== '' ? TerrainController::lirePolygone($valeurs['polygone']) : null;
    if ($points !== null && !isset($erreurs['position'])) {
        $surface = TerrainController::surfaceHectares($points);
        if ($surface <= 0) {
            $erreurs['position'] = 'La parcelle dessinée est trop petite.';
        } else {
            $valeurs['superficie'] = (string) $surface;
            [$valeurs['latitude'], $valeurs['longitude']] = TerrainController::centre($points);
            $valeurs['polygone'] = json_encode($points);
            unset($erreurs['superficie']);
        }
    }

    // Photo (facultative)
    $nouvellePhoto = null;
    if (empty($erreurs) && fichierEnvoye($_FILES['photo'] ?? null)) {
        $upload = uploadImage($_FILES['photo'], 'terrains');
        if ($upload['ok']) {
            $nouvellePhoto = $upload['fichier'];
        } else {
            $erreurs['photo'] = $upload['erreur'];
        }
    }

    if (empty($erreurs)) {
        $objet = new Terrain(
            $valeurs['nom'],
            $valeurs['localisation'],
            (float) str_replace(',', '.', $valeurs['superficie']),
            $valeurs['typeSol'],
            $idUtilisateur,
            $terrain['id'] ?? null,
            $valeurs['latitude'] !== '' ? (float) $valeurs['latitude'] : null,
            $valeurs['longitude'] !== '' ? (float) $valeurs['longitude'] : null,
            $points !== null ? $valeurs['polygone'] : null
        );

        if ($terrain) {
            $terrainC->updateTerrain($objet);
            $id = (int) $terrain['id'];
            // Parcelle modifiée : il faudra la renvoyer au service satellite
            if (($terrain['polygone'] ?? null) !== $objet->getPolygone()) {
                $terrainC->updateAgroPolygon($id, null);
            }
        } else {
            $id = $terrainC->addTerrain($objet);
        }

        // Photo : nouvelle, retirée ou inchangée
        if ($nouvellePhoto) {
            supprimerImage($terrain['photo'] ?? null, 'terrains');
            $terrainC->updatePhoto($id, $idUtilisateur, $nouvellePhoto);
        } elseif ($terrain && !empty($_POST['supprimerPhoto'])) {
            supprimerImage($terrain['photo'] ?? null, 'terrains');
            $terrainC->updatePhoto($id, $idUtilisateur, null);
        }

        $badges = (new BadgeAutoController())->verifier($idUtilisateur);
        setFlash(t($terrain ? 'Terrain modifié avec succès.' : 'Terrain ajouté avec succès.') . BadgeAutoController::message($badges));
        header('Location: detailsTerrain.php?id=' . $id);
        exit;
    }
}
