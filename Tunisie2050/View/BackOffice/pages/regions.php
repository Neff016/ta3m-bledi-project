<div class="title-wrapper pt-30">
  <div class="row align-items-center">
    <div class="col-md-6"><div class="title"><h2>Régions</h2></div></div>
    <div class="col-md-6">
      <div class="breadcrumb-wrapper"><nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= admin_url(['page' => 'dashboard']) ?>">Tableau de bord</a></li>
        <li class="breadcrumb-item active" aria-current="page">Régions</li>
      </ol></nav></div>
    </div>
  </div>
</div>

<div class="card-style mb-30">
  <div class="d-flex justify-content-between align-items-center mb-20">
    <h6 class="mb-0">Liste des régions</h6>
    <a class="main-btn primary-btn btn-hover btn-sm" href="<?= admin_url(['page' => 'region-form']) ?>"><i class="lni lni-plus me-1"></i> Ajouter</a>
  </div>
  <div class="table-wrapper table-responsive">
    <table class="table striped-table">
      <thead>
        <tr><th><h6>#</h6></th><th><h6>Nom</h6></th><th><h6>Gouvernorat</h6></th><th><h6>Lat / Lng</h6></th><th><h6>Actions</h6></th></tr>
      </thead>
      <tbody>
      <?php foreach ($villes as $v): ?>
        <tr>
          <td><p><?= (int)$v['idVille'] ?></p></td>
          <td><p><?= h($v['nom']) ?></p></td>
          <td><p><?= h($v['gouvernorat']) ?></p></td>
          <td><p><?= h($v['latitude']) ?> / <?= h($v['longitude']) ?></p></td>
          <td>
            <div class="action d-flex align-items-center gap-2">
              <a class="text-primary" title="Modifier" href="<?= admin_url(['page' => 'region-form', 'id' => (int)$v['idVille']]) ?>"><i class="lni lni-pencil"></i></a>
              <form method="post" action="<?= admin_url(['page' => 'region-delete']) ?>" class="d-inline"
                    onsubmit="return confirm('Supprimer cette région et toutes ses projections ?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$v['idVille'] ?>">
                <button class="text-danger" title="Supprimer"><i class="lni lni-trash-can"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
