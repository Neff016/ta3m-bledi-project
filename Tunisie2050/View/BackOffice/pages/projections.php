<div class="title-wrapper pt-30">
  <div class="row align-items-center">
    <div class="col-md-6"><div class="title"><h2>Projections</h2></div></div>
    <div class="col-md-6">
      <div class="breadcrumb-wrapper"><nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= admin_url(['page' => 'dashboard']) ?>">Tableau de bord</a></li>
        <li class="breadcrumb-item active" aria-current="page">Projections</li>
      </ol></nav></div>
    </div>
  </div>
</div>

<div class="card-style mb-30">
  <div class="d-flex justify-content-between align-items-center mb-20">
    <h6 class="mb-0">Liste des projections</h6>
    <a class="main-btn primary-btn btn-hover btn-sm" href="<?= admin_url(['page' => 'projection-form']) ?>"><i class="lni lni-plus me-1"></i> Ajouter</a>
  </div>
  <div class="table-wrapper table-responsive">
    <table class="table striped-table">
      <thead>
        <tr><th><h6>Région</h6></th><th><h6>Année</h6></th><th><h6>Temp. (°C)</h6></th><th><h6>Pluie (mm)</h6></th>
            <th><h6>Stress</h6></th><th><h6>Cultures menacées</h6></th><th><h6>Actions</h6></th></tr>
      </thead>
      <tbody>
      <?php foreach ($projs as $p): ?>
        <tr>
          <td><p><?= h($villes[(int)$p['idVille']] ?? '—') ?></p></td>
          <td><p><?= (int)$p['annee'] ?></p></td>
          <td><p><?= h($p['temperatureMoy']) ?></p></td>
          <td><p><?= h($p['pluviometrie']) ?></p></td>
          <td><p><?= h($p['stressHydrique']) ?></p></td>
          <td><p><?= h($p['culturesMenacees']) ?></p></td>
          <td>
            <div class="action d-flex align-items-center gap-2">
              <a class="text-primary" title="Modifier" href="<?= admin_url(['page' => 'projection-form', 'id' => (int)$p['idProjection']]) ?>"><i class="lni lni-pencil"></i></a>
              <form method="post" action="<?= admin_url(['page' => 'projection-delete']) ?>" class="d-inline"
                    onsubmit="return confirm('Supprimer cette projection ?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['idProjection'] ?>">
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
