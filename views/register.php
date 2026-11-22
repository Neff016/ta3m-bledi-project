<?php
ob_start();
$old = $old ?? [];
?>
<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Bienvenue chez Taam Bledi</p>
        <h1>Créer un compte</h1>
    </div>
</section>

<section class="form-panel auth-panel">
    <p class="form-intro">Créez votre compte pour découvrir et suivre vos plantations.</p>

    <?php if (!empty($errors)): ?>
        <div class="flash flash-error" role="alert">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=register">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-field">
            <label for="username">Nom d’utilisateur <span>*</span></label>
            <input type="text" id="username" name="username" minlength="3" maxlength="50" pattern="[a-zA-Z0-9_\-]{3,50}" required autocomplete="username" value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-field">
            <label for="email">Adresse e-mail <span>*</span></label>
            <input type="email" id="email" name="email" maxlength="150" required autocomplete="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-field">
            <label for="password">Mot de passe <span>*</span></label>
            <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
            <small>8 caractères minimum.</small>
        </div>
        <div class="form-field">
            <label for="password_confirm">Confirmer le mot de passe <span>*</span></label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
        </div>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Créer mon compte</button>
        </div>
    </form>
    <p class="auth-links">Vous avez déjà un compte ? <a href="index.php?action=login">Se connecter</a></p>
</section>
<?php
$content = ob_get_clean();
$title = 'Créer un compte | Taam Bledi';
require __DIR__ . '/layout.php';
