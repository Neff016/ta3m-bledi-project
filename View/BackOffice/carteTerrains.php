<?php
/*
 * Back Office : carte de tous les terrains de la Tunisie (parcelles dessinées, points GPS, centres de wilaya)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';

requireAdmin();

$terrainC = new TerrainController();
$points   = [];
foreach ($terrainC->terrainsPourCarte() as $t) {
    [$lat, $lng, $exact] = TerrainController::position($t);
    $points[] = [
        'id' => null, 'nom' => $t['nom'], 'lat' => $lat, 'lng' => $lng, 'exact' => $exact,
        'polygone' => TerrainController::lirePolygone($t['polygone']), 'proprietaire' => $t['proprietaire'],
        'info' => $t['localisation'] . ' · ' . $t['superficie'] . ' ha · ' . $t['typeSol'],
    ];
}
$parWilaya = $terrainC->terrainsParWilaya();

$titrePage = 'Carte des terrains';
$boActif   = 'carteTerrains.php';
$avecCarte = true;
require __DIR__ . '/partials/boHaut.php';
?>
                    <h1 class="h3 mb-2 text-gray-800">Carte des terrains</h1>
                    <p class="mb-4">
                        <i class="fas fa-draw-polygon text-success"></i> parcelle dessinée ·
                        <i class="fas fa-map-marker-alt text-primary"></i> point GPS ·
                        <i class="fas fa-circle text-warning"></i> position approximative (centre de la wilaya)
                    </p>

                    <div class="row">
                        <div class="col-xl-9 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-body p-2">
                                    <div id="carteTerrains" class="carte-admin" data-points='<?= e(json_encode($points)) ?>'
                                         data-texte-approx="Position approximative (centre de la wilaya)"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Terrains par wilaya</h6></div>
                                <div class="card-body">
                                    <?php if (empty($parWilaya)): ?><p class="text-muted mb-0">Aucun terrain.</p><?php endif; ?>
                                    <?php $max = max(array_column($parWilaya, 'nb') ?: [1]); ?>
                                    <?php foreach ($parWilaya as $w): ?>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between small"><span><?= e($w['localisation']) ?></span>
                                                <strong><?= e($w['nb']) ?> · <?= e(number_format((float) $w['superficie'], 1, ',', ' ')) ?> ha</strong></div>
                                            <div class="progress progress-sm"><div class="progress-bar bg-success" style="width: <?= (int) round($w['nb'] / $max * 100) ?>%"></div></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
<?php
$jsSupp = ['../FrontOffice/assets/js/carte-terrains.js'];
require __DIR__ . '/partials/boBas.php';
