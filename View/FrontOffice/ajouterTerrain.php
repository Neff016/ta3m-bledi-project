<?php
/*
 * Ajouter un terrain (avec carte, parcelle dessinée et photo)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$terrainC      = new TerrainController();
$terrain       = null;                         // ajout
require __DIR__ . '/partials/traitementTerrain.php';

$texteBouton   = 'Ajouter le terrain';
$photoActuelle = null;
$titrePage     = 'Ajouter un terrain';
$pageActive    = 'mesTerrains.php';
$avecCarte     = true;
$jsSupp        = ['assets/js/carte-saisie.js', 'assets/js/validation-terrain.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">
        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= te('Nouveau terrain') ?></p>
            <h1><?= te('Ajouter un terrain') ?></h1>
          </div>
          <a href="mesTerrains.php" class="btn-hero"><i class="bi bi-arrow-left"></i> <?= te('Retour') ?></a>
        </div>
        <div class="carte-terrain tb-formulaire">
          <?php require __DIR__ . '/partials/formTerrain.php'; ?>
        </div>
      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
