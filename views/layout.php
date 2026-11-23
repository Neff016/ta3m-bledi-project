<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Taam Bledi : préservation des variétés agricoles tunisiennes.">
    <title><?= htmlspecialchars($title ?? 'Taam Bledi', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="views/assets/css/app.css">
    <link rel="stylesheet" href="views/assets/css/plantations.css">
    <script src="views/assets/js/app.js" defer></script>
    <script src="views/assets/js/plantation-validation.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php?action=home">Taam Bledi</a>
        <button class="menu-toggle" type="button" aria-label="Ouvrir la navigation" aria-expanded="false">☰</button>
        <nav class="site-nav" aria-label="Navigation principale">
            <a href="index.php?action=dashboard">Dashboard</a>
            <a href="index.php?action=plantations">Plantations</a>
            <a href="index.php?action=suivis">Suivi</a>
            <a href="index.php?action=admin_plantations">Gestion</a>
        </nav>
    </header>
    <main class="main-content">
        <?= $content ?>
    </main>
    <footer class="site-footer">
        <span>Taam Bledi · Préservation des variétés agricoles tunisiennes</span>
    </footer>
</body>
</html>
