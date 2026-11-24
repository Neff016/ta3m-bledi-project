<?php
// $currentPage is provided by the controller (see Controller::render)
$currentPage = $currentPage ?? 'dashboard';

if (!function_exists('isActive')) {
    function isActive(string $page, string $currentPage): string
    {
        return $page === $currentPage ? 'active' : '';
    }
}
?>
<!-- ======== sidebar-nav start =========== -->
<aside class="sidebar-nav-wrapper">
  <div class="navbar-logo">
    <a href="<?= admin_url(['page' => 'dashboard']) ?>"><h4 class="m-0 text-primary">Tunisie2050</h4></a>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <li class="nav-item <?= isActive('dashboard', $currentPage) ?>">
        <a href="<?= admin_url(['page' => 'dashboard']) ?>" class="<?= isActive('dashboard', $currentPage) ?>">
          <span class="icon"><i class="lni lni-grid-alt"></i></span>
          <span class="text">Tableau de bord</span>
        </a>
      </li>
      <li class="nav-item <?= isActive('regions', $currentPage) ?>">
        <a href="<?= admin_url(['page' => 'regions']) ?>" class="<?= isActive('regions', $currentPage) ?>">
          <span class="icon"><i class="lni lni-map-marker"></i></span>
          <span class="text">Régions</span>
        </a>
      </li>
      <li class="nav-item <?= isActive('projections', $currentPage) ?>">
        <a href="<?= admin_url(['page' => 'projections']) ?>" class="<?= isActive('projections', $currentPage) ?>">
          <span class="icon"><i class="lni lni-bar-chart"></i></span>
          <span class="text">Projections</span>
        </a>
      </li>
      <li class="nav-item <?= isActive('users', $currentPage) ?>">
        <a href="<?= admin_url(['page' => 'users']) ?>" class="<?= isActive('users', $currentPage) ?>">
          <span class="icon"><i class="lni lni-users"></i></span>
          <span class="text">Utilisateurs</span>
        </a>
      </li>
      <span class="divider"><hr /></span>
      <li class="nav-item">
        <a href="<?= url(['page' => 'tounes']) ?>">
          <span class="icon"><i class="lni lni-world"></i></span>
          <span class="text">Voir le site</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="#" onclick="document.getElementById('logout-form').submit(); return false;">
          <span class="icon"><i class="lni lni-exit"></i></span>
          <span class="text">Déconnexion</span>
        </a>
      </li>
    </ul>
  </nav>
</aside>
<div class="overlay"></div>
<!-- ======== sidebar-nav end =========== -->
