<div class="title-wrapper pt-30">
  <div class="row align-items-center">
    <div class="col-md-6"><div class="title"><h2>Tableau de bord</h2></div></div>
    <div class="col-md-6">
      <div class="breadcrumb-wrapper"><nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
      </ol></nav></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-3 col-lg-3 col-sm-6">
    <div class="icon-card mb-30">
      <div class="icon purple"><i class="lni lni-map-marker"></i></div>
      <div class="content"><h6 class="mb-10">Régions</h6><h3 class="text-bold mb-10"><?= count($villes) ?></h3></div>
    </div>
  </div>
  <div class="col-xl-3 col-lg-3 col-sm-6">
    <div class="icon-card mb-30">
      <div class="icon success"><i class="lni lni-bar-chart"></i></div>
      <div class="content"><h6 class="mb-10">Projections</h6><h3 class="text-bold mb-10"><?= count($projs) ?></h3></div>
    </div>
  </div>
  <div class="col-xl-3 col-lg-3 col-sm-6">
    <div class="icon-card mb-30">
      <div class="icon primary"><i class="lni lni-users"></i></div>
      <div class="content"><h6 class="mb-10">Utilisateurs</h6><h3 class="text-bold mb-10"><?= count($users) ?></h3></div>
    </div>
  </div>
  <div class="col-xl-3 col-lg-3 col-sm-6">
    <div class="icon-card mb-30">
      <div class="icon danger"><i class="lni lni-warning"></i></div>
      <div class="content"><h6 class="mb-10">Stress critique</h6><h3 class="text-bold mb-10"><?= $critiques ?></h3></div>
    </div>
  </div>
</div>

<form method="post" action="<?= admin_url(['page' => 'reset']) ?>"
      onsubmit="return confirm('Effacer les modifications des régions et projections et recharger les données initiales ?')">
  <?= csrf_field() ?>
  <button class="main-btn danger-btn-outline btn-hover btn-sm">Réinitialiser les données</button>
</form>
