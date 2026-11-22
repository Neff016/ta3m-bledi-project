<?php
ob_start();
?>
<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Backoffice</p>
        <h1>Gestion des utilisateurs</h1>
    </div>
</section>

<section class="form-panel">
    <h2 class="section-title">Ajouter un utilisateur</h2>
    <?php if (!empty($errors)): ?>
        <div class="flash flash-error" role="alert">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="POST" action="index.php?action=users" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-field">
            <label for="username">Nom d’utilisateur <span>*</span></label>
            <input type="text" id="username" name="username" maxlength="50" required value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-field">
            <label for="email">Adresse e-mail <span>*</span></label>
            <input type="email" id="email" name="email" maxlength="150" required value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-field">
            <label for="password">Mot de passe <span>*</span></label>
            <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
        </div>
        <div class="form-field">
            <label for="role">Rôle <span>*</span></label>
            <select id="role" name="role">
                <option value="user" <?= ($old['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>Utilisateur</option>
                <option value="admin" <?= ($old['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>Administrateur</option>
            </select>
        </div>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Créer le compte</button>
        </div>
    </form>
</section>

<section class="admin-users">
    <h2 class="section-title">Comptes utilisateurs</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>ID</th><th>Nom d’utilisateur</th><th>Adresse e-mail</th><th>Rôle</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($allUsers as $u): ?>
                    <tr>
                        <td><?= $u->getId() ?></td>
                        <td><?= htmlspecialchars($u->getUsername(), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u->getEmail(), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="role-badge role-<?= htmlspecialchars($u->getRole(), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($u->getRole(), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <a class="button button-secondary" href="index.php?action=user_edit&amp;id=<?= $u->getId() ?>">Modifier</a>
                            <?php if ($u->getId() !== (int) $_SESSION['user_id']): ?>
                                <form method="POST" action="index.php?action=user_delete" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="id" value="<?= $u->getId() ?>">
                                    <button class="button button-danger" type="submit" onclick="return confirm('Supprimer cet utilisateur ?');">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Utilisateurs | Taam Bledi';
$activePage = 'users';
require __DIR__ . '/layout_admin.php';
