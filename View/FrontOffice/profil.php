<?php
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/UtilisateurController.php';
require_once __DIR__ . '/../../Controller/UploadHelper.php';

$idUtilisateur = requireLogin();
$utilisateurC  = new UtilisateurController();
$user          = $utilisateurC->getUtilisateurActif($idUtilisateur);

if (!$user) {               // compte supprimé entre-temps
    header('Location: deconnexion.php');
    exit;
}

$erreurs     = [];     // onglet "Modifier le profil"
$erreursMdp  = [];     // onglet "Sécurité"
$erreursSupp = [];     // onglet "Zone sensible"
$onglet      = $_GET['onglet'] ?? 'apercu';   // onglet ouvert
$valeurs     = [
    'nom'       => $user['nom'],
    'prenom'    => $user['prenom'],
    'email'     => $user['email'],
    'telephone' => $user['telephone'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---------- 1) Modifier mes informations ---------- */
    if ($action === 'infos') {
        $onglet  = 'infos';
        $valeurs = [
            'nom'       => trim($_POST['nom'] ?? ''),
            'prenom'    => trim($_POST['prenom'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
        ];
        $erreurs = $utilisateurC->validateInfos($valeurs, $idUtilisateur);

        // Photo (facultative)
        $nouvellePhoto = null;
        if (empty($erreurs) && fichierEnvoye($_FILES['photo'] ?? null)) {
            $upload = uploadImage($_FILES['photo'], 'profils');
            if ($upload['ok']) {
                $nouvellePhoto = $upload['fichier'];
            } else {
                $erreurs['photo'] = $upload['erreur'];
            }
        }

        if (empty($erreurs)) {
            $utilisateurC->updateUtilisateur(new Utilisateur(
                $valeurs['nom'], $valeurs['prenom'], $valeurs['email'], $valeurs['telephone'],
                '', $user['role'], $user['statut'], null, $idUtilisateur   // rôle et statut inchangés
            ));
            if ($nouvellePhoto) {
                supprimerImage($user['photo'], 'profils');
                $utilisateurC->updatePhoto($idUtilisateur, $nouvellePhoto);
            }
            $_SESSION['nom']    = $valeurs['nom'];
            $_SESSION['prenom'] = $valeurs['prenom'];
            setFlash('Profil mis à jour avec succès.');
            header('Location: profil.php?onglet=infos');
            exit;
        }
    }

    /* ---------- 2) Retirer ma photo ---------- */
    if ($action === 'supprimerPhoto') {
        supprimerImage($user['photo'], 'profils');
        $utilisateurC->updatePhoto($idUtilisateur, null);
        setFlash('Photo de profil retirée.');
        header('Location: profil.php?onglet=infos');
        exit;
    }

    /* ---------- 3) Changer mon mot de passe ---------- */
    if ($action === 'motdepasse') {
        $onglet = 'securite';
        $ancien = $_POST['ancienMdp'] ?? '';
        if ($ancien === '') {
            $erreursMdp['ancienMdp'] = "L'ancien mot de passe est obligatoire.";
        } elseif (!password_verify($ancien, $user['motDePasse'])) {
            $erreursMdp['ancienMdp'] = "L'ancien mot de passe est incorrect.";
        }
        $erreursMdp += $utilisateurC->validateMotDePasse(
            $_POST['nouveauMdp'] ?? '', $_POST['confirmMdp'] ?? '', 'nouveauMdp', 'confirmMdp'
        );

        if (empty($erreursMdp)) {
            $utilisateurC->updateMotDePasse($idUtilisateur, $_POST['nouveauMdp']);
            setFlash('Mot de passe modifié avec succès.');
            header('Location: profil.php?onglet=securite');
            exit;
        }
    }

    /* ---------- 4) Supprimer mon compte ---------- */
    if ($action === 'supprimerCompte') {
        $onglet = 'danger';
        $mdp    = $_POST['mdpSuppression'] ?? '';
        if ($mdp === '') {
            $erreursSupp['mdpSuppression'] = 'Entrez votre mot de passe pour confirmer.';
        } elseif (!password_verify($mdp, $user['motDePasse'])) {
            $erreursSupp['mdpSuppression'] = 'Mot de passe incorrect.';
        }
        if (($_POST['confirmation'] ?? '') !== 'SUPPRIMER') {
            $erreursSupp['confirmation'] = 'Tapez SUPPRIMER en majuscules pour confirmer.';
        }
        if ($user['role'] === 'ADMIN') {
            $erreursSupp['confirmation'] = "Un compte administrateur ne peut pas se supprimer lui-même.";
        }

        if (empty($erreursSupp)) {
            $utilisateurC->deleteUtilisateur($idUtilisateur);
            $_SESSION = [];
            session_regenerate_id(true);
            setFlash('Votre compte a été supprimé. Merci d’avoir utilisé Taam Bledi.', 'info');
            header('Location: connexion.php');
            exit;
        }
    }
}

if (!in_array($onglet, ['apercu', 'infos', 'securite', 'danger'], true)) {
    $onglet = 'apercu';
}

/* ---------- Données affichées ---------- */
$flash      = getFlash();
$stats      = $utilisateurC->statistiquesProfil($idUtilisateur);
$badges     = $utilisateurC->badgesRecents($idUtilisateur, 3);
$recoltes   = $utilisateurC->recoltesRecentes($idUtilisateur, 4);
$initiales  = mb_strtoupper(mb_substr($user['prenom'], 0, 1) . mb_substr($user['nom'], 0, 1));
$precedente = $_SESSION['connexion_precedente'] ?? null;

$mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août',
         'septembre', 'octobre', 'novembre', 'décembre'];
$dateFr = fn(?string $d) => $d ? date('j', strtotime($d)) . ' ' . $mois[(int) date('n', strtotime($d)) - 1] . ' ' . date('Y', strtotime($d)) : '';

// Complétion du profil
$etapes = [
    ['Photo de profil',       !empty($user['photo'])],
    ['Numéro de téléphone',   !empty($user['telephone'])],
    ['Premier terrain',       $stats['terrains'] > 0],
    ['Première récolte',      $stats['recoltes'] > 0],
    ['Premier badge obtenu',  $stats['badges'] > 0],
];
$faites     = count(array_filter($etapes, fn($e) => $e[1]));
$completion = (int) round($faites / count($etapes) * 100);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Taam Bledi - Mon profil</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Marcellus:wght@400&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/utilisateurs.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: AgriCulture
  * Template URL: https://bootstrapmade.com/agriculture-bootstrap-website-template/
  * Updated: Aug 07 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body class="about-page">

  <header id="header" class="header d-flex align-items-center position-relative">
    <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="index.html" class="logo d-flex align-items-center">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <img src="assets/img/logo.png" alt="Taam Bledi">
        <!-- <h1 class="sitename">Taam Bledi</h1>  -->
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.html">Home</a></li>
          <li><a href="about.html">About Us</a></li>
          <li><a href="services.html">Our Services</a></li>
          <li><a href="testimonials.html">Testimonials</a></li>
          <li><a href="blog.html">Blog</a></li>
          <li class="dropdown"><a href="#"><span>Terrains &amp; Récoltes</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="mesTerrains.php">Mes terrains</a></li>
              <li><a href="mesRecoltes.php">Mes récoltes</a></li>
              <li><a href="recommandation.php">Recommandation IA</a></li>
              <li><a href="consommationEau.php">Consommation d'eau</a></li>
            </ul>
          </li>
          <li class="dropdown"><a href="#"><span>Mon compte</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="profil.php" class="active">Mon profil</a></li>
              <li><a href="mesBadges.php">Mes badges</a></li>
              <li><a href="connexion.php">Connexion</a></li>
              <li><a href="inscription.php">Inscription</a></li>
              <li><a href="deconnexion.php">Déconnexion</a></li>
            </ul>
          </li>
          <li><a href="contact.html">Contact</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

    </div>
  </header>

  <main class="main">
    <section class="ub-page">
      <div class="container">

        <?php if ($flash): ?>
          <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <!-- ===== En-tête du profil ===== -->
        <div class="pf-entete">
          <div class="pf-couverture"></div>
          <div class="pf-identite">
            <div class="pf-avatar-zone">
              <?php if (!empty($user['photo'])): ?>
                <img src="assets/img/uploads/profils/<?= e($user['photo']) ?>" alt="Photo de profil" class="pf-avatar">
              <?php else: ?>
                <div class="pf-avatar pf-initiales"><?= e($initiales) ?></div>
              <?php endif; ?>
            </div>
            <div class="pf-infos">
              <h1><?= e($user['prenom'] . ' ' . $user['nom']) ?></h1>
              <div class="pf-etiquettes">
                <span class="pf-badge-role"><i class="bi bi-person-badge"></i> <?= e(UtilisateurController::ROLES[$user['role']] ?? $user['role']) ?></span>
                <span class="pf-badge-statut"><i class="bi bi-patch-check-fill"></i> Compte actif</span>
                <span class="pf-membre"><i class="bi bi-calendar3"></i> Membre depuis <?= e($dateFr($user['dateInscription'])) ?></span>
              </div>
              <div class="pf-contact">
                <span><i class="bi bi-envelope"></i> <?= e($user['email']) ?></span>
                <?php if (!empty($user['telephone'])): ?>
                  <span><i class="bi bi-telephone"></i> <?= e($user['telephone']) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="pf-actions">
              <a href="profil.php?onglet=infos" class="ub-btn pf-btn"><i class="bi bi-pencil"></i> Modifier</a>
              <a href="deconnexion.php" class="pf-btn-contour"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
            </div>
          </div>
        </div>

        <!-- ===== Chiffres ===== -->
        <div class="row g-3 my-4">
          <div class="col-6 col-lg-3">
            <div class="pf-stat"><i class="bi bi-geo-alt"></i><strong><?= $stats['terrains'] ?></strong><span>Terrain<?= $stats['terrains'] > 1 ? 's' : '' ?></span></div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="pf-stat"><i class="bi bi-rulers"></i><strong><?= e(number_format($stats['superficie'], 1, ',', ' ')) ?> ha</strong><span>Superficie totale</span></div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="pf-stat"><i class="bi bi-basket"></i><strong><?= $stats['recoltes'] ?></strong><span>Récolte<?= $stats['recoltes'] > 1 ? 's' : '' ?></span></div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="pf-stat"><i class="bi bi-award"></i><strong><?= $stats['badges'] ?> · <?= $stats['points'] ?> pts</strong><span>Badges</span></div>
          </div>
        </div>

        <!-- ===== Complétion du profil ===== -->
        <?php if ($completion < 100): ?>
          <div class="pf-carte mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0">Complétez votre profil</h5>
              <strong class="pf-pourcentage"><?= $completion ?> %</strong>
            </div>
            <div class="progress pf-progression" role="progressbar" aria-valuenow="<?= $completion ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="progress-bar" style="width: <?= $completion ?>%"></div>
            </div>
            <ul class="pf-etapes">
              <?php foreach ($etapes as [$libelle, $fait]): ?>
                <li class="<?= $fait ? 'fait' : '' ?>"><i class="bi <?= $fait ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i> <?= e($libelle) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- ===== Onglets ===== -->
        <div class="pf-carte">
          <ul class="nav pf-onglets" role="tablist">
            <?php foreach (['apercu' => ['bi-grid', 'Aperçu'], 'infos' => ['bi-person', 'Modifier le profil'],
                            'securite' => ['bi-shield-lock', 'Sécurité'], 'danger' => ['bi-exclamation-triangle', 'Zone sensible']] as $id => [$icone, $titre]): ?>
              <li class="nav-item" role="presentation">
                <button class="nav-link <?= $onglet === $id ? 'active' : '' ?> <?= $id === 'danger' ? 'pf-onglet-danger' : '' ?>"
                        data-bs-toggle="tab" data-bs-target="#onglet-<?= $id ?>" type="button" role="tab">
                  <i class="bi <?= $icone ?>"></i> <?= $titre ?>
                </button>
              </li>
            <?php endforeach; ?>
          </ul>

          <div class="tab-content pt-4">

            <!-- Aperçu -->
            <div class="tab-pane fade <?= $onglet === 'apercu' ? 'show active' : '' ?>" id="onglet-apercu" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Derniers badges</h5>
                    <a href="mesBadges.php" class="pf-lien">Tout voir</a>
                  </div>
                  <?php if (empty($badges)): ?>
                    <p class="pf-vide"><i class="bi bi-award"></i> Aucun badge pour l'instant. Ajoutez vos terrains et récoltes pour en gagner.</p>
                  <?php else: ?>
                    <?php foreach ($badges as $b): ?>
                      <div class="pf-ligne">
                        <?php if (!empty($b['icone'])): ?>
                          <img src="assets/img/uploads/badges/<?= e($b['icone']) ?>" alt="" class="pf-ligne-icone">
                        <?php else: ?>
                          <span class="pf-ligne-icone pf-ligne-icone-defaut"><i class="bi bi-award-fill"></i></span>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                          <strong><?= e($b['nom']) ?></strong>
                          <small class="d-block text-muted">Obtenu le <?= e($dateFr($b['dateAttribution'])) ?></small>
                        </div>
                        <span class="ub-points"><?= e($b['points']) ?> pts</span>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
                <div class="col-lg-6">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Dernières récoltes</h5>
                    <a href="mesRecoltes.php" class="pf-lien">Tout voir</a>
                  </div>
                  <?php if (empty($recoltes)): ?>
                    <p class="pf-vide"><i class="bi bi-basket"></i> Aucune récolte enregistrée. <a href="ajouterRecolte.php">Ajouter une récolte</a></p>
                  <?php else: ?>
                    <?php foreach ($recoltes as $r): ?>
                      <div class="pf-ligne">
                        <span class="pf-ligne-icone pf-ligne-icone-defaut"><i class="bi bi-flower1"></i></span>
                        <div class="flex-grow-1">
                          <strong><?= e($r['culture']) ?></strong>
                          <small class="d-block text-muted"><?= e($r['terrainNom']) ?> · <?= e($dateFr($r['dateRecolte'])) ?></small>
                        </div>
                        <span class="pf-quantite"><?= e($r['quantite']) ?> <?= e($r['unite']) ?></span>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Modifier le profil -->
            <div class="tab-pane fade <?= $onglet === 'infos' ? 'show active' : '' ?>" id="onglet-infos" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-4 text-center">
                  <div class="pf-apercu-photo mb-3">
                    <?php if (!empty($user['photo'])): ?>
                      <img id="apercuPhoto" src="assets/img/uploads/profils/<?= e($user['photo']) ?>" alt="Aperçu" class="pf-avatar">
                    <?php else: ?>
                      <img id="apercuPhoto" src="" alt="Aperçu" class="pf-avatar d-none">
                      <div id="apercuInitiales" class="pf-avatar pf-initiales"><?= e($initiales) ?></div>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($user['photo'])): ?>
                    <form action="" method="POST" onsubmit="return confirm('Retirer votre photo de profil ?');">
                      <?= champCsrf() ?>
                      <input type="hidden" name="action" value="supprimerPhoto">
                      <button type="submit" class="pf-btn-contour pf-btn-petit"><i class="bi bi-trash"></i> Retirer la photo</button>
                    </form>
                  <?php endif; ?>
                </div>
                <div class="col-lg-8">
                  <form id="formProfil" action="" method="POST" enctype="multipart/form-data" novalidate>
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="infos">
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control <?= isset($erreurs['nom']) ? 'is-invalid' : '' ?>"
                               id="nom" name="nom" value="<?= e($valeurs['nom']) ?>">
                        <div class="ub-erreur" id="erreur-nom"><?= e($erreurs['nom'] ?? '') ?></div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control <?= isset($erreurs['prenom']) ? 'is-invalid' : '' ?>"
                               id="prenom" name="prenom" value="<?= e($valeurs['prenom']) ?>">
                        <div class="ub-erreur" id="erreur-prenom"><?= e($erreurs['prenom'] ?? '') ?></div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="text" class="form-control <?= isset($erreurs['email']) ? 'is-invalid' : '' ?>"
                               id="email" name="email" value="<?= e($valeurs['email']) ?>">
                        <div class="ub-erreur" id="erreur-email"><?= e($erreurs['email'] ?? '') ?></div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="text" class="form-control <?= isset($erreurs['telephone']) ? 'is-invalid' : '' ?>"
                               id="telephone" name="telephone" value="<?= e($valeurs['telephone']) ?>" placeholder="12345678">
                        <div class="ub-erreur" id="erreur-telephone"><?= e($erreurs['telephone'] ?? '') ?></div>
                      </div>
                    </div>
                    <div class="mb-3">
                      <label for="photo" class="form-label">Nouvelle photo de profil (facultatif)</label>
                      <input type="file" class="form-control <?= isset($erreurs['photo']) ? 'is-invalid' : '' ?>"
                             id="photo" name="photo">
                      <small class="text-muted">JPG, PNG, WEBP ou GIF, 2 Mo maximum. Un aperçu s'affiche à gauche.</small>
                      <div class="ub-erreur" id="erreur-photo"><?= e($erreurs['photo'] ?? '') ?></div>
                    </div>
                    <button type="submit" class="ub-btn pf-btn"><i class="bi bi-check2"></i> Enregistrer les modifications</button>
                  </form>
                </div>
              </div>
            </div>

            <!-- Sécurité -->
            <div class="tab-pane fade <?= $onglet === 'securite' ? 'show active' : '' ?>" id="onglet-securite" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-5">
                  <div class="pf-securite">
                    <h5><i class="bi bi-shield-check"></i> Votre compte est protégé</h5>
                    <ul>
                      <li><i class="bi bi-key"></i> Mot de passe chiffré (bcrypt)</li>
                      <li><i class="bi bi-laptop"></i> Une seule session active à la fois</li>
                      <li><i class="bi bi-hourglass-split"></i> Déconnexion après 30 min d'inactivité</li>
                      <li><i class="bi bi-clock-history"></i> Connexion précédente :
                        <strong><?= $precedente ? e($dateFr($precedente) . ' à ' . date('H:i', strtotime($precedente))) : 'première connexion' ?></strong>
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="col-lg-7">
                  <h5 class="mb-3">Changer mon mot de passe</h5>
                  <form id="formMotDePasse" action="" method="POST" novalidate>
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="motdepasse">
                    <div class="mb-3">
                      <label for="ancienMdp" class="form-label">Ancien mot de passe</label>
                      <input type="password" class="form-control <?= isset($erreursMdp['ancienMdp']) ? 'is-invalid' : '' ?>"
                             id="ancienMdp" name="ancienMdp">
                      <div class="ub-erreur" id="erreur-ancienMdp"><?= e($erreursMdp['ancienMdp'] ?? '') ?></div>
                    </div>
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="nouveauMdp" class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control <?= isset($erreursMdp['nouveauMdp']) ? 'is-invalid' : '' ?>"
                               id="nouveauMdp" name="nouveauMdp">
                        <div class="pf-force"><div id="forceBarre"></div></div>
                        <small id="forceTexte" class="text-muted">8 caractères minimum, une lettre et un chiffre.</small>
                        <div class="ub-erreur" id="erreur-nouveauMdp"><?= e($erreursMdp['nouveauMdp'] ?? '') ?></div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="confirmMdp" class="form-label">Confirmer</label>
                        <input type="password" class="form-control <?= isset($erreursMdp['confirmMdp']) ? 'is-invalid' : '' ?>"
                               id="confirmMdp" name="confirmMdp">
                        <div class="ub-erreur" id="erreur-confirmMdp"><?= e($erreursMdp['confirmMdp'] ?? '') ?></div>
                      </div>
                    </div>
                    <button type="submit" class="ub-btn pf-btn"><i class="bi bi-lock"></i> Changer le mot de passe</button>
                  </form>
                </div>
              </div>
            </div>

            <!-- Zone sensible -->
            <div class="tab-pane fade <?= $onglet === 'danger' ? 'show active' : '' ?>" id="onglet-danger" role="tabpanel">
              <div class="pf-danger">
                <h5><i class="bi bi-exclamation-triangle"></i> Supprimer mon compte</h5>
                <p>Vous ne pourrez plus vous connecter et vos terrains, récoltes et badges ne seront plus visibles.
                   Seul un <strong>administrateur</strong> pourra restaurer votre compte.</p>
                <form id="formSuppression" action="" method="POST" novalidate>
                  <?= champCsrf() ?>
                  <input type="hidden" name="action" value="supprimerCompte">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="mdpSuppression" class="form-label">Votre mot de passe</label>
                      <input type="password" class="form-control <?= isset($erreursSupp['mdpSuppression']) ? 'is-invalid' : '' ?>"
                             id="mdpSuppression" name="mdpSuppression">
                      <div class="ub-erreur" id="erreur-mdpSuppression"><?= e($erreursSupp['mdpSuppression'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label for="confirmation" class="form-label">Tapez <strong>SUPPRIMER</strong> pour confirmer</label>
                      <input type="text" class="form-control <?= isset($erreursSupp['confirmation']) ? 'is-invalid' : '' ?>"
                             id="confirmation" name="confirmation" autocomplete="off">
                      <div class="ub-erreur" id="erreur-confirmation"><?= e($erreursSupp['confirmation'] ?? '') ?></div>
                    </div>
                  </div>
                  <button type="submit" class="pf-btn-danger"><i class="bi bi-trash"></i> Supprimer mon compte</button>
                </form>
              </div>
            </div>

          </div>
        </div>

      </div>
    </section>
    <script src="assets/js/validation-utilisateurs.js"></script>
  </main>

  <footer id="footer" class="footer dark-background">

    <div class="footer-top">
      <div class="container">
        <div class="row gy-4">
          <div class="col-lg-4 col-md-6 footer-about">
            <a href="index.html" class="logo d-flex align-items-center">
              <span class="sitename">Taam Bledi</span>
            </a>
            <div class="footer-contact pt-3">
              <p>A108 Adam Street</p>
              <p>New York, NY 535022</p>
              <p class="mt-3"><strong>Phone:</strong> <span>+1 5589 55488 55</span></p>
              <p><strong>Email:</strong> <span>info@example.com</span></p>
            </div>
          </div>

          <div class="col-lg-2 col-md-3 footer-links">
            <h4>Useful Links</h4>
            <ul>
              <li><a href="#">Home</a></li>
              <li><a href="#">About us</a></li>
              <li><a href="#">Services</a></li>
              <li><a href="#">Terms of service</a></li>
              <li><a href="#">Privacy policy</a></li>
            </ul>
          </div>

          <div class="col-lg-2 col-md-3 footer-links">
            <h4>Our Services</h4>
            <ul>
              <li><a href="#">Web Design</a></li>
              <li><a href="#">Web Development</a></li>
              <li><a href="#">Product Management</a></li>
              <li><a href="#">Marketing</a></li>
              <li><a href="#">Graphic Design</a></li>
            </ul>
          </div>

          <div class="col-lg-2 col-md-3 footer-links">
            <h4>Hic solutasetp</h4>
            <ul>
              <li><a href="#">Molestiae accusamus iure</a></li>
              <li><a href="#">Excepturi dignissimos</a></li>
              <li><a href="#">Suscipit distinctio</a></li>
              <li><a href="#">Dilecta</a></li>
              <li><a href="#">Sit quas consectetur</a></li>
            </ul>
          </div>

          <div class="col-lg-2 col-md-3 footer-links">
            <h4>Nobis illum</h4>
            <ul>
              <li><a href="#">Ipsam</a></li>
              <li><a href="#">Laudantium dolorum</a></li>
              <li><a href="#">Dinera</a></li>
              <li><a href="#">Trodelas</a></li>
              <li><a href="#">Flexo</a></li>
            </ul>
          </div>

        </div>
      </div>
    </div>

    <div class="copyright text-center">
      <div class="container d-flex flex-column flex-lg-row justify-content-center justify-content-lg-between align-items-center">

        <div class="d-flex flex-column align-items-center align-items-lg-start">
          <div>
            © Copyright <strong><span>MyWebsite</span></strong>. All Rights Reserved
          </div>
          <div class="credits">
            <!-- All the links in the footer should remain intact. -->
            <!-- You can delete the links only if you purchased the pro version. -->
            <!-- Licensing information: https://bootstrapmade.com/license/ -->
            <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/herobiz-bootstrap-business-template/ -->
            Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a> | <a href="https://bootstrapmade.com/tools/">DevTools</a>
          </div>
        </div>

        <div class="social-links order-first order-lg-last mb-3 mb-lg-0">
          <a href=""><i class="bi bi-twitter-x"></i></a>
          <a href=""><i class="bi bi-facebook"></i></a>
          <a href=""><i class="bi bi-instagram"></i></a>
          <a href=""><i class="bi bi-linkedin"></i></a>
        </div>

      </div>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>