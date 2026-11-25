<?php
/*
 * Mes récoltes terminées : quantités, total, étiquette QR
 * (les plantations en cours sont dans le module « Plantation et suivi »)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$recolteC      = new RecolteController();
$recoltes      = $recolteC->listRecoltesByUser($idUtilisateur);
$totalKg       = array_sum(array_map(fn($r) => (float) $r['quantiteKg'], $recoltes));
$flash   = getFlash();

$titrePage  = 'Mes récoltes';
$pageActive = 'mesRecoltes.php';
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">

        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= te('Suivi de production') ?></p>
            <h1><?= te('Mes récoltes') ?></h1>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="ajouterRecolte.php" class="btn-hero"><i class="bi bi-plus-lg"></i> <?= te('Ajouter une récolte') ?></a>
          </div>
        </div>

        <?php if ($flash): ?>
          <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <!-- Récoltes enregistrées hors ligne (remplies par hors-ligne.js) -->
        <div id="recoltesEnAttente" class="alert alert-info" hidden></div>

        <div class="tb-panneau">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <span class="text-muted"><?= te('{n} récolte(s)', ['n' => count($recoltes)]) ?></span>
            <strong class="text-success"><i class="bi bi-box-seam"></i> <?= te('{t} t récoltées au total', ['t' => number_format($totalKg / 1000, 2, ',', ' ')]) ?></strong>
          </div>

          <?php if (empty($recoltes)): ?>
            <div class="carte-vide">
              <i class="bi bi-basket"></i>
              <p class="mt-3 mb-4"><?= te('Aucune récolte ici pour le moment.') ?></p>
              <a href="ajouterRecolte.php" class="btn-hero"><?= te('Ajouter une récolte') ?></a>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table align-middle tb-table">
                <thead>
                  <tr><th></th><th><?= te('Culture') ?></th><th><?= te('Terrain') ?></th>
                      <th><?= te('Plantation') ?></th><th><?= te('Récolte') ?></th><th class="text-end"><?= te('Quantité') ?></th><th></th></tr>
                </thead>
                <tbody>
                  <?php foreach ($recoltes as $r): ?>
                    <tr>
                      <td class="tb-col-photo">
                        <?php if ($r['photo']): ?>
                          <img src="assets/img/uploads/recoltes/<?= e($r['photo']) ?>" alt="" class="tb-mini-photo">
                        <?php else: ?>
                          <span class="tb-mini-photo tb-mini-icone"><i class="bi bi-flower1"></i></span>
                        <?php endif; ?>
                      </td>
                      <td><strong><?= e($r['culture']) ?></strong></td>
                      <td><a href="detailsTerrain.php?id=<?= e($r['idTerrain']) ?>" class="tb-lien"><?= e($r['terrainNom']) ?></a></td>
                      <td><?= e(date('d/m/Y', strtotime($r['datePlantation']))) ?></td>
                      <td><?= e(date('d/m/Y', strtotime($r['dateRecolte']))) ?></td>
                      <td class="text-end">
                        <?= e(RecolteController::nombreCourt((float) $r['quantite'])) ?> <?= te($r['unite']) ?>
                        <?php if ($r['unite'] !== 'kg'): ?><small class="d-block text-muted"><?= e(number_format((float) $r['quantiteKg'], 0, ',', ' ')) ?> kg</small><?php endif; ?>
                      </td>
                      <td class="text-end text-nowrap">
                        <?php if ($r['codeTracabilite']): ?>
                          <a href="etiquette.php?id=<?= e($r['id']) ?>" class="btn-icone" title="<?= te('Étiquette QR') ?>" target="_blank"><i class="bi bi-qr-code"></i></a>
                        <?php endif; ?>
                        <a href="modifierRecolte.php?id=<?= e($r['id']) ?>" class="btn-icone" title="<?= te('Modifier') ?>"><i class="bi bi-pencil"></i></a>
                        <form action="supprimerRecolte.php" method="POST" class="d-inline" data-confirm="<?= te('Supprimer cette récolte ?') ?>">
                          <?= champCsrf() ?>
                          <input type="hidden" name="id" value="<?= e($r['id']) ?>">
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

      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
