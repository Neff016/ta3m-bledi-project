<?php
/*
 * Back Office : tous les terrains (filtres, tri, pagination, export CSV)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/ContratFoncierController.php';
require_once __DIR__ . '/../../Controller/CertificationBioController.php';
require_once __DIR__ . '/partials/boOutils.php';

requireAdmin();                                   // Admin SEULEMENT

$terrainC = new TerrainController();
$filtres  = [
    'q'      => trim($_GET['q'] ?? ''),
    'wilaya' => $_GET['wilaya'] ?? '',
    'sol'    => $_GET['sol'] ?? '',
];
$tri     = isset(TerrainController::TRIS[$_GET['tri'] ?? '']) ? $_GET['tri'] : 'id';
$ordre   = ($_GET['ordre'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$parPage = 10;

$resultat = $terrainC->listAllTerrains($filtres, $tri, $ordre, $page, $parPage);
$terrains = $resultat['lignes'];
$certifs  = [];
foreach ((new CertificationBioController())->listAll() as $b) {
    $certifs[(int) $b['idTerrain']] = CertificationBioController::statut($b);
}
$total    = $resultat['total'];
$params   = array_filter($filtres + ['tri' => $tri, 'ordre' => $ordre], fn($v) => $v !== '');
$flash    = getFlash();

$titrePage = 'Terrains';
$boActif   = 'listeTerrains.php';
require __DIR__ . '/partials/boHaut.php';
?>
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Liste des terrains</h1>
                        <div>
                            <a href="carteTerrains.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-globe-africa"></i> Voir sur la carte</a>
                            <a href="exportCsv.php?<?= e(http_build_query(['type' => 'terrains'] + $params)) ?>" class="btn btn-sm btn-success"><i class="fas fa-file-csv"></i> Exporter en CSV</a>
                            <a href="formTerrain.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Ajouter un terrain</a>
                        </div>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4 mb-4"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Terrains</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $terrainC->countTerrains() ?></div></div></div></div>
                        <div class="col-md-4 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Superficie totale</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e(number_format($terrainC->sumSuperficie(), 2, ',', ' ')) ?> ha</div></div></div></div>
                        <div class="col-md-4 mb-4"><div class="card border-left-info shadow h-100 py-2"><div class="card-body">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Agriculteurs avec terrain</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $terrainC->countAgriculteurs() ?></div></div></div></div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Terrains (<?= $total ?> résultat<?= $total > 1 ? 's' : '' ?>)</h6>
                        </div>
                        <div class="card-body">

                            <!-- Filtres -->
                            <form method="GET" class="form-row mb-3">
                                <div class="col-md-4 mb-2">
                                    <input type="text" name="q" class="form-control" value="<?= e($filtres['q']) ?>" placeholder="Terrain, wilaya ou propriétaire...">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <select name="wilaya" class="form-control">
                                        <option value="">Toutes les wilayas</option>
                                        <?php foreach (TerrainController::WILAYAS as $w): ?>
                                            <option value="<?= e($w) ?>" <?= $filtres['wilaya'] === $w ? 'selected' : '' ?>><?= e($w) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <select name="sol" class="form-control">
                                        <option value="">Tous les sols</option>
                                        <?php foreach (TerrainController::TYPES_SOL as $s): ?>
                                            <option value="<?= e($s) ?>" <?= $filtres['sol'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrer</button>
                                    <a href="listeTerrains.php" class="btn btn-light">Effacer</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th><?= enteteTri('id', '#', $tri, $ordre, $params) ?></th>
                                            <th><?= enteteTri('nom', 'Nom', $tri, $ordre, $params) ?></th>
                                            <th><?= enteteTri('proprietaire', 'Propriétaire', $tri, $ordre, $params) ?></th>
                                            <th><?= enteteTri('localisation', 'Wilaya', $tri, $ordre, $params) ?></th>
                                            <th><?= enteteTri('superficie', 'Superficie (ha)', $tri, $ordre, $params) ?></th>
                                            <th><?= enteteTri('typeSol', 'Sol', $tri, $ordre, $params) ?></th>
                                            <th><?= enteteTri('nbRecoltes', 'Récoltes', $tri, $ordre, $params) ?></th>
                                            <th>Foncier / bio</th>
                                            <th>GPS</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($terrains)): ?>
                                            <tr><td colspan="10" class="text-center">Aucun terrain trouvé.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($terrains as $t): ?>
                                            <tr>
                                                <td><?= e($t['id']) ?></td>
                                                <td><?= e($t['nom']) ?></td>
                                                <td><?= e($t['proprietaire']) ?></td>
                                                <td><?= e($t['localisation']) ?></td>
                                                <td><?= e($t['superficie']) ?></td>
                                                <td><?= e($t['typeSol']) ?></td>
                                                <td><?= e($t['nbRecoltes']) ?><?= $t['nbParcelles'] > 0 ? ' <small class="text-muted">· ' . e($t['nbParcelles']) . ' parc.</small>' : '' ?></td>
                                                <td>
                                                    <?= $t['typeContrat'] ? '<small>' . e(ContratFoncierController::TYPES[$t['typeContrat']] ?? $t['typeContrat']) . '</small>' : '<span class="text-gray-400">—</span>' ?>
                                                    <?php $sb = $certifs[(int) $t['id']] ?? null; ?>
                                                    <?php if ($sb === 'CERTIFIE'): ?><span class="badge badge-success">Bio</span>
                                                    <?php elseif ($sb === 'CONVERSION'): ?><span class="badge badge-warning">Conversion</span><?php endif; ?>
                                                </td>
                                                <td class="text-center"><?= $t['latitude'] !== null ? '<i class="fas fa-map-marker-alt text-success" title="Position GPS"></i>' : '<span class="text-gray-400">—</span>' ?>
                                                    <?= $t['polygone'] ? '<i class="fas fa-draw-polygon text-primary" title="Parcelle dessinée"></i>' : '' ?></td>
                                                <td class="text-nowrap">
                                                    <a href="formTerrain.php?id=<?= e($t['id']) ?>" class="btn btn-info btn-sm" title="Modifier"><i class="fas fa-pen"></i></a>
                                                    <form action="supprimerTerrain.php" method="POST" class="d-inline" data-confirm="Supprimer ce terrain et toutes ses récoltes ?">
                                                        <?= champCsrf() ?>
                                                        <input type="hidden" name="id" value="<?= e($t['id']) ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?= pagination($page, $total, $parPage, $params) ?>
                        </div>
                    </div>
<?php require __DIR__ . '/partials/boBas.php'; ?>
