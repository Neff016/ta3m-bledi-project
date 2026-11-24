<!-- ========== header start ========== -->
<header class="header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-5 col-md-5 col-6">
        <div class="header-left d-flex align-items-center">
          <div class="menu-toggle-btn mr-15">
            <button id="menu-toggle" class="main-btn primary-btn btn-hover">
              <i class="lni lni-chevron-left me-2"></i> Menu
            </button>
          </div>
        </div>
      </div>
      <div class="col-lg-7 col-md-7 col-6">
        <div class="header-right">
          <div class="profile-box ml-15">
            <span class="text-medium"><i class="lni lni-user"></i>
              <?= h(trim(($currentUser['prenom'] ?? '') . ' ' . ($currentUser['nom'] ?? ''))) ?: 'Administrateur' ?>
              <small class="text-muted">(<?= h($currentUser['role'] ?? 'admin') ?>)</small>
            </span>
            <a href="#" class="ms-3 text-sm" onclick="document.getElementById('logout-form').submit(); return false;"><i class="lni lni-exit"></i> Déconnexion</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <form id="logout-form" method="post" action="<?= admin_url(['page' => 'logout']) ?>" class="d-none">
    <?= csrf_field() ?>
  </form>
</header>
<!-- ========== header end ========== -->
