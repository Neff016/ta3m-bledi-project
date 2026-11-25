<?php
/*
 * Action : supprimer une récolte (Front Office)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/UploadHelper.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mesRecoltes.php');
    exit;
}

$id       = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$recolteC = new RecolteController();
$recolte  = $id ? $recolteC->getRecolteOfUser($id, $idUtilisateur) : false;

// deleteRecolte vérifie aussi que la récolte est sur un terrain de l'utilisateur
if ($recolte && $recolteC->deleteRecolte($id, $idUtilisateur)) {
    supprimerImage($recolte['photo'], 'recoltes');
    setFlash(t('Récolte supprimée avec succès.'));
} else {
    setFlash(t('Impossible de supprimer cette récolte.'), 'danger');
}

// Retour : page "Détails terrain" si on venait de là, sinon "Mes récoltes"
// (on accepte seulement ces deux pages, pour éviter les redirections piégées)
$retour = $_POST['retour'] ?? '';
if (!preg_match('/^detailsTerrain\.php\?id=\d+$/', $retour)) {
    $retour = 'mesRecoltes.php';
}
header('Location: ' . $retour);
exit;
