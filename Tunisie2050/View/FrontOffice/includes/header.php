<?php $currentPage = $currentPage ?? ''; ?>
<header class="site-header">
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand brand" href="<?= e(url(['page' => 'tounes'])) ?>">Taam Bledi</a>

      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
              data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
        <i class="bi bi-list fs-2" style="color: var(--green-900)"></i>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="mainNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'tounes' ? 'active' : '' ?>"
               href="<?= e(url(['page' => 'tounes'])) ?>">Tunisie2050</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'cultures' ? 'active' : '' ?>"
               href="<?= e(url(['page' => 'cultures'])) ?>">Cultures menacées</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>