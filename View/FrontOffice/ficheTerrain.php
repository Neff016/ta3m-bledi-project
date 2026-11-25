<?php
/*
 * Fiche terrain imprimable (Imprimer → « Enregistrer en PDF »), sans bibliothèque
 * Infos, récoltes, rentabilité et dépenses.
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/DepenseController.php';
require_once __DIR__ . '/../../Controller/AnalyseController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$terrain = $id ? (new TerrainController())->getTerrainOfUser($id, $idUtilisateur) : false;
if (!$terrain) {
    setFlash(t("Ce terrain n'existe pas ou ne vous appartient pas."), 'danger');
    header('Location: mesTerrains.php');
    exit;
}
$recoltes = (new RecolteController())->listRecoltesByTerrain($id);
$depenses = (new DepenseController())->listParTerrain($id);
$rentab   = (new AnalyseController())->rentabilite($idUtilisateur, $id)['terrains'][0];
$kg       = array_sum(array_map(fn($r) => (float) $r['quantiteKg'], $recoltes));
[$lat, $lng, $exact] = TerrainController::position($terrain);
?>
<!DOCTYPE html>
<html lang="<?= langue() ?>" dir="<?= estRtl() ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="utf-8">
  <title><?= te('Fiche terrain') ?> — <?= e($terrain['nom']) ?></title>
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/fiche-impression.css" rel="stylesheet">
</head>
<body>
  <div class="barre-actions">
    <button type="button" onclick="window.print()"><i class="bi bi-printer"></i> <?= te('Imprimer / Enregistrer en PDF') ?></button>
    <a href="detailsTerrain.php?id=<?= e($id) ?>"><?= te('Retour') ?></a>
  </div>

  <main class="fiche">
    <header class="fiche-entete">
      <img src="assets/img/logo.png" alt="Taam Bledi">
      <div>
        <h1><?= e($terrain['nom']) ?></h1>
        <p><?= te('Fiche terrain éditée le {date}', ['date' => date('d/m/Y')]) ?></p>
      </div>
    </header>

    <section>
      <h2><?= te('Informations') ?></h2>
      <table class="infos">
        <tr><th><?= te('Wilaya') ?></th><td><?= e($terrain['localisation']) ?></td><th><?= te('Type de sol') ?></th><td><?= te($terrain['typeSol']) ?></td></tr>
        <tr><th><?= te('Superficie') ?></th><td><?= e($terrain['superficie']) ?> ha<?= $terrain['polygone'] ? ' (' . te('parcelle dessinée') . ')' : '' ?></td>
            <th><?= te('Position GPS') ?></th><td><?= $exact ? e(number_format($lat, 5) . ', ' . number_format($lng, 5)) : te('non renseignée') ?></td></tr>
        <tr><th><?= te('Production totale') ?></th><td><?= e(number_format($kg, 0, ',', ' ')) ?> kg</td>
            <th><?= te('Rendement') ?></th><td><?= e(number_format($kg / 1000 / max(0.01, (float) $terrain['superficie']), 2, ',', ' ')) ?> t/ha</td></tr>
        <tr><th><?= te("Chiffre d'affaires estimé") ?></th><td><?= e(number_format($rentab['revenu'], 3, ',', ' ')) ?> DT</td>
            <th><?= te('Bénéfice') ?></th><td><strong><?= e(number_format($rentab['benefice'], 3, ',', ' ')) ?> DT</strong></td></tr>
      </table>
    </section>

    <section>
      <h2><?= te('Récoltes') ?> (<?= count($recoltes) ?>)</h2>
      <?php if (empty($recoltes)): ?><p class="vide"><?= te('Aucune récolte.') ?></p><?php else: ?>
      <table>
        <thead><tr><th><?= te('Culture') ?></th><th><?= te('Plantation') ?></th><th><?= te('Récolte') ?></th><th><?= te('Quantité') ?></th><th><?= te('Code traçabilité') ?></th></tr></thead>
        <tbody>
          <?php foreach ($recoltes as $r): ?>
            <tr><td><?= e($r['culture']) ?></td>
                <td><?= e(date('d/m/Y', strtotime($r['datePlantation']))) ?></td><td><?= e(date('d/m/Y', strtotime($r['dateRecolte']))) ?></td>
                <td><?= e(RecolteController::nombreCourt((float) $r['quantite']) . ' ' . t($r['unite'])) ?></td><td class="code"><?= e($r['codeTracabilite']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </section>

    <section>
      <h2><?= te('Dépenses') ?> (<?= e(number_format($rentab['depenses'], 3, ',', ' ')) ?> DT)</h2>
      <?php if (empty($depenses)): ?><p class="vide"><?= te('Aucune dépense.') ?></p><?php else: ?>
      <table>
        <thead><tr><th><?= te('Date') ?></th><th><?= te('Catégorie') ?></th><th><?= te('Description') ?></th><th><?= te('Montant') ?></th></tr></thead>
        <tbody>
          <?php foreach ($depenses as $d): ?>
            <tr><td><?= e(date('d/m/Y', strtotime($d['dateDepense']))) ?></td><td><?= te($d['categorie']) ?></td><td><?= e($d['description'] ?? '') ?></td>
                <td><?= e(number_format((float) $d['montant'], 3, ',', ' ')) ?> DT</td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </section>

    <footer class="fiche-pied">Taam Bledi — <?= te('Document généré automatiquement') ?></footer>
  </main>
</body>
</html>
