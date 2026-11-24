<?php
/**
 * Layout principal du Back Office : assemble les partials et injecte $content.
 * Variables attendues (fournies par Controller::render) :
 *   $title, $currentPage, $content, $flash (optionnel), $extraScripts (optionnel)
 */
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/sidebar.php';
?>
<!-- ======== main-wrapper start =========== -->
<main class="main-wrapper">
  <?php require __DIR__ . '/partials/header.php'; ?>

  <section class="section">
    <div class="container-fluid">
      <?php if (!empty($flash)): ?>
        <div class="alert-box <?= $flash['type'] === 'success' ? 'success-alert' : 'danger-alert' ?> mt-30">
          <div class="alert"><p class="text-medium"><?= htmlspecialchars($flash['msg']) ?></p></div>
        </div>
      <?php endif; ?>

      <?= $content ?>
    </div>
  </section>

  <?php require __DIR__ . '/partials/footer.php'; ?>
</main>
<!-- ======== main-wrapper end =========== -->
<?php require __DIR__ . '/partials/scripts.php'; ?>
