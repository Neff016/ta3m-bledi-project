<?php
/*
 * Se connecter SANS mot de passe : on reçoit un code à 6 chiffres par email.
 * Étape 1 : saisir son email   →   Étape 2 : saisir le code reçu
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/UtilisateurController.php';
require_once __DIR__ . '/../../Controller/MailHelper.php';

$utilisateurC = new UtilisateurController();
$erreurs      = [];
$info         = null;
const DELAI_RENVOI = 60;   // secondes avant de pouvoir redemander un code

if (isset($_GET['changer'])) {
    unset($_SESSION['code_connexion_email']);
}
$email = $_SESSION['code_connexion_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---------- Étape 1 : envoyer le code ---------- */
    if ($action === 'email') {
        $emailSaisi = trim($_POST['email'] ?? '');
        if ($emailSaisi === '') {
            $erreurs['email'] = "L'email est obligatoire.";
        } elseif (!filter_var($emailSaisi, FILTER_VALIDATE_EMAIL)) {
            $erreurs['email'] = "L'email n'est pas valide (exemple@mail.com).";
        }
        if (empty($erreurs)) {
            $envoi = demanderCodeParEmail($utilisateurC, $emailSaisi, 'connexion');
            if ($envoi['ok']) {
                $_SESSION['code_connexion_email'] = $emailSaisi;
                header('Location: ConnexionCode.php');
                exit;
            }
            $erreurs['email'] = "L'email n'a pas pu être envoyé : " . $envoi['erreur'];
        }
    }

    /* ---------- Renvoyer le code ---------- */
    if ($action === 'renvoyer' && $email !== '') {
        $attente = DELAI_RENVOI - (time() - ($_SESSION['reset_dernier_envoi'] ?? 0));
        if ($attente > 0) {
            $erreurs['renvoi'] = "Patientez encore $attente secondes avant de demander un nouveau code.";
        } else {
            $envoi = demanderCodeParEmail($utilisateurC, $email, 'connexion');
            if ($envoi['ok']) {
                $info = 'Un nouveau code a été envoyé. L’ancien code ne fonctionne plus.';
            } else {
                $erreurs['renvoi'] = "L'email n'a pas pu être envoyé : " . $envoi['erreur'];
            }
        }
    }

    /* ---------- Étape 2 : vérifier le code et se connecter ---------- */
    if ($action === 'code' && $email !== '') {
        $code = trim($_POST['code'] ?? '');
        if ($code === '') {
            $erreurs['code'] = 'Le code est obligatoire.';
        } elseif (!preg_match('/^\d{6}$/', $code)) {
            $erreurs['code'] = 'Le code doit contenir exactement 6 chiffres.';
        } else {
            $resultat = $utilisateurC->verifierCode($email, $code);
            if ($resultat['ok']) {
                $utilisateurC->consommerCode($resultat['id']);           // le code ne resservira pas
                $user = $utilisateurC->getUtilisateurActif($resultat['id']);
                header('Location: ' . demarrerSession($utilisateurC, $user));
                exit;
            }
            $erreurs['code'] = $resultat['erreur'];
        }
    }
}

// Email masqué pour l'affichage : yo*******@gmail.com
$emailMasque = '';
if ($email !== '') {
    [$local, $domaine] = explode('@', $email, 2) + [1 => ''];
    $emailMasque = mb_substr($local, 0, 2) . str_repeat('*', max(mb_strlen($local) - 2, 3)) . '@' . $domaine;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Taam Bledi - Connexion par code</title>
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
              <li><a href="tableauDeBord.php">Tableau de bord</a></li>
              <li><a href="mesTerrains.php">Mes terrains</a></li>
              <li><a href="mesRecoltes.php">Mes récoltes</a></li>
              <li><a href="recommandation.php">Recommandation IA</a></li>
              <li><a href="consommationEau.php">Consommation d'eau</a></li>
            </ul>
          </li>
          <li class="dropdown"><a href="#"><span>Mon compte</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="profil.php">Mon profil</a></li>
              <li><a href="mesBadges.php">Mes badges</a></li>
              <li><a href="connexion.php" class="active">Connexion</a></li>
              <li><a href="inscription.php">Inscription</a></li>
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

        <div class="ub-hero">
          <span class="ub-hero-label">Espace utilisateur</span>
          <h1>Connexion par code</h1>
        </div>

        <div class="ub-card">
          <?php if ($email === ''): ?>
            <!-- Étape 1 : email -->
            <div class="text-center mb-3"><i class="bi bi-envelope-paper ub-icone-mail"></i></div>
            <p class="text-center">Pas besoin de mot de passe : entrez votre email, nous vous envoyons un code à 6 chiffres.</p>
            <form id="formCodeEmail" action="" method="POST" novalidate>
              <?= champCsrf() ?>
              <input type="hidden" name="action" value="email">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="text" class="form-control <?= isset($erreurs['email']) ? 'is-invalid' : '' ?>"
                       id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="exemple@mail.com">
                <div class="ub-erreur" id="erreur-email"><?= e($erreurs['email'] ?? '') ?></div>
              </div>
              <button type="submit" class="ub-btn">Recevoir mon code</button>
            </form>
          <?php else: ?>
            <!-- Étape 2 : code -->
            <div class="text-center mb-3"><i class="bi bi-envelope-check ub-icone-mail"></i></div>
            <div class="alert alert-success">
              Si un compte existe avec <strong><?= e($emailMasque) ?></strong>, un code de connexion vient d'être envoyé
              par email. Il est valable <?= UtilisateurController::CODE_DUREE_MINUTES ?> minutes.
              <br><small>Pensez à regarder aussi dans le dossier <strong>Spam</strong>.</small>
            </div>
            <?php if ($info): ?>
              <div class="alert alert-info"><?= e($info) ?></div>
            <?php endif; ?>
            <?php if (isset($erreurs['renvoi'])): ?>
              <div class="alert alert-danger"><?= e($erreurs['renvoi']) ?></div>
            <?php endif; ?>
            <form id="formCode" action="" method="POST" novalidate>
              <?= champCsrf() ?>
              <input type="hidden" name="action" value="code">
              <div class="mb-3">
                <label for="code" class="form-label">Code reçu</label>
                <input type="text" class="form-control ub-champ-code <?= isset($erreurs['code']) ? 'is-invalid' : '' ?>"
                       id="code" name="code" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="••••••">
                <div class="ub-erreur" id="erreur-code"><?= e($erreurs['code'] ?? '') ?></div>
              </div>
              <button type="submit" class="ub-btn">Se connecter</button>
            </form>
            <form action="" method="POST" class="text-center mt-3 mb-0">
              <?= champCsrf() ?>
              <input type="hidden" name="action" value="renvoyer">
              Pas reçu le code ?
              <button type="submit" class="btn btn-link p-0 align-baseline ub-lien-renvoi">Renvoyer le code</button>
              · <a href="ConnexionCode.php?changer=1">Changer d'email</a>
            </form>
          <?php endif; ?>
          <p class="text-center mt-3 mb-0"><a href="connexion.php"><i class="bi bi-arrow-left"></i> Retour à la connexion</a></p>
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