<?php
/*
 * Haut de page commun du module Terrains et Récoltes
 * Variables : $titrePage (texte français), $pageActive (ex : 'mesTerrains.php')
 * Facultatif : $avecCarte (true = charge Leaflet), $cssSupp (liste de fichiers CSS)
 */
require_once __DIR__ . '/../../../Controller/Langue.php';

$menuModule = [
    'tableauDeBord.php'   => ['bi-speedometer2', 'Tableau de bord'],
    'mesTerrains.php'     => ['bi-geo-alt', 'Mes terrains'],
    'mesRecoltes.php'     => ['bi-basket', 'Mes récoltes'],
    'recommandation.php'  => ['bi-stars', 'Recommandation IA'],
    'consommationEau.php' => ['bi-droplet', "Consommation d'eau"],
];
$pageActive ??= basename($_SERVER['SCRIPT_NAME']);
$retourLangue = rawurlencode(basename($_SERVER['SCRIPT_NAME']) . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
?>
<!DOCTYPE html>
<html lang="<?= langue() ?>" dir="<?= estRtl() ? 'rtl' : 'ltr' ?>">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= te($titrePage) ?> - Taam Bledi</title>
  <meta name="description" content="<?= te('Taam Bledi : gérez vos terrains et vos récoltes avec la météo, la carte et l’intelligence artificielle.') ?>">
  <meta name="theme-color" content="#2f5d3a">

  <!-- Application installable (PWA) -->
  <link rel="manifest" href="manifest.webmanifest">
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&family=Marcellus:wght@400&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files (Bootstrap RTL pour l'arabe) -->
  <link href="assets/vendor/bootstrap/css/<?= estRtl() ? 'bootstrap.rtl.min.css' : 'bootstrap.min.css' ?>" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <?php if (!empty($avecCarte)): ?>
    <!-- Carte : Leaflet + OpenStreetMap -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <?php endif; ?>

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/terrains.css" rel="stylesheet">
  <link href="assets/css/terrains-avance.css" rel="stylesheet">
  <?php foreach ($cssSupp ?? [] as $css): ?>
    <link href="<?= e($css) ?>" rel="stylesheet">
  <?php endforeach; ?>

  <!-- Traductions pour les scripts (vide en français) -->
  <script>window.TRAD = <?= estRtl() ? json_encode(dictionnaireArabe(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) : '{}' ?>;</script>
</head>

<body class="module-terrains <?= estRtl() ? 'rtl' : '' ?>">

  <header id="header" class="header d-flex align-items-center position-relative">
    <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="index.html" class="logo d-flex align-items-center">
        <img src="assets/img/logo.png" alt="Taam Bledi">
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.html"><?= te('Accueil') ?></a></li>
          <li><a href="services.html"><?= te('Variétés') ?></a></li>
          <li><a href="blog.html"><?= te('Blog') ?></a></li>
          <li class="dropdown"><a href="#" class="<?= isset($menuModule[$pageActive]) ? 'active' : '' ?>"><span><?= te('Terrains & Récoltes') ?></span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <?php foreach ($menuModule as $lien => [$icone, $libelle]): ?>
                <li><a href="<?= $lien ?>" class="<?= $pageActive === $lien ? 'active' : '' ?>"><i class="bi <?= $icone ?> me-2"></i><?= te($libelle) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </li>
          <li class="dropdown"><a href="#"><span><?= te('Mon compte') ?></span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="profil.php"><?= te('Mon profil') ?></a></li>
              <li><a href="mesBadges.php"><?= te('Mes badges') ?></a></li>
              <li><a href="connexion.php"><?= te('Connexion') ?></a></li>
              <li><a href="inscription.php"><?= te('Inscription') ?></a></li>
            </ul>
          </li>
          <li><a href="contact.html"><?= te('Contact') ?></a></li>
          <li class="tb-langue">
            <?php if (estRtl()): ?>
              <a href="langue.php?l=fr&amp;retour=<?= $retourLangue ?>" title="Français" lang="fr">FR</a>
            <?php else: ?>
              <a href="langue.php?l=ar&amp;retour=<?= $retourLangue ?>" title="العربية" lang="ar">ع</a>
            <?php endif; ?>
          </li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

    </div>
  </header>

  <!-- Bandeau hors ligne (affiché par assets/js/hors-ligne.js) -->
  <div id="bandeauHorsLigne" class="tb-hors-ligne" hidden>
    <i class="bi bi-wifi-off"></i> <?= te('Vous êtes hors ligne : vos récoltes seront enregistrées sur cet appareil et envoyées au retour d’Internet.') ?>
  </div>

  <main class="main">
