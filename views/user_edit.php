<?php
ob_start();
?>
<div class="card">
    <h2>Edit user</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=user_edit&amp;id=<?= $editedUser->getId() ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" value="<?= $editedUser->getId() ?>">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($editedUser->getUsername(), ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($editedUser->getEmail(), ENT_QUOTES, 'UTF-8') ?>">
        <label for="password">New password (leave blank to keep it)</label>
        <input type="password" id="password" name="password" minlength="8">
        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="user" <?= $editedUser->getRole() === 'user' ? 'selected' : '' ?>>user</option>
            <option value="admin" <?= $editedUser->getRole() === 'admin' ? 'selected' : '' ?>>admin</option>
        </select>
        <button type="submit">Update user</button>
    </form>
</div>
<?php
$content = ob_get_clean();
$title = 'Edit user';
$activePage = 'users';
require __DIR__ . '/layout_admin.php';
