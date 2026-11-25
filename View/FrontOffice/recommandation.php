<?php
/*
 * Recommandation : quelles cultures planter sur ce terrain cette saison ?
 * Score sur 100 (sol, saison, région, eau, historique, rotation) avec les raisons.
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/CultureController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$terrainC      = new TerrainController();
$terrains      = $terrainC->listTerrainsByUser($idUtilisateur);

$idTerrain = filter_input(INPUT_GET, 'terrain', FILTER_VALIDATE_INT) ?: ($terrains[0]['id'] ?? null);
$saison    = in_array($_GET['saison'] ?? '', CultureController::SAISONS, true) ? $_GET['saison'] : CultureController::saisonActuelle();
$terrain   = $idTerrain ? $terrainC->getTerrainOfUser((int) $idTerrain, $idUtilisateur) : false;
$erreur    = null;
if ($idTerrain && !$terrain) {
    $erreur = "Ce terrain n'existe pas ou ne vous appartient pas.";
}
$resultats = $terrain ? (new CultureController())->recommander($terrain, $saison) : [];

$titrePage  = 'Recommandation IA';
$pageActive = 'recommandation.php';
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">

        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= te('Moteur de recommandation') ?></p>
            <h1><?= te('Que planter ?') ?></h1>
          </div>
        </div>

        <?php if (empty($terrains)): ?>
          <div class="carte-terrain carte-vide">
            <i class="bi bi-stars"></i>
            <p class="mt-3 mb-4"><?= te('Ajoutez un terrain pour recevoir des recommandations.') ?></p>
            <div><a href="ajouterTerrain.php" class="btn-hero"><?= te('Ajouter un terrain') ?></a></div>
          </div>
        <?php else: ?>

        <form method="GET" class="tb-panneau mb-4" id="formRecommandation" novalidate>
          <div class="row g-3 align-items-end">
            <div class="col-md-5">
              <label for="terrain" class="form-label"><?= te('Terrain') ?></label>
              <select class="form-select" id="terrain" name="terrain">
                <?php foreach ($terrains as $t): ?>
                  <option value="<?= e($t['id']) ?>" <?= (int) $idTerrain === (int) $t['id'] ? 'selected' : '' ?>>
                    <?= e($t['nom']) ?> — <?= e($t['localisation']) ?>, <?= te($t['typeSol']) ?>, <?= e($t['superficie']) ?> ha
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="saison" class="form-label"><?= te('Saison de plantation') ?></label>
              <select class="form-select" id="saison" name="saison">
                <?php foreach (CultureController::SAISONS as $s): ?>
                  <option value="<?= e($s) ?>" <?= $saison === $s ? 'selected' : '' ?>><?= te($s) ?><?= $s === CultureController::saisonActuelle() ? ' (' . te('maintenant') . ')' : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn-hero border-0 w-100"><i class="bi bi-stars"></i> <?= te('Recommander') ?></button>
            </div>
          </div>
        </form>

        <?php if ($erreur): ?>
          <div class="alert alert-danger"><?= te($erreur) ?></div>
        <?php elseif ($terrain): ?>
          <p class="text-muted"><i class="bi bi-info-circle"></i>
            <?= te('Score sur 100 : sol (30), saison (25), région (20), besoin en eau (15), votre historique (10), rotation des cultures (bonus ou malus).') ?></p>

          <div class="row g-4">
            <?php foreach (array_slice($resultats, 0, 6) as $i => $r): ?>
              <?php $c = $r['culture']; $couleur = $r['score'] >= 75 ? 'excellent' : ($r['score'] >= 55 ? 'bon' : 'moyen'); ?>
              <div class="col-lg-4 col-md-6">
                <div class="tb-reco <?= $i === 0 ? 'meilleur' : '' ?>">
                  <?php if ($i === 0): ?><span class="tb-reco-ruban"><i class="bi bi-trophy"></i> <?= te('Meilleur choix') ?></span><?php endif; ?>
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="tb-score <?= $couleur ?>" style="--score: <?= (int) $r['score'] ?>"><span><?= (int) $r['score'] ?></span></div>
                    <div>
                      <h3><?= te($c['nom']) ?></h3>
                      <small class="text-muted"><?= te($c['categorie']) ?> · <?= te('{n} jours', ['n' => $c['dureeJours']]) ?> · <?= te('eau') ?> <?= te(CultureController::EAU[$c['besoinEau']]) ?></small>
                    </div>
                  </div>
                  <ul class="tb-raisons">
                    <?php foreach ($r['raisons'] as [$signe, $modele, $valeurs]): ?>
                      <?php
                        // Les listes (sols, saisons) et les mots sont traduits avant d'être placés dans la phrase
                        $valeurs = array_map(fn($v) => is_array($v) ? implode(', ', array_map(fn($x) => mb_strtolower(t($x)), $v))
                                                                    : (in_array($v, TerrainController::TYPES_SOL, true) || in_array($v, CultureController::EAU, true) ? mb_strtolower(t($v)) : $v), $valeurs);
                      ?>
                      <li class="<?= $signe === '+' ? 'plus' : 'moins' ?>"><i class="bi bi-<?= $signe === '+' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i> <?= te($modele, $valeurs) ?></li>
                    <?php endforeach; ?>
                  </ul>
                  <div class="tb-reco-chiffres">
                    <div><strong><?= e(number_format($r['productionKg'] / 1000, 1, ',', ' ')) ?> t</strong><span><?= te('production estimée') ?></span></div>
                    <div><strong><?= e(number_format($r['revenu'], 0, ',', ' ')) ?> DT</strong><span><?= te('revenu estimé') ?></span></div>
                  </div>
                  <p class="small mt-3 mb-3"><i class="bi bi-lightbulb text-warning"></i> <?= te($c['conseil']) ?></p>
                  <a href="consommationEau.php?terrain=<?= e($terrain['id']) ?>&amp;culture=<?= e(rawurlencode($c['nom'])) ?>" class="btn-hero w-100 text-center d-block">
                    <i class="bi bi-droplet"></i> <?= te('Calculer son besoin en eau') ?></a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <details class="tb-panneau mt-4">
            <summary class="fw-bold"><?= te('Voir le classement complet ({n} cultures)', ['n' => count($resultats)]) ?></summary>
            <div class="table-responsive mt-3">
              <table class="table table-sm align-middle tb-table">
                <thead><tr><th>#</th><th><?= te('Culture') ?></th><th><?= te('Score') ?></th><th><?= te('Eau') ?></th><th class="text-end"><?= te('Revenu estimé') ?></th></tr></thead>
                <tbody>
                  <?php foreach ($resultats as $i => $r): ?>
                    <tr><td><?= $i + 1 ?></td><td><?= te($r['culture']['nom']) ?></td>
                        <td><div class="tb-barre-score"><div style="width: <?= (int) $r['score'] ?>%"></div></div> <?= (int) $r['score'] ?></td>
                        <td><?= te(CultureController::EAU[$r['culture']['besoinEau']]) ?></td>
                        <td class="text-end"><?= e(number_format($r['revenu'], 0, ',', ' ')) ?> DT</td></tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </details>
        <?php endif; ?>
        <?php endif; ?>

      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
