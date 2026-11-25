<?php
/*
 * Page PUBLIQUE de traçabilité « De la ferme à la table » (ouverte en scannant le QR code)
 * Pas besoin d'être connecté. On montre l'origine du produit, sans données privées
 * (pas d'email, pas de téléphone, nom de famille abrégé).
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/CultureController.php';
require_once __DIR__ . '/../../Controller/ControleQualiteController.php';
require_once __DIR__ . '/../../Controller/CertificationBioController.php';
require_once __DIR__ . '/../../Controller/EnvironnementController.php';
require_once __DIR__ . '/../../Controller/Langue.php';

$code    = strtoupper(trim((string) ($_GET['code'] ?? '')));
$produit = (new RecolteController())->getParCode($code);

$culture = null;
if ($produit) {
    $culture = (new CultureController())->trouver($produit['culture']);
    [$lat, $lng, $exact] = TerrainController::position($produit);
    // La parcelle précise si elle est dessinée, sinon le contour du terrain
    $parcelle = TerrainController::lirePolygone($produit['parcellePolygone']) ?? TerrainController::lirePolygone($produit['polygone']);
    $qualite  = (new ControleQualiteController())->getParRecolte((int) $produit['id']);
    $certif   = (new CertificationBioController())->getParTerrain((int) $produit['idTerrain']);
    $bio      = CertificationBioController::statutRecolte($certif, $produit['dateRecolte']);
    $bilan    = (new EnvironnementController())->bilan($produit);
    $duree    = (int) round((strtotime($produit['dateRecolte']) - strtotime($produit['datePlantation'])) / 86400);
}
?>
<!DOCTYPE html>
<html lang="<?= langue() ?>" dir="<?= estRtl() ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= $produit ? e($produit['culture']) . ' — ' : '' ?><?= te('Traçabilité') ?> · Taam Bledi</title>
  <meta name="robots" content="noindex">
  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Marcellus&family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/<?= estRtl() ? 'bootstrap.rtl.min.css' : 'bootstrap.min.css' ?>" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/terrains-avance.css" rel="stylesheet">
</head>
<body class="tb-public <?= estRtl() ? 'rtl' : '' ?>">
  <header class="tb-public-entete">
    <a href="index.html"><img src="assets/img/logo.png" alt="Taam Bledi"></a>
    <span><i class="bi bi-patch-check-fill"></i> <?= te('Produit traçable') ?></span>
    <a class="tb-public-langue" href="langue.php?l=<?= estRtl() ? 'fr' : 'ar' ?>&amp;retour=<?= rawurlencode('tracabilite.php?code=' . $code) ?>"><?= estRtl() ? 'FR' : 'ع' ?></a>
  </header>

  <main class="container tb-public-contenu">
    <?php if (!$produit): ?>
      <div class="tb-panneau text-center py-5">
        <i class="bi bi-qr-code-scan display-3 text-muted"></i>
        <h1 class="h3 mt-3"><?= te('Code introuvable') ?></h1>
        <p class="text-muted"><?= te('Ce code de traçabilité ne correspond à aucun produit. Vérifiez l’étiquette.') ?></p>
      </div>
    <?php else: ?>
      <div class="tb-public-produit">
        <div class="tb-public-photo" <?php if ($produit['photo']): ?>style="background-image:url('assets/img/uploads/recoltes/<?= e($produit['photo']) ?>')"<?php endif; ?>>
          <?php if (!$produit['photo']): ?><i class="bi bi-flower1"></i><?php endif; ?>
        </div>
        <div class="p-4">
          <p class="tb-public-sur-titre"><?= te('De la ferme à la table') ?></p>
          <h1><?= e($produit['culture']) ?></h1>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php if ($culture): ?><span class="badge text-bg-success"><?= te($culture['categorie']) ?></span><?php endif; ?>
            <?php if ($qualite): ?><span class="tb-classe tb-classe-<?= strtolower(e($qualite['classe'])) ?>"><?= te(ControleQualiteController::CLASSES[$qualite['classe']]) ?></span><?php endif; ?>
            <?php if ($bio === 'CERTIFIE'): ?><span class="tb-classe tb-classe-bio"><i class="bi bi-flower1"></i> <?= te('Agriculture biologique') ?></span>
            <?php elseif ($bio === 'CONVERSION'): ?><span class="tb-classe tb-classe-conversion"><i class="bi bi-hourglass-split"></i> <?= te('En conversion vers l’agriculture biologique') ?></span><?php endif; ?>
            <?php if ($bilan['note']): ?><span class="tb-note-env tb-note-<?= strtolower($bilan['note']) ?>" title="<?= te('Bilan environnemental') ?>"><?= e($bilan['note']) ?></span><?php endif; ?>
          </div>
          <ul class="tb-public-infos">
            <li><i class="bi bi-person"></i> <?= te('Cultivé par {nom}', ['nom' => $produit['prenom'] . ' ' . mb_substr($produit['nom'], 0, 1) . '.']) ?></li>
            <li><i class="bi bi-geo-alt"></i> <?= e($produit['terrainNom']) ?><?= $produit['parcelleNom'] ? ' (' . e($produit['parcelleNom']) . ')' : '' ?> — <?= e($produit['localisation']) ?>, <?= te('Tunisie') ?></li>
            <?php if ($bio && $certif): ?>
              <li><i class="bi bi-patch-check"></i> <?= te('Certifié par {o}', ['o' => $certif['organisme']]) ?><?= $certif['numeroCertificat'] ? ' · ' . e($certif['numeroCertificat']) : '' ?></li>
            <?php endif; ?>
            <li><i class="bi bi-layers"></i> <?= te('Sol {sol}', ['sol' => mb_strtolower(t($produit['typeSol']))]) ?></li>
            <li><i class="bi bi-upc"></i> <?= te('Lot n° {code}', ['code' => $code]) ?></li>
          </ul>
        </div>
      </div>

      <div class="row g-4 mt-1">
        <div class="col-lg-6">
          <div class="tb-panneau h-100">
            <h2 class="tb-titre-panneau"><i class="bi bi-clock-history"></i> <?= te('Parcours du produit') ?></h2>
            <ul class="tb-frise">
              <li><span class="tb-frise-icone"><i class="bi bi-flower1"></i></span>
                <div class="tb-frise-contenu"><strong><?= te('Plantation') ?></strong><br><?= e(date('d/m/Y', strtotime($produit['datePlantation']))) ?></div></li>
              <li><span class="tb-frise-icone"><i class="bi bi-basket"></i></span>
                <div class="tb-frise-contenu"><strong><?= te('Récolte') ?></strong><br><?= e(date('d/m/Y', strtotime($produit['dateRecolte']))) ?>
                  <small class="text-muted">· <?= te('{n} jours de culture', ['n' => $duree]) ?></small></div></li>
              <?php if ($qualite): ?>
                <li><span class="tb-frise-icone"><i class="bi bi-award"></i></span>
                  <div class="tb-frise-contenu"><strong><?= te('Contrôle qualité') ?></strong><br><?= e(date('d/m/Y', strtotime($qualite['dateControle']))) ?>
                    <small class="d-block"><?= implode(' · ', array_map(fn($m) => te($m[0], $m[1]), ControleQualiteController::resume($qualite))) ?></small></div></li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="tb-panneau h-100">
            <h2 class="tb-titre-panneau"><i class="bi bi-map"></i> <?= te('Lieu de production') ?></h2>
            <div id="carteTerrains" class="tb-carte tb-carte-petite"
                 data-points='<?= e(json_encode([['id' => null, 'nom' => $produit['terrainNom'], 'lat' => $lat, 'lng' => $lng, 'exact' => $exact, 'polygone' => $parcelle, 'info' => $produit['localisation']]])) ?>'
                 data-texte-approx="<?= te('Position approximative (centre de la wilaya)') ?>"></div>
          </div>
        </div>
      </div>

      <?php if ($bilan['note']): ?>
        <div class="tb-panneau mt-4">
          <h2 class="tb-titre-panneau"><i class="bi bi-globe2"></i> <?= te('Impact environnemental') ?></h2>
          <div class="d-flex flex-wrap gap-4 align-items-center">
            <div class="d-flex gap-1" aria-label="<?= te('Note {n} sur une échelle de A à E', ['n' => $bilan['note']]) ?>">
              <?php foreach (EnvironnementController::NOTES as $n): ?>
                <span class="tb-note-env tb-note-<?= strtolower($n) ?> <?= $n === $bilan['note'] ? 'tb-note-grande' : 'tb-note-eteinte' ?>"><?= $n ?></span>
              <?php endforeach; ?>
            </div>
            <?php if ($bilan['eau']): ?>
              <div><strong><?= e(number_format($bilan['eau']['litresParKg'], 0, ',', ' ')) ?> L</strong> <span class="text-muted"><?= te('d’eau par kg') ?></span></div>
            <?php endif; ?>
            <?php if ($bilan['carbone']): ?>
              <div><strong><?= e(number_format($bilan['carbone']['parKg'], 2, ',', ' ')) ?> kg</strong> <span class="text-muted"><?= te('de CO₂e par kg') ?></span></div>
            <?php endif; ?>
          </div>
          <p class="small text-muted mt-2 mb-0"><?= te('Estimation calculée par Taam Bledi à partir de la durée de culture, du rendement et des dépenses de l’agriculteur.') ?></p>
        </div>
      <?php endif; ?>

      <?php if ($culture): ?>
        <div class="tb-panneau mt-4">
          <h2 class="tb-titre-panneau"><i class="bi bi-book"></i> <?= te('À propos de cette culture') ?></h2>
          <p class="mb-2"><?= te($culture['conseil']) ?></p>
          <a href="services.html" class="tb-lien"><?= te('Découvrir les variétés tunisiennes au Musée') ?> <i class="bi bi-arrow-right"></i></a>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </main>

  <footer class="tb-public-pied">© <?= date('Y') ?> Taam Bledi — <?= te('Agriculture tunisienne traçable') ?></footer>
  <script src="assets/js/terrains-commun.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script src="assets/js/carte-terrains.js"></script>
</body>
</html>
