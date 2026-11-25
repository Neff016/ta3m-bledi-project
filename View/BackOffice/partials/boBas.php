<?php
/*
 * Bas de page commun du Back Office. Facultatif : $avecCarte, $jsSupp
 */
?>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto"><span>Taam Bledi &copy; <?= date('Y') ?> — Projet Web 2A, ESPRIT</span></div>
                </div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <!-- Fenêtre de déconnexion -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="titreDeconnexion" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titreDeconnexion">Se déconnecter ?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">Cliquez sur « Se déconnecter » pour fermer votre session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Annuler</button>
                    <a class="btn btn-primary" href="../FrontOffice/deconnexion.php">Se déconnecter</a>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <?php if (!empty($avecCarte)): ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <?php endif; ?>
    <script>
        // Confirmation avant suppression : <form data-confirm="…">
        document.addEventListener('submit', function (ev) {
            if (ev.target.dataset.confirm && !confirm(ev.target.dataset.confirm)) ev.preventDefault();
        }, true);
    </script>
    <?php foreach ($jsSupp ?? [] as $js): ?>
        <script src="<?= e($js) ?>"></script>
    <?php endforeach; ?>
</body>

</html>
