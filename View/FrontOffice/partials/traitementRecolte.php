<?php
/*
 * Traitement commun du formulaire Récolte (ajout ET modification)
 * Variables attendues : $recolteC, $idUtilisateur, $recolte (ligne existante ou null pour un ajout)
 * Remplit $valeurs, $erreurs, $avertissement ; en cas de succès : enregistre et redirige.
 */
require_once __DIR__ . '/../../../Controller/UploadHelper.php';
require_once __DIR__ . '/../../../Controller/BadgeAutoController.php';

$valeurs = [
    'idTerrain'      => $recolte['idTerrain'] ?? ($_GET['idTerrain'] ?? ''),   // pré-sélection depuis "Détails terrain"
    'culture'        => $recolte['culture'] ?? mb_substr(trim((string) ($_GET['culture'] ?? '')), 0, 100),
    'datePlantation' => $recolte['datePlantation'] ?? '',
    'dateRecolte'    => $recolte['dateRecolte'] ?? '',
    'quantite'       => isset($recolte['quantite']) ? RecolteController::nombreCourt((float) $recolte['quantite']) : '',
    'unite'          => $recolte['unite'] ?? 'kg',
];
$erreurs       = [];
$avertissement = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valeurs = [
        'idTerrain'      => $_POST['idTerrain'] ?? '',
        'culture'        => trim($_POST['culture'] ?? ''),
        'datePlantation' => $_POST['datePlantation'] ?? '',
        'dateRecolte'    => $_POST['dateRecolte'] ?? '',
        'quantite'       => trim($_POST['quantite'] ?? ''),
        'unite'          => $_POST['unite'] ?? '',
    ];

    // Vérifie aussi que le terrain choisi appartient à l'utilisateur
    $erreurs = $recolteC->validateRecolte($valeurs, $idUtilisateur);

    // Rotation des cultures : simple avertissement, l'utilisateur peut confirmer
    $cultureChangee = !$recolte || RecolteController::normaliser($recolte['culture']) !== RecolteController::normaliser($valeurs['culture'])
                      || (int) $recolte['idTerrain'] !== (int) $valeurs['idTerrain'];
    if (empty($erreurs) && $cultureChangee && empty($_POST['confirmerRotation'])) {
        $avertissement = $recolteC->avertissementRotation((int) $valeurs['idTerrain'], $valeurs['culture'],
                                                          $valeurs['datePlantation'], $recolte ? (int) $recolte['id'] : null);
    }

    // Photo (facultative)
    $nouvellePhoto = null;
    if (empty($erreurs) && !$avertissement && fichierEnvoye($_FILES['photo'] ?? null)) {
        $upload = uploadImage($_FILES['photo'], 'recoltes');
        if ($upload['ok']) {
            $nouvellePhoto = $upload['fichier'];
        } else {
            $erreurs['photo'] = $upload['erreur'];
        }
    }

    if (empty($erreurs) && !$avertissement) {
        $objet = new Recolte(
            $valeurs['culture'],
            $valeurs['datePlantation'],
            $valeurs['dateRecolte'],
            (float) str_replace(',', '.', $valeurs['quantite']),
            $valeurs['unite'],
            (int) $valeurs['idTerrain'],
            $recolte['id'] ?? null
        );
        if ($recolte) {
            $recolteC->updateRecolte($objet);
            $id = (int) $recolte['id'];
        } else {
            $id = $recolteC->addRecolte($objet);
        }

        if ($nouvellePhoto) {
            supprimerImage($recolte['photo'] ?? null, 'recoltes');
            $recolteC->updatePhoto($id, $nouvellePhoto);
        } elseif ($recolte && !empty($_POST['supprimerPhoto'])) {
            supprimerImage($recolte['photo'] ?? null, 'recoltes');
            $recolteC->updatePhoto($id, null);
        }

        $badges = (new BadgeAutoController())->verifier($idUtilisateur);
        setFlash(t($recolte ? 'Récolte modifiée avec succès.' : 'Récolte ajoutée avec succès.') . BadgeAutoController::message($badges));
        header('Location: mesRecoltes.php');
        exit;
    }
}
