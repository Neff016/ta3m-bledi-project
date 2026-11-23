<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/partials/header.php'; ?>
    <?php if ($message = flash('success')): ?><div class="container alert success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($message = flash('error')): ?><div class="container alert error"><?= e($message) ?></div><?php endif; ?>
    <main><?= $content ?></main>
    <?php require __DIR__ . '/partials/footer.php'; ?>
</div>
<?php require __DIR__ . '/partials/scripts.php'; ?>
</body>
</html>
