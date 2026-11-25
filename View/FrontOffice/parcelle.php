<?php
/*
 * Ajouter ou modifier une parcelle d'un terrain
 *   parcelle.php?terrain=3        → ajout
 *   parcelle.php?terrain=3&id=7   → modification
 * Le contour se dessine sur la carte ; la surface est alors calculée (et recalculée par le serveur).
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/ParcelleController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$parcelleC     = new ParcelleController();

$idTerrain = filter_input(INPUT_GET, 'terrain', FILTER_VALIDATE_INT);
$terrain   = $idTerrain ? (new TerrainController())->getTerrainOfUser($idTerrain, $idUtilisateur) : false;
if (!$terrain) {
    setFlash(t("Ce terrain n'existe pas ou ne vous appartient pas."), 'danger');
    header('Location: mesTerrains.php');
    exit;
}

$id       = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$parcelle = $id ? $parcelleC->getParcelleOfUser($id, $idUtilisateur) : null;
if ($id && (!$parcelle || (int) $parcelle['idTerrain'] !== (int) $terrain['id'])) {
    setFlash(t('Parcelle introuvable.'), 'danger');
    header('Location: detailsTerrain.php?id=' . $terrain['id'] . '&onglet=parcelles');
    exit;
}

$valeurs = ['nom' => $parcelle['nom'] ?? '', 'surface' => isset($parcelle['surface']) ? RecolteController::nombreCourt((float) $parcelle['surface']) : '',
            'polygone' => $parcelle['polygone'] ?? ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valeurs = ['nom' => trim($_POST['nom'] ?? ''), 'surface' => trim($_POST['surface'] ?? ''), 'polygone' => trim($_POST['polygone'] ?? '')];
    $erreurs = $parcelleC->valider($valeurs, $terrain, $parcelle ? (int) $parcelle['id'] : null);
    if (empty($erreurs)) {
        $objet = $parcelleC->versObjet($valeurs, (int) $terrain['id'], $parcelle ? (int) $parcelle['id'] : null);
        if ($parcelle) {
            $parcelleC->modifier($objet);
        } else {
            $parcelleC->ajouter($objet);
        }
        setFlash(t($parcelle ? 'Parcelle modifiée.' : 'Parcelle ajoutée.'));
        header('Location: detailsTerrain.php?id=' . $terrain['id'] . '&onglet=parcelles');
        exit;
    }
}

// Sur la carte : le contour du terrain et les autres parcelles, pour se repérer
[$lat, $lng] = TerrainController::position($terrain);
$repere = [];
foreach ($parcelleC->listParTerrain((int) $terrain['id']) as $p) {
    if ((!$parcelle || (int) $p['id'] !== (int) $parcelle['id']) && ($poly = TerrainController::lirePolygone($p['polygone']))) {
        $repere[] = ['nom' => $p['nom'], 'polygone' => $poly];
    }
}
$disponible = max(0, (float) $terrain['superficie'] - $parcelleC->surfaceUtilisee((int) $terrain['id'], $parcelle ? (int) $parcelle['id'] : null));

$titrePage  = $parcelle ? 'Modifier la parcelle' : 'Ajouter une parcelle';
$pageActive = 'mesTerrains.php';
$avecCarte  = true;
$jsSupp     = ['assets/js/carte-saisie.js', 'assets/js/carte-parcelle.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">
        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= e($terrain['nom']) ?> · <?= e(CultureController::nombre((float) $terrain['superficie'], 2)) ?> ha</p>
            <h1><?= te($titrePage) ?></h1>
          </div>
          <a href="detailsTerrain.php?id=<?= e($terrain['id']) ?>&amp;onglet=parcelles" class="btn-hero"><i class="bi bi-arrow-left"></i> <?= te('Retour') ?></a>
        </div>

        <div class="carte-terrain tb-formulaire">
          <form action="" method="POST" id="formParcelle" novalidate data-disponible="<?= e(round($disponible, 2)) ?>">
            <?= champCsrf() ?>
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="nom" class="form-label"><?= te('Nom de la parcelle') ?></label>
                <input type="text" class="form-control <?= isset($erreurs['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" value="<?= e($valeurs['nom']) ?>" placeholder="<?= te('Ex : Parcelle Nord') ?>">
                <div class="erreur" id="erreur-nom"><?= te($erreurs['nom'] ?? '') ?></div>
              </div>
              <div class="col-md-6 mb-4">
                <label for="superficie" class="form-label"><?= te('Surface (ha)') ?></label>
                <input type="text" class="form-control <?= isset($erreurs['surface']) ? 'is-invalid' : '' ?>" id="superficie" name="surface" value="<?= e($valeurs['surface']) ?>" placeholder="1.2">
                <small class="text-muted"><?= te('Encore {d} ha disponibles sur ce terrain. Calculée automatiquement si vous dessinez le contour.', ['d' => CultureController::nombre($disponible, 2)]) ?></small>
                <div class="erreur" id="erreur-superficie"><?= te($erreurs['surface'] ?? '', $erreurs['surfaceValeurs'] ?? []) ?></div>
              </div>

              <div class="col-12 mb-4">
                <label class="form-label"><?= te('Contour de la parcelle (facultatif)') ?></label>
                <div class="tb-outils-carte">
                  <button type="button" class="tb-outil" id="btnDessiner"><i class="bi bi-pentagon"></i> <?= te('Dessiner le contour') ?></button>
                  <button type="button" class="tb-outil" id="btnTerminerParcelle" hidden><i class="bi bi-check2"></i> <?= te('Terminer la parcelle') ?></button>
                  <button type="button" class="tb-outil danger" id="btnEffacerCarte"><i class="bi bi-eraser"></i> <?= te('Effacer') ?></button>
                </div>
                <div id="carteParcelle" class="tb-carte tb-carte-saisie"
                     data-centre='<?= e(json_encode([$lat, $lng])) ?>'
                     data-terrain='<?= e(json_encode(TerrainController::lirePolygone($terrain['polygone']))) ?>'
                     data-reperes='<?= e(json_encode($repere)) ?>'
                     data-texte-aide="<?= te('Cliquez sur chaque coin de la parcelle, puis sur « Terminer la parcelle ».') ?>"
                     data-texte-surface="<?= te('Surface calculée : {ha} ha') ?>"></div>
                <p id="aideCarte" class="tb-aide-carte"><i class="bi bi-info-circle"></i> <?= te('Le contour du terrain est en pointillés, les autres parcelles en gris.') ?></p>
                <input type="hidden" id="polygone" name="polygone" value="<?= e($valeurs['polygone']) ?>">
                <div class="erreur" id="erreur-polygone"><?= te($erreurs['polygone'] ?? '') ?></div>
              </div>
            </div>
            <div class="pied">
              <a href="detailsTerrain.php?id=<?= e($terrain['id']) ?>&amp;onglet=parcelles" class="lien-details"><?= te('Annuler') ?></a>
              <button type="submit" class="btn-hero border-0"><?= te($parcelle ? 'Enregistrer' : 'Ajouter la parcelle') ?></button>
            </div>
          </form>
        </div>
      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
