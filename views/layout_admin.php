<?php
$activePage = $activePage ?? 'dashboard';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Espace d’administration Taam Bledi.">
    <title><?= htmlspecialchars($title ?? 'Administration | Taam Bledi', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="views/assets/css/app.css">
    <script src="views/assets/js/app.js" defer></script>
    <script src="views/assets/js/auth-validation.js" defer></script>
</head>
<body class="admin-body">
    <header class="admin-header">
        <a class="admin-brand" href="index.php?action=admin">Taam Bledi</a>
        <span class="admin-label">BACKOFFICE</span>
        <div class="admin-account">
            <span><?= htmlspecialchars($user->getUsername(), ENT_QUOTES, 'UTF-8') ?></span>
            <a class="admin-logout" href="index.php?action=logout">Déconnexion</a>
        </div>
    </header>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <p class="admin-sidebar-title">Gestion</p>
            <nav aria-label="Navigation administration">
                <a href="index.php?action=admin" <?= $activePage === 'dashboard' ? 'class="active" aria-current="page"' : '' ?>>Tableau de bord</a>
                <a href="index.php?action=users" <?= $activePage === 'users' ? 'class="active" aria-current="page"' : '' ?>>Utilisateurs</a>
                <a href="index.php?action=dashboard">Espace utilisateur</a>
            </nav>
        </aside>
        <main class="admin-main">
            <?= $content ?>
        </main>
    </div>
</body>
</html>
