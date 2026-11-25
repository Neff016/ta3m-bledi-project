<?php
/*
 * Back Office : contrats fonciers et certifications bio de tous les terrains
 *  - répartition propriété / location / khammès / métayage
 *  - contrats qui se terminent bientôt (l'agriculteur risque de perdre son terrain)
 *  - terrains bio, en conversion, certificats expirés
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/ContratFoncierController.php';
require_once __DIR__ . '/../../Controller/CertificationBioController.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';

requireAdmin();

$contratC = new ContratFoncierController();
$type     = isset(ContratFoncierController::TYPES[$_GET['type'] ?? '']) ? $_GET['type'] : '';
$contrats = $contratC->listAll($type);
$tous     = $type === '' ? $contrats : $contratC->listAll();
$certifs  = (new CertificationBioController())->listAll();
$nbTerrains = (new TerrainController())->countTerrains();

$parType = array_fill_keys(array_keys(ContratFoncierController::TYPES), 0);
foreach ($tous as $c) {
    $parType[$c['type']]++;
}
$aujourdhui = date('Y-m-d');
$bientot = array_filter($tous, fn($c) => $c['dateFin'] && $c['dateFin'] >= $aujourdhui
                                         && $c['dateFin'] <= date('Y-m-d', strtotime('+' . ContratFoncierController::JOURS_ALERTE . ' days')));
$statuts = array_fill_keys(array_keys(CertificationBioController::STATUTS), 0);
foreach ($certifs as $i => $b) {
    $certifs[$i]['statut'] = CertificationBioController::statut($b);
    $statuts[$certifs[$i]['statut']]++;
}
$couleursBio = ['AVANT' => 'secondary', 'CONVERSION' => 'warning', 'CERTIFIE' => 'success', 'EXPIRE' => 'danger'];

$titrePage = 'Foncier et bio';
$boActif   = 'foncierBio.php';
require __DIR__ . '/partials/boHaut.php';
?>
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800">Contrats fonciers et certifications bio</h1>
                            <p class="mb-0 small">Les agriculteurs remplissent ces informations dans l'onglet « Contrat et bio » de chaque terrain.</p>
                        </div>
                    </div>

                    <div class="row">
                        <?php foreach (ContratFoncierController::TYPES as $code => $libelle): ?>
                            <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= e($libelle) ?></div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($parType[$code]) ?> <small class="text-muted">/ <?= e($nbTerrains) ?> terrains</small></div>
                            </div></div></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-4"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Contrats finissant dans <?= ContratFoncierController::JOURS_ALERTE ?> jours</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($bientot) ?></div></div></div></div>
                        <div class="col-md-4 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Terrains certifiés bio</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($statuts['CERTIFIE']) ?> <small class="text-muted">+ <?= e($statuts['CONVERSION']) ?> en conversion</small></div></div></div></div>
                        <div class="col-md-4 mb-4"><div class="card border-left-danger shadow h-100 py-2"><div class="card-body">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Certificats expirés</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($statuts['EXPIRE']) ?></div></div></div></div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Contrats fonciers (<?= count($contrats) ?>)</h6>
                            <form method="GET" class="form-inline" novalidate>
                                <select name="type" class="form-control form-control-sm mr-2">
                                    <option value="">Tous les régimes</option>
                                    <?php foreach (ContratFoncierController::TYPES as $code => $libelle): ?>
                                        <option value="<?= e($code) ?>" <?= $type === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr><th>Terrain</th><th>Agriculteur</th><th>Régime</th><th>Propriétaire</th><th>Conditions</th><th>Début</th><th>Fin</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($contrats)): ?>
                                            <tr><td colspan="7" class="text-center">Aucun contrat.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($contrats as $c): ?>
                                            <?php $jours = $c['dateFin'] ? $contratC->versObjet($c)->joursRestants() : null; ?>
                                            <tr>
                                                <td><strong><?= e($c['terrainNom']) ?></strong> <small class="text-muted">(<?= e($c['localisation']) ?>, <?= e($c['superficie']) ?> ha)</small></td>
                                                <td><?= e($c['agriculteur']) ?></td>
                                                <td><?= e(ContratFoncierController::TYPES[$c['type']] ?? $c['type']) ?></td>
                                                <td><?= e($c['proprietaire'] ?? '—') ?></td>
                                                <td>
                                                    <?php if ($c['type'] === 'LOCATION'): ?><?= e(number_format((float) $c['loyerAnnuel'], 0, ',', ' ')) ?> DT / an
                                                    <?php elseif ($c['type'] !== 'PROPRIETE'): ?><?= e(rtrim(rtrim(number_format((float) $c['partProprietaire'], 2, ',', ''), '0'), ',')) ?> % de la récolte
                                                    <?php else: ?>—<?php endif; ?>
                                                </td>
                                                <td><?= e(date('d/m/Y', strtotime($c['dateDebut']))) ?></td>
                                                <td>
                                                    <?php if ($c['dateFin']): ?>
                                                        <?= e(date('d/m/Y', strtotime($c['dateFin']))) ?>
                                                        <?php if ($jours < 0): ?><span class="badge badge-danger">terminé</span>
                                                        <?php elseif ($jours <= ContratFoncierController::JOURS_ALERTE): ?><span class="badge badge-warning">dans <?= e($jours) ?> j</span><?php endif; ?>
                                                    <?php else: ?>—<?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Certifications bio (<?= count($certifs) ?>)</h6></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr><th>Terrain</th><th>Agriculteur</th><th>Organisme</th><th>N° certificat</th><th>Conversion</th><th>Certifié depuis</th><th>Expire le</th><th>Statut</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($certifs)): ?>
                                            <tr><td colspan="8" class="text-center">Aucune certification bio.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($certifs as $b): ?>
                                            <tr>
                                                <td><strong><?= e($b['terrainNom']) ?></strong> <small class="text-muted">(<?= e($b['localisation']) ?>)</small></td>
                                                <td><?= e($b['agriculteur']) ?></td>
                                                <td><?= e($b['organisme']) ?></td>
                                                <td><?= e($b['numeroCertificat'] ?? '—') ?></td>
                                                <td><?= e(date('d/m/Y', strtotime($b['dateDebutConversion']))) ?> · <?= e($b['dureeConversionMois']) ?> mois</td>
                                                <td><?= e(date('d/m/Y', strtotime(CertificationBioController::versObjet($b)->getDateCertification()))) ?></td>
                                                <td><?= $b['dateExpiration'] ? e(date('d/m/Y', strtotime($b['dateExpiration']))) : '—' ?></td>
                                                <td><span class="badge badge-<?= $couleursBio[$b['statut']] ?>"><?= e(CertificationBioController::STATUTS[$b['statut']]) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
<?php require __DIR__ . '/partials/boBas.php'; ?>
