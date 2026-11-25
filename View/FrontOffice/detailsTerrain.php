<?php
/*
 * Détails d'un terrain (seulement s'il appartient à l'utilisateur)
 * Onglets : Aperçu · Météo · Récoltes · Dépenses et rentabilité · Satellite
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/DepenseController.php';
require_once __DIR__ . '/../../Controller/AnalyseController.php';
require_once __DIR__ . '/../../Controller/MeteoController.php';
require_once __DIR__ . '/../../Controller/SatelliteController.php';
require_once __DIR__ . '/../../Controller/BadgeAutoController.php';
require_once __DIR__ . '/../../Controller/UploadHelper.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$terrainC      = new TerrainController();
$depenseC      = new DepenseController();

$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$terrain = $id ? $terrainC->getTerrainOfUser($id, $idUtilisateur) : false;
if (!$terrain) {
    setFlash(t("Ce terrain n'existe pas ou ne vous appartient pas."), 'danger');
    header('Location: mesTerrains.php');
    exit;
}

$ongletsValides = ['apercu', 'meteo', 'recoltes', 'depenses', 'satellite'];
$onglet = in_array($_GET['onglet'] ?? '', $ongletsValides, true) ? $_GET['onglet'] : 'apercu';
$erreursDepense = [];
$depense = ['categorie' => 'Semences', 'montant' => '', 'dateDepense' => date('Y-m-d'), 'description' => ''];
$retour  = 'detailsTerrain.php?id=' . $id . '&onglet=';

/* ---------------- Actions (formulaires des onglets) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouterDepense') {
        $onglet = 'depenses';
        $depense = ['categorie' => $_POST['categorie'] ?? '', 'montant' => trim($_POST['montant'] ?? ''),
                    'dateDepense' => $_POST['dateDepense'] ?? '', 'description' => trim($_POST['description'] ?? '')];
        $erreursDepense = $depenseC->valider($depense);
        if (empty($erreursDepense)) {
            $depenseC->ajouter($id, $depense);
            $badges = (new BadgeAutoController())->verifier($idUtilisateur);
            setFlash(t('Dépense enregistrée.') . BadgeAutoController::message($badges));
            header('Location: ' . $retour . 'depenses');
            exit;
        }
    }

    if ($action === 'supprimerDepense') {
        $ok = $depenseC->supprimer((int) ($_POST['idDepense'] ?? 0), $idUtilisateur);
        setFlash(t($ok ? 'Dépense supprimée.' : 'Dépense introuvable.'), $ok ? 'success' : 'danger');
        header('Location: ' . $retour . 'depenses');
        exit;
    }
}

/* ---------------- Données affichées ---------------- */
$flash     = getFlash();
$recoltes  = (new RecolteController())->listRecoltesByTerrain($id);
$depenses  = $depenseC->listParTerrain($id);
$rentab    = (new AnalyseController())->rentabilite($idUtilisateur, $id);
$rentabT   = $rentab['terrains'][0] ?? ['revenu' => 0, 'depenses' => 0, 'benefice' => 0];

[$lat, $lng, $positionExacte] = TerrainController::position($terrain);
$meteo   = (new MeteoController())->previsions($lat, $lng);
$alertes = $meteo['ok'] ? MeteoController::alertes($meteo['jours']) : [];

$kgTotal   = array_sum(array_map(fn($r) => (float) $r['quantiteKg'], $recoltes));
$parcelle  = TerrainController::lirePolygone($terrain['polygone']);

// Satellite : chargé seulement quand on ouvre l'onglet (appel d'API plus long)
$satellite = $onglet === 'satellite' ? (new SatelliteController())->ndvi($terrain) : null;

// Dépenses par catégorie (graphique)
$parCategorie = [];
foreach ($depenses as $d) {
    $parCategorie[$d['categorie']] = ($parCategorie[$d['categorie']] ?? 0) + (float) $d['montant'];
}
arsort($parCategorie);

