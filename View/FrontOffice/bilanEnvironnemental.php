<?php
/*
 * Bilan environnemental de mes récoltes : empreinte eau (L/kg), carbone (kg CO₂e/kg), note de A à E
 * (calcul : EnvironnementController ; pas de table, tout est recalculé à partir des récoltes et des dépenses)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/EnvironnementController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$terrains      = (new TerrainController())->listTerrainsByUser($idUtilisateur);
$idTerrain     = filter_input(INPUT_GET, 'terrain', FILTER_VALIDATE_INT) ?: null;
if ($idTerrain && !in_array($idTerrain, array_map('intval', array_column($terrains, 'id')), true)) {
    $idTerrain = null;                                   // terrain d'un autre utilisateur : ignoré
}
$lignes = (new EnvironnementController())->bilansUtilisateur($idUtilisateur, $idTerrain);

// Moyennes pondérées par la quantité récoltée
$kgTotal = 0; $litres = 0; $kgEau = 0; $co2 = 0; $kgCo2 = 0; $notes = [];
$parCulture = [];
foreach ($lignes as $l) {
    $kg = (float) $l['quantiteKg'];
    $kgTotal += $kg;
    if ($l['bilan']['eau']) {
        $litres += $l['bilan']['eau']['litresParKg'] * $kg;
        $kgEau += $kg;
        $parCulture[$l['culture']][] = $l['bilan']['eau']['litresParKg'];
    }
    if ($l['bilan']['carbone']) {
        $co2 += $l['bilan']['carbone']['total'];
        $kgCo2 += $kg;
    }
    if ($l['bilan']['note']) {
        $notes[] = $l['bilan']['note'];
    }
}
$eauMoyenne = $kgEau > 0 ? $litres / $kgEau : null;
$co2Moyen   = $kgCo2 > 0 ? $co2 / $kgCo2 : null;
$noteMoyenne = $notes ? EnvironnementController::NOTES[(int) round(array_sum(array_map(fn($n) => array_search($n, EnvironnementController::NOTES, true), $notes)) / count($notes))] : null;
$bonnes = count(array_filter($notes, fn($n) => in_array($n, ['A', 'B'], true)));
$moyennesCulture = array_map(fn($v) => round(array_sum($v) / count($v)), $parCulture);
arsort($moyennesCulture);

$titrePage  = 'Bilan environnemental';
$pageActive = 'bilanEnvironnemental.php';
$jsSupp     = ['assets/js/graphiques.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">

        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= te('Agriculture durable') ?></p>
            <h1><?= te('Bilan environnemental') ?></h1>
          </div>
          <form method="GET" class="d-flex gap-2 align-items-center" novalidate>
            <label for="terrain" class="visually-hidden"><?= te('Terrain') ?></label>
            <select id="terrain" name="terrain" class="form-select" onchange="this.form.submit()">
              <option value=""><?= te('Tous mes terrains') ?></option>
              <?php foreach ($terrains as $t): ?>
                <option value="<?= e($t['id']) ?>" <?= $idTerrain === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nom']) ?></option>
              <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn-hero"><?= te('Filtrer') ?></button></noscript>
          </form>
        </div>

        <?php if (empty($lignes)): ?>
          <div class="carte-terrain carte-vide">
            <i class="bi bi-globe2"></i>
            <p class="mt-3 mb-4"><?= te('Ajoutez des récoltes pour calculer leur bilan environnemental.') ?></p>
            <div><a href="ajouterRecolte.php" class="btn-hero"><?= te('Ajouter une récolte') ?></a></div>
          </div>
        <?php else: ?>

        <div class="row g-3 mb-4">
          <div class="col-6 col-lg-3"><div class="tb-kpi">
            <span class="tb-note-env tb-note-grande tb-note-<?= strtolower(e($noteMoyenne ?? 'x')) ?>"><?= e($noteMoyenne ?? '?') ?></span>
            <strong><?= te('Note moyenne') ?></strong><span><?= te('{n} récolte(s) notée(s) A ou B sur {t}', ['n' => $bonnes, 't' => count($notes)]) ?></span></div></div>
          <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-droplet"></i>
            <strong><?= $eauMoyenne !== null ? e(number_format($eauMoyenne, 0, ',', ' ')) . ' L' : '—' ?></strong><span><?= te('d’eau par kg récolté') ?></span></div></div>
          <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-cloud-haze2"></i>
            <strong><?= $co2Moyen !== null ? e(number_format($co2Moyen, 2, ',', ' ')) . ' kg' : '—' ?></strong><span><?= te('de CO₂e par kg récolté') ?></span></div></div>
          <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-box-seam"></i>
            <strong><?= e(number_format($kgTotal / 1000, 2, ',', ' ')) ?> t</strong><span><?= te('récoltées au total') ?></span></div></div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-lg-6">
            <div class="tb-panneau h-100">
              <h2 class="tb-titre-panneau"><?= te('Empreinte eau moyenne par culture (L/kg)') ?></h2>
              <?php if ($moyennesCulture): ?>
                <div class="tb-graphique" data-type="barresH" data-unite="L/kg"
                     data-libelles='<?= e(json_encode(array_map('t', array_keys($moyennesCulture)), JSON_UNESCAPED_UNICODE)) ?>'
                     data-series='<?= e(json_encode([['nom' => t('Litres par kg'), 'valeurs' => array_values($moyennesCulture), 'couleur' => '#008a17']], JSON_UNESCAPED_UNICODE)) ?>'></div>
              <?php else: ?>
                <p class="text-muted"><?= te('Aucune culture du catalogue : l’empreinte eau ne peut pas être calculée.') ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="tb-panneau h-100">
              <h2 class="tb-titre-panneau"><?= te('Comment est calculée la note ?') ?></h2>
              <ul class="small mb-2">
                <li><?= te('Eau : besoin de la culture (ET0 × kc, méthode FAO-56) pendant sa durée réelle, divisé par la quantité récoltée. La note compare votre rendement au rendement moyen en Tunisie : meilleur rendement = moins d’eau par kg.') ?></li>
                <li><?= te('Carbone : dépenses du terrain pendant la culture (engrais, carburant et matériel, pompage, traitements) × un facteur d’émission moyen par dinar.') ?></li>
                <li><?= te('Note globale : moyenne des deux notes, arrondie vers la moins bonne.') ?></li>
              </ul>
              <div class="d-flex gap-1 mb-2" aria-hidden="true">
                <?php foreach (EnvironnementController::NOTES as $n): ?><span class="tb-note-env tb-note-<?= strtolower($n) ?>"><?= $n ?></span><?php endforeach; ?>
              </div>
              <p class="small text-muted mb-0"><i class="bi bi-info-circle"></i> <?= te('Ordre de grandeur pour comparer vos récoltes entre elles, pas une certification. Notez vos dépenses pour un bilan carbone complet.') ?></p>
            </div>
          </div>
        </div>

        <div class="tb-panneau">
          <h2 class="tb-titre-panneau"><?= te('Détail par récolte') ?></h2>
          <div class="table-responsive">
            <table class="table align-middle tb-table">
              <thead>
                <tr><th><?= te('Culture') ?></th><th><?= te('Terrain') ?></th><th><?= te('Récolte') ?></th>
                    <th class="text-end"><?= te('Rendement') ?></th><th class="text-end"><?= te('Eau (L/kg)') ?></th>
                    <th class="text-end"><?= te('CO₂e (kg/kg)') ?></th><th class="text-center"><?= te('Note') ?></th></tr>
              </thead>
              <tbody>
                <?php foreach ($lignes as $l): $b = $l['bilan']; ?>
                  <tr>
                    <td><strong><?= e($l['culture']) ?></strong></td>
                    <td><?= e($l['terrainNom']) ?><?= $l['parcelleNom'] ? ' <small class="text-muted">· ' . e($l['parcelleNom']) . '</small>' : '' ?></td>
                    <td><?= e(date('d/m/Y', strtotime($l['dateRecolte']))) ?></td>
                    <td class="text-end">
                      <?php if ($b['eau']): ?>
                        <?= e(CultureController::nombre($b['eau']['rendement'], 2)) ?> t/ha
                        <small class="d-block text-muted"><?= te('moyenne : {r}', ['r' => CultureController::nombre($b['eau']['rendementRef'], 2)]) ?></small>
                      <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-end">
                      <?php if ($b['eau']): ?>
                        <?= e(number_format($b['eau']['litresParKg'], 0, ',', ' ')) ?> <span class="tb-note-env tb-note-petite tb-note-<?= strtolower($b['eau']['note']) ?>"><?= e($b['eau']['note']) ?></span>
                        <small class="d-block text-muted"><?= te('dont {p} % d’irrigation', ['p' => $b['eau']['partIrrigation']]) ?></small>
                      <?php else: ?><small class="text-muted"><?= te('culture hors catalogue') ?></small><?php endif; ?>
                    </td>
                    <td class="text-end">
                      <?php if ($b['carbone']): ?>
                        <?= e(number_format($b['carbone']['parKg'], 2, ',', ' ')) ?> <span class="tb-note-env tb-note-petite tb-note-<?= strtolower($b['carbone']['note']) ?>"><?= e($b['carbone']['note']) ?></span>
                        <small class="d-block text-muted"><?= te('{t} kg au total', ['t' => number_format($b['carbone']['total'], 0, ',', ' ')]) ?></small>
                      <?php else: ?><small class="text-muted"><?= te('aucune dépense notée') ?></small><?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php if ($b['note']): ?>
                        <span class="tb-note-env tb-note-<?= strtolower($b['note']) ?>" title="<?= te($b['complet'] ? 'Note complète' : 'Note partielle') ?>"><?= e($b['note']) ?></span>
                        <?php if (!$b['complet']): ?><small class="d-block text-muted"><?= te('partielle') ?></small><?php endif; ?>
                      <?php else: ?>—<?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <?php endif; ?>
      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
