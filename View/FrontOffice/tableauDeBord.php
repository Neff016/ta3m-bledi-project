<?php
/*
 * Tableau de bord de l'agriculteur : production, rendement, prévisions,
 * comparaison régionale anonyme et rentabilité (graphiques SVG)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/AnalyseController.php';
require_once __DIR__ . '/../../Controller/MeteoController.php';
require_once __DIR__ . '/../../Controller/ContratFoncierController.php';
require_once __DIR__ . '/../../Controller/CertificationBioController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$terrains      = (new TerrainController())->listTerrainsByUser($idUtilisateur);
$a             = (new AnalyseController())->tableauDeBord($idUtilisateur);
$flash         = getFlash();

// Alertes météo de tous les terrains (positions arrondies : un seul appel par zone)
$alertes = [];
$zonesVues = [];
foreach ($terrains as $t) {
    [$lat, $lng] = TerrainController::position($t);
    $zone = round($lat, 1) . ',' . round($lng, 1);
    if (isset($zonesVues[$zone])) {
        continue;
    }
    $zonesVues[$zone] = true;
    $meteo = (new MeteoController())->previsions($lat, $lng);
    if ($meteo['ok']) {
        foreach (MeteoController::alertes(array_slice($meteo['jours'], 0, 3)) as $al) {
            $alertes[] = [$t['nom'], $al];
        }
    }
}

$superficie = array_sum(array_column($terrains, 'superficie'));
$rentab     = $a['rentabilite'];
$benefice   = $rentab['totalRevenu'] - $rentab['totalDepenses'] - $rentab['totalFoncier'];
$finsContrat = (new ContratFoncierController())->alertesFin($idUtilisateur);
$alertesBio  = (new CertificationBioController())->alertes($idUtilisateur);
$annee      = (int) date('Y');

$titrePage  = 'Tableau de bord';
$pageActive = 'tableauDeBord.php';
$jsSupp     = ['assets/js/graphiques.js'];
require __DIR__ . '/partials/haut.php';
?>
    <section class="page-terrains">
      <div class="container">

        <div class="hero-terrains">
          <div>
            <p class="sur-titre"><?= te('Bonjour {prenom}', ['prenom' => $_SESSION['prenom'] ?? '']) ?></p>
            <h1><?= te('Tableau de bord') ?></h1>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="ajouterRecolte.php" class="btn-hero btn-hero-clair"><i class="bi bi-mic"></i> <?= te('Nouvelle récolte') ?></a>
            <a href="recommandation.php" class="btn-hero"><i class="bi bi-stars"></i> <?= te('Que planter ?') ?></a>
          </div>
        </div>

        <?php if ($flash): ?>
          <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php foreach ($finsContrat as $c): ?>
          <div class="alert alert-<?= $c['joursRestants'] < 0 ? 'danger' : 'warning' ?> d-flex gap-2 align-items-center"><i class="bi bi-calendar-x fs-5"></i>
            <span><strong><?= e($c['terrainNom']) ?> :</strong>
              <?= $c['joursRestants'] < 0 ? te('Le contrat foncier de ce terrain est terminé depuis le {d}.', ['d' => date('d/m/Y', strtotime($c['dateFin']))])
                                          : te('Le contrat foncier se termine dans {n} jour(s), le {d} : pensez à le renouveler.', ['n' => $c['joursRestants'], 'd' => date('d/m/Y', strtotime($c['dateFin']))]) ?>
              <a href="detailsTerrain.php?id=<?= e($c['idTerrain']) ?>&amp;onglet=foncier" class="alert-link"><?= te('Voir le contrat') ?></a></span></div>
        <?php endforeach; ?>
        <?php foreach ($alertesBio as [$type, $icone, $texte, $valeurs]): ?>
          <div class="alert alert-<?= e($type) ?> d-flex gap-2 align-items-center"><i class="bi <?= e($icone) ?> fs-5"></i> <span><?= te($texte, $valeurs) ?></span></div>
        <?php endforeach; ?>

        <?php foreach (array_slice($alertes, 0, 3) as [$nomTerrain, [$type, $icone, $texte, $valeurs]]): ?>
          <div class="alert alert-<?= e($type) ?> d-flex gap-2 align-items-center"><i class="bi <?= e($icone) ?> fs-5"></i>
            <span><strong><?= e($nomTerrain) ?> :</strong> <?= te($texte, $valeurs) ?></span></div>
        <?php endforeach; ?>

        <?php if (empty($terrains)): ?>
          <div class="carte-terrain carte-vide">
            <i class="bi bi-speedometer2"></i>
            <p class="mt-3 mb-4"><?= te('Ajoutez votre premier terrain pour voir vos statistiques.') ?></p>
            <div><a href="ajouterTerrain.php" class="btn-hero"><?= te('Ajouter un terrain') ?></a></div>
          </div>
        <?php else: ?>

        <!-- Chiffres clés -->
        <div class="row g-3 mb-4">
          <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-box-seam"></i><strong><?= e(number_format($a['kgAnnee'] / 1000, 2, ',', ' ')) ?> t</strong><span><?= te('Récolté en {annee}', ['annee' => $annee]) ?></span></div></div>
          <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-graph-up-arrow"></i><strong><?= e(number_format($a['kgTotal'] / 1000 / max(0.01, $superficie), 2, ',', ' ')) ?> t/ha</strong><span><?= te('Rendement moyen') ?></span></div></div>
          <div class="col-6 col-lg-3"><div class="tb-kpi"><i class="bi bi-cash-coin"></i><strong><?= e(number_format($rentab['totalRevenu'], 0, ',', ' ')) ?> DT</strong><span><?= te("Chiffre d'affaires estimé") ?></span></div></div>
          <div class="col-6 col-lg-3"><div class="tb-kpi <?= $benefice < 0 ? 'negatif' : 'positif' ?>"><i class="bi bi-piggy-bank"></i><strong><?= e(number_format($benefice, 0, ',', ' ')) ?> DT</strong><span><?= te('Bénéfice estimé') ?></span></div></div>
        </div>

        <div class="row g-4">
          <!-- Production des 12 derniers mois -->
          <div class="col-lg-8">
            <div class="tb-panneau h-100">
              <h2 class="tb-titre-panneau"><?= te('Production des 12 derniers mois (kg)') ?></h2>
              <div class="tb-graphique" data-type="barres" data-unite="kg"
                   data-libelles='<?= e(json_encode($a['parMois']['libelles'], JSON_UNESCAPED_UNICODE)) ?>'
                   data-series='<?= e(json_encode([['nom' => t('Production'), 'valeurs' => $a['parMois']['valeurs'], 'couleur' => '#008a17']], JSON_UNESCAPED_UNICODE)) ?>'></div>
            </div>
          </div>
          <!-- Répartition par culture -->
          <div class="col-lg-4">
            <div class="tb-panneau h-100">
              <h2 class="tb-titre-panneau"><?= te('Répartition par culture') ?></h2>
              <div class="tb-graphique" data-type="anneau" data-unite="kg"
                   data-libelles='<?= e(json_encode(array_keys($a['parCulture']), JSON_UNESCAPED_UNICODE)) ?>'
                   data-series='<?= e(json_encode([['nom' => 'kg', 'valeurs' => array_values($a['parCulture'])]], JSON_UNESCAPED_UNICODE)) ?>'></div>
            </div>
          </div>

          <!-- Année en cours / année précédente -->
          <div class="col-lg-7">
            <div class="tb-panneau h-100">
              <h2 class="tb-titre-panneau"><?= te('{n} comparé à {p} (kg)', ['n' => $annee, 'p' => $annee - 1]) ?></h2>
              <div class="tb-graphique" data-type="barres" data-unite="kg"
                   data-libelles='<?= e(json_encode(array_map('t', AnalyseController::MOIS), JSON_UNESCAPED_UNICODE)) ?>'
                   data-series='<?= e(json_encode([['nom' => (string) ($annee - 1), 'valeurs' => $a['anneeN1'], 'couleur' => '#6a4ce2'],
                                                   ['nom' => (string) $annee, 'valeurs' => $a['anneeN'], 'couleur' => '#008a17']])) ?>'></div>
            </div>
          </div>
          <!-- Rendement par terrain -->
          <div class="col-lg-5">
            <div class="tb-panneau h-100">
              <h2 class="tb-titre-panneau"><?= te('Rendement par terrain (12 mois)') ?></h2>
              <div class="tb-graphique" data-type="barresH" data-unite="t/ha"
                   data-libelles='<?= e(json_encode(array_column($a['rendements'], 'nom'), JSON_UNESCAPED_UNICODE)) ?>'
                   data-series='<?= e(json_encode([['nom' => 't/ha', 'valeurs' => array_column($a['rendements'], 'tha'), 'couleur' => '#008a17']])) ?>'></div>
            </div>
          </div>

          <!-- Prévisions -->
          <div class="col-lg-6">
            <div class="tb-panneau h-100">
              <h2 class="tb-titre-panneau"><i class="bi bi-magic"></i> <?= te('Prévision des prochaines récoltes') ?></h2>
              <?php if (empty($a['previsions'])): ?>
                <p class="text-muted"><?= te('Enregistrez au moins une récolte terminée pour obtenir une prévision.') ?></p>
              <?php else: ?>
                <?php foreach ($a['previsions'] as $p): ?>
                  <div class="tb-prevision">
                    <i class="bi bi-<?= $p['tendance'] === 'hausse' ? 'arrow-up-right text-success' : ($p['tendance'] === 'baisse' ? 'arrow-down-right text-danger' : 'arrow-right text-secondary') ?>"></i>
                    <div class="flex-grow-1">
                      <strong><?= te($p['culture']) ?></strong> <small class="text-muted">(<?= e($p['terrain']) ?>)</small> :
                      <?= te('environ {q} vers le {date}', [
                          'q'    => $p['kg'] >= 1000 ? number_format($p['kg'] / 1000, 1, ',', ' ') . ' t' : number_format($p['kg'], 0, ',', ' ') . ' kg',
                          'date' => date('d/m/Y', strtotime($p['date']))]) ?>
                      <small class="d-block text-muted"><?= te('Date {source} · fiabilité {f} · tendance {t}', [
                          'source' => t($p['source']), 'f' => t($p['fiabilite']), 't' => t($p['tendance'])]) ?></small>
                    </div>
                  </div>
                <?php endforeach; ?>
                <p class="small text-muted mt-2 mb-0"><i class="bi bi-info-circle"></i> <?= te('Calcul : droite de régression sur vos récoltes passées (méthode des moindres carrés).') ?></p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Comparaison régionale -->
          <div class="col-lg-6">
            <div class="tb-panneau h-100">
              <h2 class="tb-titre-panneau"><i class="bi bi-people"></i> <?= te('Comparaison avec votre région') ?></h2>
              <?php if (empty($a['comparaisons'])): ?>
                <p class="text-muted"><?= te('Pas encore assez de récoltes pour comparer.') ?></p>
              <?php else: ?>
                <?php foreach ($a['comparaisons'] as $c): ?>
                  <div class="tb-comparaison">
                    <span class="tb-ecart <?= $c['ecart'] >= 0 ? 'positif' : 'negatif' ?>"><?= $c['ecart'] >= 0 ? '+' : '' ?><?= $c['ecart'] ?> %</span>
                    <div>
                      <?= te('Votre rendement de {culture} à {wilaya} est {sens} la {base}.', [
                          'culture' => $c['culture'], 'wilaya' => $c['wilaya'],
                          'sens'    => t($c['ecart'] >= 0 ? 'au-dessus de' : 'en dessous de'), 'base' => t($c['base'][0], $c['base'][1])]) ?>
                      <small class="d-block text-muted"><?= e(number_format($c['moi'], 2, ',', ' ')) ?> t/ha · <?= te('référence') ?> <?= e(number_format($c['reference'], 2, ',', ' ')) ?> t/ha</small>
                    </div>
                  </div>
                <?php endforeach; ?>
                <p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-lock"></i> <?= te('Comparaison anonyme : aucun nom d’agriculteur n’est utilisé.') ?></p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Rentabilité par terrain -->
          <div class="col-12">
            <div class="tb-panneau">
              <h2 class="tb-titre-panneau"><i class="bi bi-cash-stack"></i> <?= te('Rentabilité par terrain') ?></h2>
              <?php if ($rentab['meilleure']): ?>
                <p><i class="bi bi-trophy text-warning"></i> <?= te('Votre culture la plus rentable : {c} ({v} DT par hectare).', [
                    'c' => $rentab['meilleure']['culture'], 'v' => number_format($rentab['meilleure']['parHa'], 0, ',', ' ')]) ?></p>
              <?php endif; ?>
              <div class="table-responsive">
                <table class="table align-middle tb-table">
                  <thead><tr><th><?= te('Terrain') ?></th><th class="text-end"><?= te("Chiffre d'affaires") ?></th><th class="text-end"><?= te('Dépenses') ?></th><th class="text-end"><?= te('Loyer ou part du propriétaire') ?></th><th class="text-end"><?= te('Bénéfice') ?></th><th></th></tr></thead>
                  <tbody>
                    <?php foreach ($rentab['terrains'] as $r): ?>
                      <tr>
                        <td><strong><?= e($r['nom']) ?></strong></td>
                        <td class="text-end"><?= e(number_format($r['revenu'], 0, ',', ' ')) ?> DT</td>
                        <td class="text-end"><?= e(number_format($r['depenses'], 0, ',', ' ')) ?> DT</td>
                        <td class="text-end"><?= $r['foncier'] > 0 ? e(number_format($r['foncier'], 0, ',', ' ')) . ' DT' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-end fw-bold <?= $r['benefice'] < 0 ? 'text-danger' : 'text-success' ?>"><?= e(number_format($r['benefice'], 0, ',', ' ')) ?> DT</td>
                        <td class="text-end"><a href="detailsTerrain.php?id=<?= e($r['id']) ?>&amp;onglet=depenses" class="tb-lien"><?= te('Détails') ?></a></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </section>
<?php require __DIR__ . '/partials/bas.php'; ?>
