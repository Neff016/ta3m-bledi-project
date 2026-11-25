<?php
/*
 * Haut de page commun du Back Office (pages du module Terrains et Récoltes)
 * Variables : $titrePage, $boActif (nom du fichier de la page, ex : 'listeTerrains.php'),
 * facultatif : $avecCarte (Leaflet)
 */
$lienSidebar = function (string $fichier, string $icone, string $texte) use ($boActif): string {
    $actif = $boActif === $fichier ? ' active' : '';
    return '<li class="nav-item' . $actif . '"><a class="nav-link" href="' . $fichier . '"><i class="fas fa-fw ' . $icone . '"></i> <span>' . $texte . '</span></a></li>';
};
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Taam Bledi - <?= e($titrePage) ?></title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <?php if (!empty($avecCarte)): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <?php endif; ?>
    <link href="css/terrains-admin.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-seedling"></i></div>
                <div class="sidebar-brand-text mx-3">Taam Bledi</div>
            </a>
            <hr class="sidebar-divider my-0">
            <?= $lienSidebar('dashboard.php', 'fa-chart-pie', 'Statistiques') ?>

            <hr class="sidebar-divider">
            <div class="sidebar-heading">Utilisateurs &amp; Badges</div>
            <?= $lienSidebar('listeUtilisateurs.php', 'fa-users', 'Utilisateurs') ?>
            <?= $lienSidebar('listeBadges.php', 'fa-award', 'Badges') ?>
            <?= $lienSidebar('attribuerBadge.php', 'fa-medal', 'Attribuer un badge') ?>

            <hr class="sidebar-divider">
            <div class="sidebar-heading">Terrains &amp; Récoltes</div>
            <?= $lienSidebar('listeTerrains.php', 'fa-map-marked-alt', 'Terrains') ?>
            <?= $lienSidebar('listeRecoltes.php', 'fa-seedling', 'Récoltes') ?>
            <?= $lienSidebar('carteTerrains.php', 'fa-globe-africa', 'Carte des terrains') ?>
            <?= $lienSidebar('listeCultures.php', 'fa-tags', 'Cultures et prix') ?>

            <hr class="sidebar-divider">
            <li class="nav-item"><a class="nav-link" href="../FrontOffice/index.html"><i class="fas fa-fw fa-globe"></i> <span>Voir le site</span></a></li>
            <hr class="sidebar-divider d-none d-md-block">
            <div class="text-center d-none d-md-inline"><button class="rounded-circle border-0" id="sidebarToggle"></button></div>
        </ul>
        <!-- End of Sidebar -->

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3"><i class="fa fa-bars"></i></button>
                    <span class="text-gray-600 d-none d-md-inline"><i class="fas fa-seedling text-success"></i> Back Office — <?= e($titrePage) ?></span>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= e(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')) ?></span>
                                <img class="img-profile rounded-circle" src="img/undraw_profile.svg" alt="">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="../FrontOffice/profil.php"><i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Mon profil</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Se déconnecter</a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <div class="container-fluid">
