<header>
 <div class="container nav">
  <a class="brand" href="<?= APP_AREA === 'backoffice' ? 'index.php?page=admin' : 'index.php?page=ateliers' ?>">Ta3m Bledi</a>
  <nav class="nav-right">
   <?php if (APP_AREA === 'backoffice'): ?>
    <?php if (is_admin()): ?>
     <a class="nav-link <?= $currentPage === 'admin' ? 'active' : '' ?>" href="index.php?page=admin">Tableau de bord</a>
     <a class="nav-link <?= $currentPage === 'admin-ateliers' ? 'active' : '' ?>" href="index.php?page=admin-ateliers">Gérer les ateliers</a>
     <a class="nav-link <?= $currentPage === 'admin-inscriptions' ? 'active' : '' ?>" href="index.php?page=admin-inscriptions">Inscriptions</a>
     <a class="nav-link" href="../frontoffice/index.php?page=ateliers" target="_blank">Voir le Front Office</a>
     <span class="nav-user">Administrateur <?= e(current_user()['name']) ?></span>
     <a class="nav-link" href="index.php?page=logout">Déconnexion</a>
    <?php else: ?>
     <a class="nav-link active" href="index.php?page=login">Connexion Admin</a>
    <?php endif; ?>
   <?php else: ?>
    <a class="nav-pill" href="index.php?page=ateliers">Ateliers</a>
    <?php if (is_logged_in() && !is_admin()): ?>
     <a class="nav-link <?= $currentPage === 'mes-inscriptions' ? 'active' : '' ?>" href="index.php?page=mes-inscriptions">Mes inscriptions</a>
     <span class="nav-user"><?= e(current_user()['name']) ?></span>
     <a class="nav-link" href="index.php?page=logout">Déconnexion</a>
    <?php elseif (is_admin()): ?>
     <a class="nav-link" href="index.php?page=login">Connexion</a>
    <?php else: ?>
     <a class="nav-link" href="index.php?page=login">Connexion</a>
     <a class="nav-link" href="index.php?page=register">Créer un compte</a>
    <?php endif; ?>
   <?php endif; ?>
  </nav>
 </div>
</header>
