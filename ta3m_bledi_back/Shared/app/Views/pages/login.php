<section class="section auth-section"><div class="auth-card">
 <div class="kicker"><?= APP_AREA === 'backoffice' ? 'Espace administration' : 'Espace utilisateur' ?></div><h2>Connexion</h2><p><?= APP_AREA === 'backoffice' ? 'Connecte-toi pour gérer les ateliers et les inscriptions.' : 'Connecte-toi pour gérer tes inscriptions.' ?></p>
 <form method="post" action="index.php?page=login">
  <?= csrf_field() ?>
  <label>Adresse e-mail</label><input type="email" name="email" required autocomplete="email">
  <label>Mot de passe</label><input type="password" name="password" required autocomplete="current-password">
  <button class="btn btn-wide" type="submit">Se connecter</button>
 </form>
 <?php if (APP_AREA === 'frontoffice'): ?><p class="auth-help">Pas encore de compte ? <a href="index.php?page=register">Créer un compte</a></p><?php endif; ?>
</div></section>
