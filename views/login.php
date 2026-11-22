<?php
ob_start();
?>
<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Ravi de vous revoir</p>
        <h1>Connexion</h1>
    </div>
</section>

<section class="form-panel auth-panel">
    <p class="form-intro">Connectez-vous pour accéder à votre espace Taam Bledi.</p>

    <?php if (!empty($errors)): ?>
        <div class="flash flash-error" role="alert">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['registered'])): ?>
        <div class="flash flash-success" role="status">Votre compte a été créé. Vous pouvez maintenant vous connecter.</div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=login">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-field">
            <label for="email">Adresse e-mail <span>*</span></label>
            <input type="email" id="email" name="email" required autocomplete="email">
        </div>
        <div class="form-field">
            <label for="password">Mot de passe <span>*</span></label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Se connecter</button>
        </div>
    </form>

    <p class="auth-links">Pas encore de compte ? <a href="index.php?action=register">Créer un compte</a></p>
</section>
<?php
$content = ob_get_clean();
$title = 'Connexion | Taam Bledi';
require __DIR__ . '/layout.php';
