<?php
/**
 * views/user_space.php
 * Expects: $user (UserModel)
 */
ob_start();
?>
<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Mon espace Taam Bledi</p>
        <h1>Bienvenue, <?= htmlspecialchars($user->getUsername(), ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
</section>
<section class="form-panel">
    <p class="form-intro">Votre compte est prêt. Retrouvez ici vos informations de profil.</p>
    <div class="table-wrap">
        <table>
            <tbody>
                <tr><th>ID</th><td><?= $user->getId() ?></td></tr>
                <tr><th>Nom d’utilisateur</th><td><?= htmlspecialchars($user->getUsername(), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Adresse e-mail</th><td><?= htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Inscription</th><td><?= htmlspecialchars($user->getCreatedAt(), ENT_QUOTES, 'UTF-8') ?></td></tr>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Mon espace | Taam Bledi';
require __DIR__ . '/layout.php';
