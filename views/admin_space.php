<?php
/**
 * views/admin_space.php
 * Backoffice dashboard - visually distinct from the frontoffice.
 */
ob_start();
?>
<div class="stats-grid">
    <article class="stat-card"><span>Comptes inscrits</span><strong><?= $totalUsers ?></strong></article>
    <article class="stat-card"><span>Utilisateurs</span><strong><?= $totalRegular ?></strong></article>
    <article class="stat-card"><span>Administrateurs</span><strong><?= $totalAdmins ?></strong></article>
</div>

<div class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Backoffice</p>
        <h1>Espace administrateur</h1>
    </div>
    <a class="button button-light" href="index.php?action=users">Gérer les utilisateurs</a>
</div>

<div class="form-actions" style="margin: 0 0 24px">
    <a class="button button-primary" href="index.php?action=admin_plantations">Gérer les plantations</a>
    <a class="button button-light" href="index.php?action=admin_suivis">Consulter les suivis</a>
</div>

<section class="admin-users">
    <h2 class="section-title">Comptes utilisateurs</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>ID</th><th>Nom d’utilisateur</th><th>Adresse e-mail</th><th>Rôle</th><th>Créé le</th></tr>
            </thead>
            <tbody>
                <?php foreach ($allUsers as $u): ?>
                    <tr>
                        <td><?= $u->getId() ?></td>
                        <td><?= htmlspecialchars($u->getUsername(), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u->getEmail(), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="role-badge role-<?= htmlspecialchars($u->getRole(), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($u->getRole(), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($u->getCreatedAt(), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/layout_admin.php';
