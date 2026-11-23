<section class="section auth-section"><div class="auth-card">
 <div class="kicker">Rejoignez la communauté</div><h2>Créer un compte</h2><p>Un compte est nécessaire pour réserver un atelier.</p>
 <form method="post" action="index.php?page=register">
  <?= csrf_field() ?>
  <label>Nom complet</label><input type="text" name="name" required maxlength="120">
  <label>Adresse e-mail</label><input type="email" name="email" required maxlength="190">
  <label>Mot de passe (8 caractères minimum)</label><input type="password" name="password" required minlength="8">
  <button class="btn btn-wide" type="submit">Créer mon compte</button>
 </form>
 <p class="auth-help">Déjà inscrit ? <a href="index.php?page=login">Connexion</a></p>
</div></section>
