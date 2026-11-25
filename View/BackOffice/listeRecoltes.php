<?php
/*
 * Back Office : toutes les récoltes (filtres, tri, pagination, export CSV)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/partials/boOutils.php';

requireAdmin();

$recolteC = new RecolteController();
$filtres  = [
    'q'      => trim($_GET['q'] ?? ''),
    'wilaya' => $_GET['wilaya'] ?? '',
    'du'     => $_GET['du'] ?? '',
    'au'     => $_GET['au'] ?? '',
];
$tri     = isset(RecolteController::TRIS[$_GET['tri'] ?? '']) ? $_GET['tri'] : 'dateRecolte';
$ordre   = ($_GET['ordre'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$parPage = 10;

$resultat = $recolteC->listAllRecoltes($filtres, $tri, $ordre, $page, $parPage);
$recoltes = $resultat['lignes'];
$total    = $resultat['total'];
$tout     = $recolteC->listAllRecoltes($filtres)['lignes'];          // pour les totaux filtrés
$totalKg  = array_sum(array_map(fn($r) => (float) $r['quantiteKg'], $tout));
$moyenne  = count($tout) > 0 ? $totalKg / count($tout) : 0;
$params   = array_filter($filtres + ['tri' => $tri, 'ordre' => $ordre], fn($v) => $v !== '');
$flash    = getFlash();

$titrePage = 'Récoltes';
$boActif   = 'listeRecoltes.php';
require __DIR__ . '/partials/boHaut.php';
?>
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Liste des récoltes</h1>
                        <div class="d-flex flex-wrap" style="gap:6px">
                            <a href="exportCsv.php?<?= e(http_build_query(['type' => 'recoltes'] + $params)) ?>" class="btn btn-sm btn-success"><i class="fas fa-file-csv"></i> Exporter en CSV</a>
                        </div>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4 mb-4"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Récoltes (filtre actuel)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total ?></div></div></div></div>
                        <div class="col-md-4 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Production récoltée</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e(number_format($totalKg / 1000, 2, ',', ' ')) ?> t</div></div></div></div>
                        <div class="col-md-4 mb-4"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Moyenne par récolte</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e(number_format($moyenne, 0, ',', ' ')) ?> kg</div></div></div></div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Récoltes (<?= $total ?> résultat<?= $total > 1 ? 's' : '' ?>)</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="form-row mb-3" id="filtresRecoltes" novalidate>
                                <div class="col-md-5 mb-2">
                                    <input type="text" name="q" class="form-control" value="<?= e($filtres['q']) ?>" placeholder="Culture, terrain, agriculteur...">
                                </div>
                                <div class="col-md-2 mb-2">
                                    <select name="wilaya" class="form-control">
                                        <option value="">Toutes les wilayas</option>
                                        <?php foreach (TerrainController::WILAYAS as $w): ?>
                                            <option value="<?= e($w) ?>" <?= $filtres['wilaya'] === $w ? 'selected' : '' ?>><?= e($w) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2"><input type="date" name="du" class="form-control" value="<?= e($filtres['du']) ?>" title="Récolte à partir du"></div>
                                <div class="col-md-2 mb-2"><input type="date" name="au" class="form-control" value="<?= e($filtres['au']) ?>" title="Récolte jusqu'au"></div>
                                <div class="col-md-1 mb-2"><button type="submit" class="btn btn-primary btn-block" title="Filtrer"><i class="fas fa-filter"></i></button></div>
                                <div class="col-12"><small class="text-danger" id="erreurFiltres"></small>
                                    <a href="listeRecoltes.php" class="small">Effacer les filtres</a></div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th><?= enteteTri('culture', 'Culture', $tri, $ordre, $params) ?></th>
                                            <th><?= enteteTri('terrain', 'Terrain', $tri, $ordre, $params) ?></th>
                                            <th><?= enteteTri('proprietaire', 'Agriculteur', $tri, $ordre, $params) ?></th>
                                            <th>Plantation</th>
                                            <th><?= enteteTri('dateRecolte', 'Récolte', $tri, $ordre, $params) ?></th>
                                            <th><?= enteteTri('quantiteKg', 'Quantité (kg)', $tri, $ordre, $params) ?></th>
                                            <th>QR</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recoltes)): ?>
                                            <tr><td colspan="8" class="text-center">Aucune récolte trouvée.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($recoltes as $r): ?>
                                            <tr>
                                                <td><?= e($r['culture']) ?></td>
                                                <td><?= e($r['terrainNom']) ?> <small class="text-muted">(<?= e($r['localisation']) ?>)</small></td>
                                                <td><?= e($r['proprietaire']) ?></td>
                                                <td><?= e(date('d/m/Y', strtotime($r['datePlantation']))) ?></td>
                                                <td><?= e(date('d/m/Y', strtotime($r['dateRecolte']))) ?></td>
                                                <td class="text-right"><?= e(number_format((float) $r['quantiteKg'], 0, ',', ' ')) ?></td>
                                                <td class="text-center">
                                                    <?php if ($r['codeTracabilite']): ?>
                                                        <a href="../FrontOffice/tracabilite.php?code=<?= e($r['codeTracabilite']) ?>" target="_blank" title="Page publique"><i class="fas fa-qrcode"></i></a>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form action="supprimerRecolte.php" method="POST" class="d-inline" data-confirm="Supprimer cette récolte ?">
                                                        <?= champCsrf() ?>
                                                        <input type="hidden" name="id" value="<?= e($r['id']) ?>">
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
<?php
$jsSupp = ['js/filtres-recoltes.js'];
require __DIR__ . '/partials/boBas.php';