$titrePage  = $terrain['nom'];
$pageActive = 'mesTerrains.php';
$avecCarte  = true;
$jsSupp     = ['assets/js/carte-terrains.js', 'assets/js/graphiques.js', 'assets/js/validation-depense.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">

        <!-- En-tête du terrain -->
        <div class="tb-entete-terrain" <?php if ($terrain['photo']): ?>style="background-image:linear-gradient(90deg,rgba(31,46,38,.85),rgba(31,46,38,.35)),url('assets/img/uploads/terrains/<?= e($terrain['photo']) ?>')"<?php endif; ?>>
          <div>
            <p class="sur-titre"><i class="bi bi-geo-alt"></i> <?= e($terrain['localisation']) ?> · <?= te($terrain['typeSol']) ?></p>
            <h1><?= e($terrain['nom']) ?></h1>
            <p class="mb-0"><?= e($terrain['superficie']) ?> ha
              <?php if ($positionExacte): ?> · <i class="bi bi-crosshair"></i> <?= e(number_format($lat, 4)) ?>, <?= e(number_format($lng, 4)) ?><?php endif; ?></p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="ajouterRecolte.php?idTerrain=<?= e($id) ?>" class="btn-hero"><i class="bi bi-plus-lg"></i> <?= te('Ajouter une récolte') ?></a>
            <a href="recommandation.php?terrain=<?= e($id) ?>" class="btn-hero btn-hero-clair"><i class="bi bi-stars"></i> <?= te('Que planter ?') ?></a>
            <a href="ficheTerrain.php?id=<?= e($id) ?>" class="btn-hero btn-hero-clair" target="_blank"><i class="bi bi-file-earmark-pdf"></i> <?= te('Fiche PDF') ?></a>
            <a href="modifierTerrain.php?id=<?= e($id) ?>" class="btn-hero btn-hero-clair"><i class="bi bi-pencil"></i></a>
          </div>
        </div>

        <?php if ($flash): ?>
          <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php foreach (array_slice($alertes, 0, 2) as [$type, $icone, $texte, $valeurs]): ?>
          <div class="alert alert-<?= e($type) ?> d-flex gap-2 align-items-center"><i class="bi <?= e($icone) ?> fs-5"></i> <?= te($texte, $valeurs) ?></div>
        <?php endforeach; ?>

        <!-- Onglets -->
        <ul class="nav tb-onglets" role="tablist">
          <?php foreach (['apercu' => ['bi-grid', 'Aperçu'], 'meteo' => ['bi-cloud-sun', 'Météo et eau'],
                          'recoltes' => ['bi-basket', 'Récoltes'],
                          'depenses' => ['bi-cash-coin', 'Rentabilité'], 'satellite' => ['bi-globe-europe-africa', 'Satellite']] as $cle => [$icone, $libelle]): ?>
            <li class="nav-item">
              <?php if ($cle === 'satellite'): ?>
                <a class="nav-link <?= $onglet === $cle ? 'active' : '' ?>" href="<?= e($retour . 'satellite') ?>"><i class="bi <?= $icone ?>"></i> <?= te($libelle) ?></a>
              <?php else: ?>
                <button class="nav-link <?= $onglet === $cle ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#onglet-<?= $cle ?>" type="button" role="tab">
                  <i class="bi <?= $icone ?>"></i> <?= te($libelle) ?>
                  <?php if ($cle === 'recoltes'): ?><span class="tb-compteur"><?= count($recoltes) ?></span><?php endif; ?>
                </button>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="tab-content tb-panneau tb-panneau-onglets">

          <!-- ===================== APERÇU ===================== -->
          <div class="tab-pane fade <?= $onglet === 'apercu' ? 'show active' : '' ?>" id="onglet-apercu" role="tabpanel">
            <div class="row g-3 mb-4">
              <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-basket"></i><strong><?= count($recoltes) ?></strong><span><?= te('Récoltes') ?></span></div></div>
              <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-box-seam"></i><strong><?= e(number_format($kgTotal / 1000, 2, ',', ' ')) ?> t</strong><span><?= te('Production totale') ?></span></div></div>
              <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-graph-up"></i><strong><?= e(number_format($kgTotal / 1000 / max(0.01, (float) $terrain['superficie']), 2, ',', ' ')) ?> t/ha</strong><span><?= te('Rendement') ?></span></div></div>
              <div class="col-6 col-lg-3"><div class="tb-kpi <?= $rentabT['benefice'] < 0 ? 'negatif' : '' ?>"><i class="bi bi-cash-stack"></i><strong><?= e(number_format($rentabT['benefice'], 0, ',', ' ')) ?> DT</strong><span><?= te('Bénéfice estimé') ?></span></div></div>
            </div>
            <div class="row g-4">
              <div class="col-lg-7">
                <div id="carteTerrains" class="tb-carte" data-lien-details=""
                     data-points='<?= e(json_encode([['id' => null, 'nom' => $terrain['nom'], 'lat' => $lat, 'lng' => $lng, 'exact' => $positionExacte, 'polygone' => $parcelle, 'info' => $terrain['superficie'] . ' ha']])) ?>'
                     data-texte-approx="<?= te('Position approximative (centre de la wilaya)') ?>"></div>
                <?php if (!$positionExacte): ?>
                  <p class="small text-muted mt-2"><i class="bi bi-info-circle"></i> <?= te('Position approximative (centre de la wilaya)') ?> — <a href="modifierTerrain.php?id=<?= e($id) ?>"><?= te('placer le terrain sur la carte') ?></a></p>
                <?php endif; ?>
              </div>
              <div class="col-lg-5">
                <h3 class="tb-sous-titre"><?= te('Dernières récoltes') ?></h3>
                <?php if (empty($recoltes)): ?>
                  <p class="text-muted"><?= te('Aucune récolte sur ce terrain.') ?> <a href="recommandation.php?terrain=<?= e($id) ?>"><?= te('Voir les cultures conseillées') ?></a></p>
                <?php else: ?>
                  <?php foreach (array_slice($recoltes, 0, 4) as $r): ?>
                    <div class="tb-ligne-culture">
                      <i class="bi bi-basket"></i>
                      <div class="flex-grow-1"><strong><?= e($r['culture']) ?></strong>
                        <small class="d-block text-muted"><?= e(date('d/m/Y', strtotime($r['dateRecolte']))) ?> · <?= e(RecolteController::nombreCourt((float) $r['quantite'])) ?> <?= te($r['unite']) ?></small></div>
                      <a href="consommationEau.php?terrain=<?= e($id) ?>&amp;culture=<?= e(rawurlencode($r['culture'])) ?>" class="tb-lien" title="<?= te("Consommation d'eau") ?>"><i class="bi bi-droplet"></i></a>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- ===================== MÉTÉO ET EAU ===================== -->
          <div class="tab-pane fade <?= $onglet === 'meteo' ? 'show active' : '' ?>" id="onglet-meteo" role="tabpanel">
            <?php if (!$meteo['ok']): ?>
              <div class="alert alert-warning"><i class="bi bi-wifi-off"></i> <?= te('Météo indisponible pour le moment.') ?> <small>(<?= e($meteo['erreur']) ?>)</small></div>
            <?php else: ?>
              <div class="tb-meteo">
                <?php foreach ($meteo['jours'] as $j): ?>
                  <div class="tb-jour <?= $j['date'] === date('Y-m-d') ? 'aujourdhui' : '' ?>">
                    <span class="tb-jour-nom"><?= te(MeteoController::libelleJour($j['date'])) ?> <small><?= e(date('d/m', strtotime($j['date']))) ?></small></span>
                    <i class="bi <?= e($j['icone']) ?>" title="<?= te($j['libelle']) ?>"></i>
                    <strong><?= e(round($j['tmax'])) ?>° <small><?= e(round($j['tmin'])) ?>°</small></strong>
                    <span class="tb-jour-detail"><i class="bi bi-droplet"></i> <?= e($j['pluie']) ?> mm</span>
                    <span class="tb-jour-detail"><i class="bi bi-wind"></i> <?= e(round($j['vent'])) ?> km/h</span>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if (empty($alertes)): ?>
                <div class="alert alert-success mt-3"><i class="bi bi-check-circle"></i> <?= te('Aucune alerte météo pour les 7 prochains jours.') ?></div>
              <?php else: ?>
                <?php foreach ($alertes as [$type, $icone, $texte, $valeurs]): ?>
                  <div class="alert alert-<?= e($type) ?> mt-2 mb-0 d-flex gap-2"><i class="bi <?= e($icone) ?>"></i> <?= te($texte, $valeurs) ?></div>
                <?php endforeach; ?>
              <?php endif; ?>
              <div class="text-center mt-4">
                <a href="consommationEau.php?terrain=<?= e($id) ?>" class="btn-hero"><i class="bi bi-droplet-half"></i> <?= te("Calculer le besoin en eau de ce terrain") ?></a>
              </div>
              <p class="small text-muted mt-3 mb-0"><?= te('Source : Open-Meteo, mise à jour toutes les heures.') ?></p>
            <?php endif; ?>
          </div>

          <!-- ===================== RÉCOLTES ===================== -->
          <div class="tab-pane fade <?= $onglet === 'recoltes' ? 'show active' : '' ?>" id="onglet-recoltes" role="tabpanel">
            <?php if (empty($recoltes)): ?>
              <p class="text-muted"><?= te('Aucune récolte sur ce terrain.') ?> <a href="ajouterRecolte.php?idTerrain=<?= e($id) ?>"><?= te('Ajouter une récolte') ?></a></p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle tb-table">
                  <thead><tr><th><?= te('Culture') ?></th><th><?= te('Plantation') ?></th><th><?= te('Récolte') ?></th><th><?= te('Quantité') ?></th><th></th></tr></thead>
                  <tbody>
                    <?php foreach ($recoltes as $r): ?>
                      <tr>
                        <td><strong><?= e($r['culture']) ?></strong></td>
                        <td><?= e(date('d/m/Y', strtotime($r['datePlantation']))) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($r['dateRecolte']))) ?></td>
                        <td><?= e(RecolteController::nombreCourt((float) $r['quantite'])) ?> <?= te($r['unite']) ?></td>
                        <td class="text-end text-nowrap">
                          <?php if ($r['codeTracabilite']): ?>
                            <a href="etiquette.php?id=<?= e($r['id']) ?>" class="btn-icone" title="<?= te('Étiquette QR') ?>" target="_blank"><i class="bi bi-qr-code"></i></a>
                          <?php endif; ?>
                          <a href="modifierRecolte.php?id=<?= e($r['id']) ?>" class="btn-icone" title="<?= te('Modifier') ?>"><i class="bi bi-pencil"></i></a>
                          <form action="supprimerRecolte.php" method="POST" class="d-inline" data-confirm="<?= te('Supprimer cette récolte ?') ?>">
                            <?= champCsrf() ?>
                            <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                            <input type="hidden" name="retour" value="detailsTerrain.php?id=<?= e($id) ?>">
                            <button type="submit" class="btn-icone danger" title="<?= te('Supprimer') ?>"><i class="bi bi-trash"></i></button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- ===================== DÉPENSES ET RENTABILITÉ ===================== -->
          <div class="tab-pane fade <?= $onglet === 'depenses' ? 'show active' : '' ?>" id="onglet-depenses" role="tabpanel">
            <div class="row g-3 mb-4">
              <div class="col-md-4"><div class="tb-kpi"><i class="bi bi-arrow-down-circle"></i><strong><?= e(number_format($rentabT['revenu'], 0, ',', ' ')) ?> DT</strong><span><?= te("Chiffre d'affaires estimé") ?></span></div></div>
              <div class="col-md-4"><div class="tb-kpi"><i class="bi bi-arrow-up-circle"></i><strong><?= e(number_format($rentabT['depenses'], 0, ',', ' ')) ?> DT</strong><span><?= te('Dépenses') ?></span></div></div>
              <div class="col-md-4"><div class="tb-kpi <?= $rentabT['benefice'] < 0 ? 'negatif' : 'positif' ?>"><i class="bi bi-cash-stack"></i><strong><?= e(number_format($rentabT['benefice'], 0, ',', ' ')) ?> DT</strong><span><?= te('Bénéfice') ?></span></div></div>
            </div>
            <p class="small text-muted"><i class="bi bi-info-circle"></i> <?= te("Chiffre d'affaires = quantités récoltées × prix moyen du marché (mis à jour par l'administrateur).") ?></p>
            <div class="row g-4">
              <div class="col-lg-5">
                <h3 class="tb-sous-titre"><?= te('Nouvelle dépense') ?></h3>
                <form action="<?= e($retour . 'depenses') ?>" method="POST" id="formDepense" novalidate>
                  <?= champCsrf() ?>
                  <input type="hidden" name="action" value="ajouterDepense">
                  <div class="row">
                    <div class="col-6 mb-3">
                      <label for="categorie" class="form-label"><?= te('Catégorie') ?></label>
                      <select class="form-select <?= isset($erreursDepense['categorie']) ? 'is-invalid' : '' ?>" id="categorie" name="categorie">
                        <?php foreach (DepenseController::CATEGORIES as $c): ?>
                          <option value="<?= e($c) ?>" <?= $depense['categorie'] === $c ? 'selected' : '' ?>><?= te($c) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div class="erreur" id="erreur-categorie"><?= te($erreursDepense['categorie'] ?? '') ?></div>
                    </div>
                    <div class="col-6 mb-3">
                      <label for="montant" class="form-label"><?= te('Montant (DT)') ?></label>
                      <input type="text" class="form-control <?= isset($erreursDepense['montant']) ? 'is-invalid' : '' ?>" id="montant" name="montant" value="<?= e($depense['montant']) ?>" placeholder="150">
                      <div class="erreur" id="erreur-montant"><?= te($erreursDepense['montant'] ?? '') ?></div>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label for="dateDepense" class="form-label"><?= te('Date') ?></label>
                    <input type="date" class="form-control <?= isset($erreursDepense['dateDepense']) ? 'is-invalid' : '' ?>" id="dateDepense" name="dateDepense" value="<?= e($depense['dateDepense']) ?>">
                    <div class="erreur" id="erreur-dateDepense"><?= te($erreursDepense['dateDepense'] ?? '') ?></div>
                  </div>
                  <div class="mb-3">
                    <label for="description" class="form-label"><?= te('Description (facultatif)') ?></label>
                    <input type="text" class="form-control <?= isset($erreursDepense['description']) ? 'is-invalid' : '' ?>" id="description" name="description" value="<?= e($depense['description']) ?>">
                    <div class="erreur" id="erreur-description"><?= te($erreursDepense['description'] ?? '') ?></div>
                  </div>
                  <button type="submit" class="btn-hero border-0"><i class="bi bi-plus-lg"></i> <?= te('Enregistrer la dépense') ?></button>
                </form>
              </div>
              <div class="col-lg-7">
                <h3 class="tb-sous-titre"><?= te('Dépenses par catégorie') ?></h3>
                <div class="tb-graphique" data-type="barresH" data-unite="DT"
                     data-libelles='<?= e(json_encode(array_map('t', array_keys($parCategorie)), JSON_UNESCAPED_UNICODE)) ?>'
                     data-series='<?= e(json_encode([['nom' => t('Dépenses'), 'valeurs' => array_values($parCategorie), 'couleur' => '#6a4ce2']], JSON_UNESCAPED_UNICODE)) ?>'></div>
                <?php if (!empty($depenses)): ?>
                  <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle tb-table">
                      <thead><tr><th><?= te('Date') ?></th><th><?= te('Catégorie') ?></th><th><?= te('Description') ?></th><th class="text-end"><?= te('Montant') ?></th><th></th></tr></thead>
                      <tbody>
                        <?php foreach ($depenses as $d): ?>
                          <tr>
                            <td><?= e(date('d/m/Y', strtotime($d['dateDepense']))) ?></td>
                            <td><?= te($d['categorie']) ?></td>
                            <td><?= e($d['description'] ?? '') ?></td>
                            <td class="text-end"><?= e(number_format((float) $d['montant'], 3, ',', ' ')) ?> DT</td>
                            <td class="text-end">
                              <form action="<?= e($retour . 'depenses') ?>" method="POST" data-confirm="<?= te('Supprimer cette dépense ?') ?>">
                                <?= champCsrf() ?>
                                <input type="hidden" name="action" value="supprimerDepense">
                                <input type="hidden" name="idDepense" value="<?= e($d['id']) ?>">
                                <button type="submit" class="btn-icone danger" title="<?= te('Supprimer') ?>"><i class="bi bi-x-lg"></i></button>
                              </form>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- ===================== SATELLITE (NDVI) ===================== -->
          <div class="tab-pane fade <?= $onglet === 'satellite' ? 'show active' : '' ?>" id="onglet-satellite" role="tabpanel">
            <p><?= te('Le NDVI mesure la vigueur de la végétation à partir des images satellites (de -1 à 1) : plus il est élevé, plus les plantes sont vertes et en bonne santé.') ?></p>
            <?php if ($satellite === null): ?>
              <a href="<?= e($retour . 'satellite') ?>" class="btn-hero"><?= te("Charger l'analyse satellite") ?></a>
            <?php elseif (!$satellite['ok']): ?>
              <div class="alert alert-info"><i class="bi bi-info-circle"></i> <?= te($satellite['erreur']) ?></div>
            <?php else: ?>
              <?php $derniere = end($satellite['historique']) ?: null; ?>
              <div class="row g-4">
                <div class="col-lg-5">
                  <?php if ($derniere): ?>
                    <?php [$couleurNdvi, $texteNdvi] = SatelliteController::interpreter($derniere['ndvi']); ?>
                    <div class="tb-kpi mb-3"><i class="bi bi-globe-europe-africa"></i><strong><?= e(number_format($derniere['ndvi'], 2, ',', ' ')) ?></strong>
                      <span><?= te('NDVI moyen au {date}', ['date' => date('d/m/Y', strtotime($derniere['date']))]) ?></span></div>
                    <div class="alert alert-<?= e($couleurNdvi) ?>"><?= te($texteNdvi) ?></div>
                  <?php endif; ?>
                  <?php if ($satellite['image']): ?>
                    <img src="ndviImage.php?id=<?= e($id) ?>" alt="NDVI" class="tb-ndvi-image">
                    <p class="small text-muted"><?= te('Image {source} du {date}', ['source' => $satellite['image']['source'], 'date' => date('d/m/Y', strtotime($satellite['image']['date']))]) ?>
                      — <?= te('vert = végétation dense, rouge = sol nu ou plantes en souffrance') ?></p>
                  <?php endif; ?>
                </div>
                <div class="col-lg-7">
                  <h3 class="tb-sous-titre"><?= te('Évolution du NDVI (6 mois)') ?></h3>
                  <div class="tb-graphique" data-type="ligne" data-unite=""
                       data-libelles='<?= e(json_encode(array_map(fn($h) => date('d/m', strtotime($h['date'])), $satellite['historique']))) ?>'
                       data-series='<?= e(json_encode([['nom' => 'NDVI', 'valeurs' => array_column($satellite['historique'], 'ndvi'), 'couleur' => '#008a17']])) ?>'></div>
                </div>
              </div>
              <p class="small text-muted mt-3 mb-0"><?= te('Source : Agromonitoring (Sentinel-2, Landsat 8). Images très nuageuses ignorées.') ?></p>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
