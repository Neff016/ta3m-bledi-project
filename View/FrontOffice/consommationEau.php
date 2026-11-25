<?php
/*
 * Consommation d'eau : besoin en eau d'une culture sur un terrain pour les 7 prochains jours
 * Méthode FAO-56 : ETc = ET0 (Open-Meteo) × kc (catalogue) ; besoin net = ETc − pluie utile
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/CultureController.php';
require_once __DIR__ . '/../../Controller/MeteoController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$terrainC      = new TerrainController();
$cultureC      = new CultureController();
$terrains      = $terrainC->listTerrainsByUser($idUtilisateur);
$cultures      = $cultureC->listCultures();

$idTerrain = filter_input(INPUT_GET, 'terrain', FILTER_VALIDATE_INT) ?: ($terrains[0]['id'] ?? null);
$terrain   = $idTerrain ? $terrainC->getTerrainOfUser((int) $idTerrain, $idUtilisateur) : false;

// Culture : celle demandée, sinon la dernière récoltée sur le terrain, sinon la première du catalogue
$culture = null;
if (($_GET['culture'] ?? '') !== '') {
    $culture = $cultureC->trouver((string) $_GET['culture']);
}
if (!$culture && $terrain) {
    foreach ((new RecolteController())->listRecoltesByTerrain((int) $terrain['id']) as $r) {
        if ($culture = $cultureC->trouver($r['culture'])) {
            break;
        }
    }
}
$culture ??= $cultures[0] ?? null;

$resultat = null;
$erreur   = null;
if ($idTerrain && !$terrain) {
    $erreur = "Ce terrain n'existe pas ou ne vous appartient pas.";
} elseif ($terrain && $culture) {
    [$lat, $lng] = TerrainController::position($terrain);
    $meteo = (new MeteoController())->previsions($lat, $lng);
    if ($meteo['ok']) {
        $resultat = MeteoController::besoinEau($meteo['jours'], (float) $culture['kc'], (float) $terrain['superficie']);
        [$conseil, $valeursConseil] = MeteoController::conseilIrrigation($resultat);
    } else {
        $erreur = $meteo['erreur'];
    }
}

$titrePage  = "Consommation d'eau";
$pageActive = 'consommationEau.php';
$jsSupp     = ['assets/js/graphiques.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">

        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= te('Irrigation intelligente') ?></p>
            <h1><?= te("Consommation d'eau") ?></h1>
          </div>
        </div>

        <?php if (empty($terrains)): ?>
          <div class="carte-terrain carte-vide">
            <i class="bi bi-droplet"></i>
            <p class="mt-3 mb-4"><?= te('Ajoutez un terrain pour calculer son besoin en eau.') ?></p>
            <div><a href="ajouterTerrain.php" class="btn-hero"><?= te('Ajouter un terrain') ?></a></div>
          </div>
        <?php else: ?>

        <form method="GET" class="tb-panneau mb-4" novalidate>
          <div class="row g-3 align-items-end">
            <div class="col-md-5">
              <label for="terrain" class="form-label"><?= te('Terrain') ?></label>
              <select class="form-select" id="terrain" name="terrain">
                <?php foreach ($terrains as $t): ?>
                  <option value="<?= e($t['id']) ?>" <?= (int) $idTerrain === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nom']) ?> (<?= e($t['superficie']) ?> ha)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="culture" class="form-label"><?= te('Culture') ?></label>
              <select class="form-select" id="culture" name="culture">
                <?php foreach ($cultures as $c): ?>
                  <option value="<?= e($c['nom']) ?>" <?= $culture && $culture['id'] === $c['id'] ? 'selected' : '' ?>><?= te($c['nom']) ?> (kc <?= e($c['kc']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn-hero border-0 w-100"><i class="bi bi-calculator"></i> <?= te('Calculer') ?></button>
            </div>
          </div>
        </form>

        <?php if ($erreur): ?>
          <div class="alert alert-warning"><i class="bi bi-wifi-off"></i> <?= te($erreur) ?></div>
        <?php elseif ($resultat): ?>

          <div class="tb-conseil-eau mb-4">
            <i class="bi bi-droplet-half"></i>
            <div>
              <small><?= te('Conseil du jour pour {c} — {t}', ['c' => t($culture['nom']), 't' => $terrain['nom']]) ?></small>
              <p class="mb-0"><?= te($conseil, $valeursConseil) ?></p>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="tb-kpi"><i class="bi bi-moisture"></i><strong><?= e(number_format($resultat['totalM3'], 1, ',', ' ')) ?> m³</strong><span><?= te('Besoin sur 7 jours') ?></span></div></div>
            <div class="col-md-4"><div class="tb-kpi"><i class="bi bi-calendar-day"></i><strong><?= e(number_format($resultat['moyenneM3'], 1, ',', ' ')) ?> m³</strong><span><?= te('Moyenne par jour') ?></span></div></div>
            <div class="col-md-4"><div class="tb-kpi"><i class="bi bi-recycle"></i><strong><?= e(number_format($resultat['totalM3'] * 0.35, 1, ',', ' ')) ?> m³</strong><span><?= te('Économie possible au goutte-à-goutte (≈ 35 %)') ?></span></div></div>
          </div>

          <div class="row g-4">
            <div class="col-lg-6">
              <div class="tb-panneau h-100">
                <h2 class="tb-titre-panneau"><?= te("Eau d'irrigation à apporter par jour (m³)") ?></h2>
                <div class="tb-graphique" data-type="barres" data-unite="m³"
                     data-libelles='<?= e(json_encode(array_map(fn($j) => date('d/m', strtotime($j['date'])), $resultat['jours']))) ?>'
                     data-series='<?= e(json_encode([['nom' => 'm³', 'valeurs' => array_column($resultat['jours'], 'm3'), 'couleur' => '#257de0']])) ?>'></div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="tb-panneau h-100">
                <h2 class="tb-titre-panneau"><?= te('Détail du calcul') ?></h2>
                <div class="table-responsive">
                  <table class="table table-sm align-middle tb-table">
                    <thead><tr><th><?= te('Jour') ?></th><th title="<?= te('Évapotranspiration de référence') ?>">ET0</th><th title="ET0 × kc">ETc</th><th><?= te('Pluie') ?></th><th><?= te('Besoin net') ?></th><th class="text-end">m³</th></tr></thead>
                    <tbody>
                      <?php foreach ($resultat['jours'] as $j): ?>
                        <tr><td><?= te(MeteoController::libelleJour($j['date'])) ?></td><td><?= e($j['et0']) ?> mm</td><td><?= e($j['etc']) ?> mm</td>
                            <td><?= e($j['pluie']) ?> mm</td><td><?= e($j['netMm']) ?> mm</td><td class="text-end fw-bold"><?= e(number_format($j['m3'], 1, ',', ' ')) ?></td></tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="tb-panneau mt-4">
            <h2 class="tb-titre-panneau"><i class="bi bi-mortarboard"></i> <?= te('Comment est fait le calcul ?') ?></h2>
            <ol class="mb-0">
              <li><?= te('ET0 : quantité d’eau évaporée chaque jour par une prairie de référence (donnée Open-Meteo pour la position du terrain).') ?></li>
              <li><?= te('ETc = ET0 × kc : le coefficient kc dépend de la culture ({c} : {kc}).', ['c' => t($culture['nom']), 'kc' => $culture['kc']]) ?></li>
              <li><?= te('Pluie utile = 80 % de la pluie prévue ; le surplus est gardé pour le jour suivant.') ?></li>
              <li><?= te('Volume = besoin net (mm) × superficie ({s} ha) × 10 (1 mm sur 1 ha = 10 m³).', ['s' => $terrain['superficie']]) ?></li>
            </ol>
          </div>
        <?php endif; ?>
        <?php endif; ?>

      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
