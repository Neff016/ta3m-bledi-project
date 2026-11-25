<?php
/*
 * Contrôle qualité d'une récolte (ajout, modification, suppression)
 *   qualiteRecolte.php?id=12
 * La classe et les déchets changent le chiffre d'affaires estimé ; le résultat apparaît sur la page du QR code.
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/CultureController.php';
require_once __DIR__ . '/../../Controller/ControleQualiteController.php';
require_once __DIR__ . '/../../Controller/CertificationBioController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$qualiteC      = new ControleQualiteController();

$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$recolte = $id ? (new RecolteController())->getRecolteOfUser($id, $idUtilisateur) : false;
if (!$recolte) {
    setFlash(t("Cette récolte n'existe pas ou ne vous appartient pas."), 'danger');
    header('Location: mesRecoltes.php');
    exit;
}
$culture = (new CultureController())->trouver($recolte['culture']);
$mesure  = ControleQualiteController::mesure($culture);
$qualite = $qualiteC->getParRecolte($id);

$valeurs = $qualite ? [
    'classe' => $qualite['classe'], 'calibre' => $qualite['calibre'] ?? '',
    'tauxDechets' => ControleQualiteController::court($qualite['tauxDechets']),
    'humidite' => $qualite['humidite'] !== null ? ControleQualiteController::court($qualite['humidite']) : '',
    'brix' => $qualite['brix'] !== null ? ControleQualiteController::court($qualite['brix']) : '',
    'dateControle' => $qualite['dateControle'], 'remarque' => $qualite['remarque'] ?? '',
] : ['classe' => 'I', 'calibre' => '', 'tauxDechets' => '', 'humidite' => '', 'brix' => '',
     'dateControle' => min(date('Y-m-d'), $recolte['dateRecolte']), 'remarque' => ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'supprimer') {
        $ok = $qualiteC->supprimer($id);
        setFlash(t($ok ? 'Contrôle qualité supprimé.' : 'Aucun contrôle à supprimer.'), $ok ? 'success' : 'danger');
        header('Location: mesRecoltes.php');
        exit;
    }
    $valeurs = [
        'classe' => $_POST['classe'] ?? '', 'calibre' => trim($_POST['calibre'] ?? ''),
        'tauxDechets' => trim($_POST['tauxDechets'] ?? ''), 'humidite' => trim($_POST['humidite'] ?? ''),
        'brix' => trim($_POST['brix'] ?? ''), 'dateControle' => $_POST['dateControle'] ?? '',
        'remarque' => trim($_POST['remarque'] ?? ''),
    ];
    $erreurs = $qualiteC->valider($valeurs, $recolte, $mesure);
    if (empty($erreurs)) {
        $qualiteC->enregistrer($qualiteC->versObjet($valeurs, $id, $mesure));
        $msg = t('Contrôle qualité enregistré.');
        if ($mesure === 'humidite' && (float) str_replace(',', '.', $valeurs['humidite']) > ControleQualiteController::HUMIDITE_MAX) {
            $msg .= ' ' . t('Attention : grain trop humide (plus de 14,5 %), séchez-le avant le stockage.');
        }
        setFlash($msg, 'success');
        header('Location: mesRecoltes.php');
        exit;
    }
}

// Aperçu de l'effet sur le prix
$certif   = (new CertificationBioController())->getParTerrain((int) $recolte['idTerrain']);
$bio      = CertificationBioController::statutRecolte($certif, $recolte['dateRecolte']) === 'CERTIFIE';
$prixKg   = $culture ? (float) $culture['prixKg'] : null;

$titrePage  = 'Contrôle qualité';
$pageActive = 'mesRecoltes.php';
$jsSupp     = ['assets/js/validation-qualite.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">
        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= e($recolte['culture']) ?> · <?= e($recolte['terrainNom']) ?> · <?= e(date('d/m/Y', strtotime($recolte['dateRecolte']))) ?></p>
            <h1><?= te('Contrôle qualité') ?></h1>
          </div>
          <a href="mesRecoltes.php" class="btn-hero"><i class="bi bi-arrow-left"></i> <?= te('Retour') ?></a>
        </div>

        <div class="row g-4">
          <div class="col-lg-8">
            <div class="carte-terrain tb-formulaire">
              <form action="" method="POST" id="formQualite" novalidate
                    data-plantation="<?= e($recolte['datePlantation']) ?>" data-mesure="<?= e($mesure ?? '') ?>"
                    data-quantite="<?= e((float) $recolte['quantiteKg']) ?>" data-prix="<?= e($prixKg ?? '') ?>"
                    data-bio="<?= $bio ? '1' : '0' ?>" data-coefs='<?= e(json_encode(ControleQualiteController::COEFFICIENTS)) ?>'>
                <?= champCsrf() ?>
                <div class="row">
                  <div class="col-md-6 mb-4">
                    <label class="form-label"><?= te('Classe') ?></label>
                    <div class="tb-choix-classe" role="radiogroup" aria-label="<?= te('Classe') ?>">
                      <?php foreach (ControleQualiteController::CLASSES as $code => $libelle): ?>
                        <input type="radio" class="btn-check" name="classe" id="classe-<?= e($code) ?>" value="<?= e($code) ?>" <?= $valeurs['classe'] === $code ? 'checked' : '' ?>>
                        <label class="tb-classe tb-classe-<?= strtolower(e($code)) ?>" for="classe-<?= e($code) ?>"><?= te($libelle) ?>
                          <small class="d-block"><?= e(sprintf('%+d %%', round((ControleQualiteController::COEFFICIENTS[$code] - 1) * 100))) ?></small></label>
                      <?php endforeach; ?>
                    </div>
                    <div class="erreur" id="erreur-classe"><?= te($erreurs['classe'] ?? '') ?></div>
                  </div>
                  <div class="col-md-3 mb-4">
                    <label for="tauxDechets" class="form-label"><?= te('Déchets (%)') ?></label>
                    <input type="text" class="form-control <?= isset($erreurs['tauxDechets']) ? 'is-invalid' : '' ?>" id="tauxDechets" name="tauxDechets" value="<?= e($valeurs['tauxDechets']) ?>" placeholder="2">
                    <div class="erreur" id="erreur-tauxDechets"><?= te($erreurs['tauxDechets'] ?? '') ?></div>
                  </div>
                  <div class="col-md-3 mb-4">
                    <label for="calibre" class="form-label"><?= te('Calibre (facultatif)') ?></label>
                    <input type="text" class="form-control <?= isset($erreurs['calibre']) ? 'is-invalid' : '' ?>" id="calibre" name="calibre" value="<?= e($valeurs['calibre']) ?>" placeholder="57-67 mm">
                    <div class="erreur" id="erreur-calibre"><?= te($erreurs['calibre'] ?? '') ?></div>
                  </div>

                  <?php if ($mesure === 'humidite'): ?>
                    <div class="col-md-6 mb-4">
                      <label for="humidite" class="form-label"><?= te('Humidité du grain (%)') ?></label>
                      <input type="text" class="form-control <?= isset($erreurs['humidite']) ? 'is-invalid' : '' ?>" id="humidite" name="humidite" value="<?= e($valeurs['humidite']) ?>" placeholder="12.5">
                      <small class="text-muted"><?= te('Au-dessus de 14,5 % le grain risque de moisir au stockage.') ?></small>
                      <div class="erreur" id="erreur-humidite"><?= te($erreurs['humidite'] ?? '') ?></div>
                    </div>
                  <?php elseif ($mesure === 'brix'): ?>
                    <div class="col-md-6 mb-4">
                      <label for="brix" class="form-label"><?= te('Taux de sucre (°Brix, facultatif)') ?></label>
                      <input type="text" class="form-control <?= isset($erreurs['brix']) ? 'is-invalid' : '' ?>" id="brix" name="brix" value="<?= e($valeurs['brix']) ?>" placeholder="11">
                      <small class="text-muted"><?= te('Mesuré avec un réfractomètre sur le jus du fruit.') ?></small>
                      <div class="erreur" id="erreur-brix"><?= te($erreurs['brix'] ?? '') ?></div>
                    </div>
                  <?php endif; ?>

                  <div class="col-md-6 mb-4">
                    <label for="dateControle" class="form-label"><?= te('Date du contrôle') ?></label>
                    <input type="date" class="form-control <?= isset($erreurs['dateControle']) ? 'is-invalid' : '' ?>" id="dateControle" name="dateControle" value="<?= e($valeurs['dateControle']) ?>">
                    <div class="erreur" id="erreur-dateControle"><?= te($erreurs['dateControle'] ?? '') ?></div>
                  </div>
                  <div class="col-12 mb-4">
                    <label for="remarque" class="form-label"><?= te('Remarque (facultatif)') ?></label>
                    <input type="text" class="form-control <?= isset($erreurs['remarque']) ? 'is-invalid' : '' ?>" id="remarque" name="remarque" value="<?= e($valeurs['remarque']) ?>" placeholder="<?= te('Ex : quelques fruits marqués par la grêle') ?>">
                    <div class="erreur" id="erreur-remarque"><?= te($erreurs['remarque'] ?? '') ?></div>
                  </div>
                </div>
                <div class="pied">
                  <a href="mesRecoltes.php" class="lien-details"><?= te('Annuler') ?></a>
                  <button type="submit" class="btn-hero border-0"><i class="bi bi-check-lg"></i> <?= te('Enregistrer le contrôle') ?></button>
                </div>
              </form>
              <?php if ($qualite): ?>
                <form action="" method="POST" class="mt-3" data-confirm="<?= te('Supprimer ce contrôle qualité ?') ?>">
                  <?= champCsrf() ?>
                  <input type="hidden" name="action" value="supprimer">
                  <button type="submit" class="btn btn-link text-danger p-0"><i class="bi bi-trash"></i> <?= te('Supprimer le contrôle') ?></button>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="tb-panneau">
              <h2 class="tb-titre-panneau"><i class="bi bi-cash-coin"></i> <?= te('Effet sur le prix') ?></h2>
              <?php if ($prixKg === null): ?>
                <p class="text-muted mb-0"><?= te('Cette culture n’est pas dans le catalogue : pas de prix du marché.') ?></p>
              <?php else: ?>
                <p class="mb-1"><?= te('Prix du marché : {p} DT/kg', ['p' => number_format($prixKg, 3, ',', ' ')]) ?></p>
                <?php if ($bio): ?><p class="mb-1 text-success"><i class="bi bi-flower1"></i> <?= te('Récolte bio : +25 %') ?></p><?php endif; ?>
                <p class="mb-1"><?= te('Quantité récoltée : {q} kg', ['q' => number_format((float) $recolte['quantiteKg'], 0, ',', ' ')]) ?></p>
                <div class="tb-apercu-prix">
                  <span><?= te('Chiffre d’affaires estimé') ?></span>
                  <strong id="apercuRevenu">—</strong>
                  <small id="apercuDetail" class="d-block text-muted"></small>
                </div>
              <?php endif; ?>
              <hr>
              <p class="small text-muted mb-0"><?= te('Classes des normes de commercialisation : Extra (qualité supérieure, sans défaut), I (bonne qualité, légers défauts), II (défauts acceptés). Les déchets ne sont pas vendus.') ?></p>
            </div>
          </div>
        </div>
      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
