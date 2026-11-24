<!DOCTYPE html>
<html lang="fr">
<head>
  <?php require __DIR__ . '/includes/head.php'; ?>
</head>
<body>
  <?php require __DIR__ . '/includes/header.php'; ?>

  <main class="container pb-5">
    <?= $content ?>
  </main>

  <?php require __DIR__ . '/includes/footer.php'; ?>
  <?php require __DIR__ . '/includes/scripts.php'; ?>
</body>
</html>