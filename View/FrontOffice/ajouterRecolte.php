<?php
/*
 * Ajouter une récolte terminée (culture du catalogue / Musée, saisie vocale, photo)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/CultureController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$recolteC      = new RecolteController();
$terrains      = (new TerrainController())->listTerrainsByUser($idUtilisateur);  // pour le <select>
$recolte       = null;                                                           // ajout

if (empty($terrains)) {
    setFlash(t("Ajoutez d'abord un terrain avant d'enregistrer une récolte."), 'warning');
    header('Location: ajouterTerrain.php');
    exit;
}
require __DIR__ . '/partials/traitementRecolte.php';

$nomsCultures  = (new CultureController())->nomsPourAutocompletion();
$texteBouton   = 'Ajouter la récolte';
$photoActuelle = null;
$titrePage     = 'Ajouter une récolte';
$pageActive    = 'mesRecoltes.php';
$jsSupp        = ['assets/js/validation-recolte.js', 'assets/js/saisie-vocale.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">
        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= te('Nouvelle récolte') ?></p>
            <h1><?= te('Ajouter une récolte') ?></h1>
          </div>
          <a href="mesRecoltes.php" class="btn-hero"><i class="bi bi-arrow-left"></i> <?= te('Retour') ?></a>
        </div>
        <div class="carte-terrain tb-formulaire">
          <?php require __DIR__ . '/partials/formRecolte.php'; ?>
        </div>
      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
