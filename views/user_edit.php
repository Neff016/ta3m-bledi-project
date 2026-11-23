<?php
ob_start();
?>
<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Backoffice</p>
        <h1>Modifier un utilisateur</h1>
    </div>
</section>

<section class="form-panel">
    <h2 class="section-title">Informations du compte</h2>
    <?php if (!empty($errors)): ?>
        <div class="flash flash-error" role="alert">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="POST" action="index.php?action=user_edit&amp;id=<?= $editedUser->getId() ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" value="<?= $editedUser->getId() ?>">
        <div class="form-field">
            <label for="username">Nom d’utilisateur <span>*</span></label>
            <input type="text" id="username" name="username" maxlength="50" required value="<?= htmlspecialchars($editedUser->getUsername(), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-field">
            <label for="email">Adresse e-mail <span>*</span></label>
            <input type="email" id="email" name="email" maxlength="150" required value="<?= htmlspecialchars($editedUser->getEmail(), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-field">
            <label for="password">Nouveau mot de passe</label>
            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password">
            <small>Laisser vide pour conserver le mot de passe actuel.</small>
        </div>
        <div class="form-field">
            <label for="role">Rôle <span>*</span></label>
            <select id="role" name="role">
                <option value="user" <?= $editedUser->getRole() === 'user' ? 'selected' : '' ?>>Utilisateur</option>
                <option value="admin" <?= $editedUser->getRole() === 'admin' ? 'selected' : '' ?>>Administrateur</option>
            </select>
        </div>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Enregistrer</button>
            <a class="button button-light" href="index.php?action=users">Annuler</a>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
$title = 'Modifier utilisateur | Taam Bledi';
$activePage = 'users';
require __DIR__ . '/layout_admin.php';
