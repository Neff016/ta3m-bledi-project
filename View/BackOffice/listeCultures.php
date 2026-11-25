<?php
/*
 * Back Office : catalogue des cultures (moteur de recommandation, besoin en eau, prix du marché)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/CultureController.php';

requireAdmin();

$cultures = (new CultureController())->listCultures();
$flash    = getFlash();

$titrePage = 'Cultures et prix';
$boActif   = 'listeCultures.php';
require __DIR__ . '/partials/boHaut.php';
?>
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800">Cultures et prix du marché</h1>
                            <p class="mb-0 small">Ces données alimentent la recommandation, le calcul d'eau et la rentabilité des agriculteurs.</p>
                        </div>
                        <a href="formCulture.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Ajouter une culture</a>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Catalogue (<?= count($cultures) ?> cultures)</h6></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr><th>Culture</th><th>Catégorie</th><th>Sols</th><th>Saisons</th><th>Eau (kc)</th><th>Rendement</th><th>Durée</th><th>Prix</th><th>Actions</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cultures as $c): ?>
                                            <tr>
                                                <td><strong><?= e($c['nom']) ?></strong>
                                                    <small class="d-block text-muted"><?= $c['regions'] === '*' ? 'Toutes les régions' : e(str_replace(',', ', ', $c['regions'])) ?></small></td>
                                                <td><?= e($c['categorie']) ?></td>
                                                <td><small><?= e(str_replace(',', ', ', $c['sols'])) ?></small></td>
                                                <td><small><?= e(str_replace(',', ', ', $c['saisons'])) ?></small></td>
                                                <td><?= e(CultureController::EAU[$c['besoinEau']] ?? $c['besoinEau']) ?> (<?= e($c['kc']) ?>)</td>
                                                <td><?= e($c['rendement']) ?> t/ha</td>
                                                <td><?= e($c['dureeJours']) ?> j</td>
                                                <td class="text-nowrap"><strong><?= e(number_format((float) $c['prixKg'], 3, ',', ' ')) ?></strong> DT/kg</td>
                                                <td class="text-nowrap">
                                                    <a href="formCulture.php?id=<?= e($c['id']) ?>" class="btn btn-sm btn-info" title="Modifier"><i class="fas fa-pen"></i></a>
                                                    <form action="supprimerCulture.php" method="POST" class="d-inline" data-confirm="Supprimer cette culture du catalogue ?">
                                                        <?= champCsrf() ?>
                                                        <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
<?php require __DIR__ . '/partials/boBas.php'; ?>
