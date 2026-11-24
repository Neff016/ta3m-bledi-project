<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Connexion – Tunisie2050 Admin</title>
  <link rel="stylesheet" href="<?= BO_URL ?>assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="<?= BO_URL ?>assets/css/lineicons.css" />
  <link rel="stylesheet" href="<?= BO_URL ?>assets/css/main.css" />
  <link rel="stylesheet" href="<?= BO_URL ?>assets/css/custom.css" />
</head>
<body>
  <main style="padding:60px 0">
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
          <div class="card-style">
            <h6 class="mb-15">Administration Tunisie2050</h6>
            <p class="text-sm mb-25">Connectez-vous pour gérer les régions, les projections et les utilisateurs.</p>
            <?php if (!empty($flash)): ?>
              <div class="alert-box danger-alert"><div class="alert"><p class="text-medium"><?= h($flash['msg']) ?></p></div></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
              <div class="alert-box danger-alert"><div class="alert"><p class="text-medium"><?= h($error) ?></p></div></div>
            <?php endif; ?>
            <form method="post" action="<?= admin_url(['page' => 'login']) ?>" data-validate novalidate>
              <?= csrf_field() ?>
              <div class="input-style-1">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="admin@tunisie2050.tn" maxlength="100"
                       value="<?= h($email) ?>" required autofocus autocomplete="username" />
              </div>
              <div class="input-style-1">
                <label for="pass">Mot de passe</label>
                <input type="password" id="pass" name="pass" placeholder="Mot de passe" maxlength="72"
                       required autocomplete="current-password" />
              </div>
              <button class="main-btn primary-btn btn-hover w-100 text-center">Se connecter</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="<?= BO_URL ?>assets/js/validation.js"></script>
</body>
</html>
