<?php
ob_start();
?>
<div class="card">
    <h2>User management</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=users">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8">
        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="user" <?= ($old['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>user</option>
            <option value="admin" <?= ($old['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>admin</option>
        </select>
        <button type="submit">Create user</button>
    </form>

    <table>
        <thead>
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($allUsers as $u): ?>
            <tr>
                <td><?= $u->getId() ?></td>
                <td><?= htmlspecialchars($u->getUsername(), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u->getEmail(), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u->getRole(), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <a href="index.php?action=user_edit&amp;id=<?= $u->getId() ?>">Edit</a>
                    <?php if ($u->getId() !== (int) $_SESSION['user_id']): ?>
                    <form method="POST" action="index.php?action=user_delete" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= $u->getId() ?>">
                        <button type="submit" onclick="return confirm('Delete this user?');">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'Users';
$activePage = 'users';
require __DIR__ . '/layout_admin.php';
