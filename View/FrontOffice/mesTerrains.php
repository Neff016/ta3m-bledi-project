<?php
/*
 * Mes terrains : chiffres clés, carte de tous les terrains, cartes des terrains
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/ContratFoncierController.php';
require_once __DIR__ . '/../../Controller/CertificationBioController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();                 // pas connecté → page de connexion
$terrains      = (new TerrainController())->listTerrainsByUser($idUtilisateur);   // SEULEMENT ses terrains
$flash         = getFlash();
$contrats      = (new ContratFoncierController())->parTerrainDeUtilisateur($idUtilisateur);
$certifs       = (new CertificationBioController())->parTerrainDeUtilisateur($idUtilisateur);

$superficie   = array_sum(array_column($terrains, 'superficie'));
$production   = array_sum(array_column($terrains, 'productionKg'));
$geolocalises = count(array_filter($terrains, fn($t) => $t['latitude'] !== null));

// Données pour la carte (JSON lu par assets/js/carte-terrains.js)
$pointsCarte = [];
foreach ($terrains as $t) {
    [$lat, $lng, $exact] = TerrainController::position($t);
    $pointsCarte[] = [
        'id' => (int) $t['id'], 'nom' => $t['nom'], 'lat' => $lat, 'lng' => $lng, 'exact' => $exact,
        'polygone' => TerrainController::lirePolygone($t['polygone']),
        'info' => $t['localisation'] . ' · ' . $t['superficie'] . ' ha · ' . $t['typeSol'],
    ];
}

$titrePage  = 'Mes terrains';
$pageActive = 'mesTerrains.php';
$avecCarte  = true;
$jsSupp     = ['assets/js/carte-terrains.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">

        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= te('Agriculture intelligente') ?></p>
            <h1><?= te('Mes terrains') ?></h1>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="tableauDeBord.php" class="btn-hero btn-hero-clair"><i class="bi bi-speedometer2"></i> <?= te('Tableau de bord') ?></a>
            <a href="ajouterTerrain.php" class="btn-hero"><i class="bi bi-plus-lg"></i> <?= te('Ajouter un terrain') ?></a>
          </div>
        </div>

        <?php if ($flash): ?>
          <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (empty($terrains)): ?>
          <div class="carte-terrain carte-vide">
            <i class="bi bi-geo-alt"></i>
            <p class="mt-3 mb-4"><?= te("Vous n'avez encore aucun terrain.") ?></p>
            <div><a href="ajouterTerrain.php" class="btn-hero"><?= te('Ajouter mon premier terrain') ?></a></div>
          </div>
        <?php else: ?>

          <!-- Chiffres clés -->
          <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-geo-alt"></i><strong><?= count($terrains) ?></strong><span><?= te('Terrains') ?></span></div></div>
            <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-rulers"></i><strong><?= e(number_format($superficie, 2, ',', ' ')) ?> ha</strong><span><?= te('Superficie totale') ?></span></div></div>
            <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-basket"></i><strong><?= e(number_format($production / 1000, 2, ',', ' ')) ?> t</strong><span><?= te('Production totale') ?></span></div></div>
            <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-pin-map"></i><strong><?= $geolocalises ?> / <?= count($terrains) ?></strong><span><?= te('Terrains sur la carte') ?></span></div></div>
          </div>

          <!-- Carte de tous mes terrains -->
          <div class="tb-panneau mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
              <h2 class="tb-titre-panneau mb-0"><i class="bi bi-map"></i> <?= te('Mes terrains sur la carte') ?></h2>
              <?php if ($geolocalises < count($terrains)): ?>
                <small class="text-muted"><i class="bi bi-info-circle"></i> <?= te('Les terrains sans position sont placés au centre de leur wilaya : modifiez-les pour les placer exactement.') ?></small>
              <?php endif; ?>
            </div>
            <div id="carteTerrains" class="tb-carte" data-points='<?= e(json_encode($pointsCarte)) ?>'
                 data-texte-details="<?= te('Voir les détails') ?>" data-texte-approx="<?= te('Position approximative (centre de la wilaya)') ?>"></div>
          </div>

          <!-- Cartes des terrains -->
          <div class="row g-4">
            <?php foreach ($terrains as $t): ?>
              <div class="col-lg-4 col-md-6">
                <div class="carte-terrain tb-carte-terrain">
                  <div class="tb-photo-terrain" <?php if ($t['photo']): ?>style="background-image:url('assets/img/uploads/terrains/<?= e($t['photo']) ?>')"<?php endif; ?>>
                    <?php if (!$t['photo']): ?><i class="bi bi-image"></i><?php endif; ?>
                    <span class="badge-sol"><?= te($t['typeSol']) ?></span>
                    <?php $sb = CertificationBioController::statut($certifs[(int) $t['id']] ?? null); ?>
                    <?php if ($sb === 'CERTIFIE' || $sb === 'CONVERSION'): ?>
                      <span class="badge-bio <?= $sb === 'CONVERSION' ? 'conversion' : '' ?>"><i class="bi bi-<?= $sb === 'CERTIFIE' ? 'flower1' : 'hourglass-split' ?>"></i> <?= te($sb === 'CERTIFIE' ? 'Bio' : 'Conversion') ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="tb-corps-terrain">
                    <h3><?= e($t['nom']) ?></h3>
                    <?php if (isset($contrats[(int) $t['id']])): ?>
                      <p class="small text-muted mb-1"><i class="bi bi-file-earmark-text"></i> <?= te(ContratFoncierController::TYPES[$contrats[(int) $t['id']]['type']]) ?></p>
                    <?php endif; ?>
                    <p class="sous-titre">
                      <?php if ((int) $t['nbRecoltes'] === 0): ?>
                        <?= te('Aucune récolte') ?>
                      <?php else: ?>
                        <?= te('{n} récolte(s)', ['n' => $t['nbRecoltes']]) ?> · <?= e(number_format($t['productionKg'] / 1000, 2, ',', ' ')) ?> t
                      <?php endif; ?>
                    </p>
                    <ul>
                      <li><i class="bi bi-geo-alt"></i> <?= e($t['localisation']) ?>
                        <?php if ($t['latitude'] !== null): ?><span class="tb-gps" title="GPS"><i class="bi bi-crosshair"></i></span><?php endif; ?>
                      </li>
                      <li><i class="bi bi-rulers"></i> <?= e($t['superficie']) ?> ha
                        <?php if ($t['polygone']): ?><small class="text-muted">(<?= te('parcelle dessinée') ?>)</small><?php endif; ?>
                      </li>
                    </ul>
                    <div class="pied">
                      <a href="detailsTerrain.php?id=<?= e($t['id']) ?>" class="lien-details"><?= te('Voir les détails') ?></a>
                      <span class="actions-mini">
                        <a href="ficheTerrain.php?id=<?= e($t['id']) ?>" title="<?= te('Fiche PDF') ?>"><i class="bi bi-file-earmark-pdf"></i></a>
                        <a href="modifierTerrain.php?id=<?= e($t['id']) ?>" title="<?= te('Modifier') ?>"><i class="bi bi-pencil"></i></a>
                        <form action="supprimerTerrain.php" method="POST" class="d-inline"
                              data-confirm="<?= te('Supprimer ce terrain et toutes ses récoltes ?') ?>">
                          <?= champCsrf() ?>
                          <input type="hidden" name="id" value="<?= e($t['id']) ?>">
                          <button type="submit" class="btn-icone danger" title="<?= te('Supprimer') ?>"><i class="bi bi-trash"></i></button>
                        </form>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
