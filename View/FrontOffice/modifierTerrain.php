<?php
/*
 * Modifier un terrain (seulement s'il appartient à l'utilisateur)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$terrainC      = new TerrainController();

// L'id du terrain arrive dans l'URL : modifierTerrain.php?id=3
$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$terrain = $id ? $terrainC->getTerrainOfUser($id, $idUtilisateur) : false;

// Terrain introuvable OU appartient à quelqu'un d'autre → retour à la liste
if (!$terrain) {
    setFlash(t("Ce terrain n'existe pas ou ne vous appartient pas."), 'danger');
    header('Location: mesTerrains.php');
    exit;
}
require __DIR__ . '/partials/traitementTerrain.php';

$texteBouton   = 'Enregistrer';
$photoActuelle = $terrain['photo'];
$titrePage     = 'Modifier le terrain';
$pageActive    = 'mesTerrains.php';
$avecCarte     = true;
$jsSupp        = ['assets/js/carte-saisie.js', 'assets/js/validation-terrain.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">
        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= e($terrain['nom']) ?></p>
            <h1><?= te('Modifier le terrain') ?></h1>
          </div>
          <a href="detailsTerrain.php?id=<?= e($terrain['id']) ?>" class="btn-hero"><i class="bi bi-arrow-left"></i> <?= te('Retour') ?></a>
        </div>
        <div class="carte-terrain tb-formulaire">
          <?php require __DIR__ . '/partials/formTerrain.php'; ?>
        </div>
      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
