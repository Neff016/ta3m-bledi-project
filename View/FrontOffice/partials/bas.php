<?php
/*
 * Bas de page commun du module Terrains et Récoltes
 * Facultatif : $avecCarte (Leaflet), $jsSupp (liste de fichiers JS)
 */
?>
  </main>

  <footer id="footer" class="footer dark-background">
    <div class="footer-top">
      <div class="container">
        <div class="row gy-4">
          <div class="col-lg-5 col-md-6 footer-about">
            <a href="index.html" class="logo d-flex align-items-center">
              <span class="sitename">Taam Bledi</span>
            </a>
            <p class="pt-3"><?= te('La plateforme des agriculteurs tunisiens : terrains, récoltes, météo, traçabilité et conseils.') ?></p>
          </div>
          <div class="col-lg-3 col-md-3 footer-links">
            <h4><?= te('Terrains & Récoltes') ?></h4>
            <ul>
              <li><a href="tableauDeBord.php"><?= te('Tableau de bord') ?></a></li>
              <li><a href="mesTerrains.php"><?= te('Mes terrains') ?></a></li>
              <li><a href="mesRecoltes.php"><?= te('Mes récoltes') ?></a></li>
            </ul>
          </div>
          <div class="col-lg-4 col-md-3 footer-links">
            <h4><?= te('Outils intelligents') ?></h4>
            <ul>
              <li><a href="recommandation.php"><?= te('Recommandation IA') ?></a></li>
              <li><a href="consommationEau.php"><?= te("Consommation d'eau") ?></a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <div class="copyright text-center">
      <div class="container">
        © <?= date('Y') ?> <strong>Taam Bledi</strong> — <?= te('Projet Web 2A, ESPRIT') ?>
        <div class="credits small"><?= te('Météo : Open-Meteo · Cartes : © OpenStreetMap') ?> · Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a></div>
      </div>
    </div>
  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>   <!-- utilisé par main.js -->
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <?php if (!empty($avecCarte)): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <?php endif; ?>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/terrains-commun.js"></script>
  <script src="assets/js/hors-ligne.js"></script>
  <?php foreach ($jsSupp ?? [] as $js): ?>
    <script src="<?= e($js) ?>"></script>
  <?php endforeach; ?>

</body>

</html>
