<?php
/*
 * Modifier une récolte (seulement si son terrain appartient à l'utilisateur)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/CultureController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$recolteC      = new RecolteController();

$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$recolte = $id ? $recolteC->getRecolteOfUser($id, $idUtilisateur) : false;
if (!$recolte) {
    setFlash(t("Cette récolte n'existe pas ou ne vous appartient pas."), 'danger');
    header('Location: mesRecoltes.php');
    exit;
}

$terrains = (new TerrainController())->listTerrainsByUser($idUtilisateur);
require __DIR__ . '/partials/traitementRecolte.php';

$nomsCultures  = (new CultureController())->nomsPourAutocompletion();
$texteBouton   = 'Enregistrer';
$photoActuelle = $recolte['photo'];
$titrePage     = 'Modifier la récolte';
$pageActive    = 'mesRecoltes.php';
$jsSupp        = ['assets/js/validation-recolte.js', 'assets/js/saisie-vocale.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">
        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= e($recolte['culture']) ?> · <?= e($recolte['terrainNom']) ?></p>
            <h1><?= te('Modifier la récolte') ?></h1>
          </div>
          <a href="mesRecoltes.php" class="btn-hero"><i class="bi bi-arrow-left"></i> <?= te('Retour') ?></a>
        </div>
        <div class="carte-terrain tb-formulaire">
          <?php require __DIR__ . '/partials/formRecolte.php'; ?>
        </div>
      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
