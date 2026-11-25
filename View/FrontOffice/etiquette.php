<?php
/*
 * Étiquette QR d'une récolte à imprimer (sur une caisse ou un sac)
 * Le QR code mène à la page publique tracabilite.php?code=…
 * Le QR est dessiné dans le navigateur (assets/vendor/qrcode, sans Internet).
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/BadgeAutoController.php';
require_once __DIR__ . '/../../Controller/ControleQualiteController.php';
require_once __DIR__ . '/../../Controller/CertificationBioController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$idUtilisateur = requireLogin();
$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$recolte = $id ? (new RecolteController())->getRecolteOfUser($id, $idUtilisateur) : false;
if (!$recolte || !$recolte['codeTracabilite']) {
    setFlash(t("L'étiquette est disponible seulement pour une récolte terminée."), 'warning');
    header('Location: mesRecoltes.php');
    exit;
}

// Badge « Producteur traçable » à la première étiquette
$badges = (new BadgeAutoController())->verifier($idUtilisateur, true);

// Adresse complète de la page publique (avec le dossier du projet)
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$lien  = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'])
       . '/tracabilite.php?code=' . $recolte['codeTracabilite'];
$nombre = max(1, min(8, (int) ($_GET['n'] ?? 2)));
$qualite = (new ControleQualiteController())->getParRecolte($id);
$bio     = CertificationBioController::statutRecolte((new CertificationBioController())->getParTerrain((int) $recolte['idTerrain']), $recolte['dateRecolte']);
?>
<!DOCTYPE html>
<html lang="<?= langue() ?>" dir="<?= estRtl() ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="utf-8">
  <title><?= te('Étiquette QR') ?> — <?= e($recolte['culture']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/fiche-impression.css" rel="stylesheet">
</head>
<body>
  <div class="barre-actions">
    <button type="button" onclick="window.print()"><i class="bi bi-printer"></i> <?= te('Imprimer les étiquettes') ?></button>
    <a href="<?= e($lien) ?>" target="_blank"><?= te('Voir la page publique') ?></a>
    <a href="mesRecoltes.php"><?= te('Retour') ?></a>
  </div>
  <?php if ($badges): ?>
    <p class="reglage">🏅 <?= e(trim(BadgeAutoController::message($badges))) ?></p>
  <?php endif; ?>
  <form class="reglage" method="GET">
    <input type="hidden" name="id" value="<?= e($id) ?>">
    <label><?= te("Nombre d'étiquettes") ?>
      <select name="n" onchange="this.form.submit()">
        <?php foreach ([1, 2, 4, 6, 8] as $n): ?><option <?= $n === $nombre ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
      </select>
    </label>
  </form>

  <div class="etiquettes">
    <?php for ($i = 0; $i < $nombre; $i++): ?>
      <div class="etiquette">
        <p class="marque">Taam Bledi</p>
        <p class="produit"><?= e($recolte['culture']) ?></p>
        <?php if ($qualite || $bio): ?>
          <p class="mentions">
            <?php if ($qualite): ?><span><?= te(ControleQualiteController::CLASSES[$qualite['classe']]) ?><?= $qualite['calibre'] ? ' · ' . e($qualite['calibre']) : '' ?></span><?php endif; ?>
            <?php if ($bio === 'CERTIFIE'): ?><span class="bio">🌿 <?= te('Bio') ?></span>
            <?php elseif ($bio === 'CONVERSION'): ?><span><?= te('En conversion bio') ?></span><?php endif; ?>
          </p>
        <?php endif; ?>
        <p class="origine"><i class="bi bi-geo-alt"></i> <?= e($recolte['localisation']) ?> · <?= te('récolté le {date}', ['date' => date('d/m/Y', strtotime($recolte['dateRecolte']))]) ?></p>
        <div class="qr" data-lien="<?= e($lien) ?>"></div>
        <p class="code"><?= e($recolte['codeTracabilite']) ?></p>
        <p class="scan"><?= te('Scannez pour connaître l’origine du produit') ?></p>
      </div>
    <?php endfor; ?>
  </div>

  <script src="assets/vendor/qrcode/qrcode.js"></script>
  <script src="assets/js/qr.js"></script>
</body>
</html>
