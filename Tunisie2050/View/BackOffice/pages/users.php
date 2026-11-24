<div class="title-wrapper pt-30">
  <div class="row align-items-center">
    <div class="col-md-6"><div class="title"><h2>Utilisateurs</h2></div></div>
    <div class="col-md-6">
      <div class="breadcrumb-wrapper"><nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= admin_url(['page' => 'dashboard']) ?>">Tableau de bord</a></li>
        <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
      </ol></nav></div>
    </div>
  </div>
</div>

<div class="card-style mb-30">
  <div class="d-flex justify-content-between align-items-center mb-20">
    <h6 class="mb-0">Liste des utilisateurs</h6>
    <a class="main-btn primary-btn btn-hover btn-sm" href="<?= admin_url(['page' => 'user-form']) ?>"><i class="lni lni-plus me-1"></i> Ajouter</a>
  </div>
  <div class="table-wrapper table-responsive">
    <table class="table striped-table">
      <thead>
        <tr><th><h6>#</h6></th><th><h6>Nom</h6></th><th><h6>E-mail</h6></th><th><h6>Téléphone</h6></th>
            <th><h6>Rôle</h6></th><th><h6>Statut</h6></th><th><h6>Inscrit le</h6></th><th><h6>Actions</h6></th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): $mine = (int)$u['idUtilisateur'] === $me; ?>
        <tr>
          <td><p><?= (int)$u['idUtilisateur'] ?></p></td>
          <td><p><?= h($u['prenom'] . ' ' . $u['nom']) ?><?= $mine ? ' <small class="text-muted">(vous)</small>' : '' ?></p></td>
          <td><p><?= h($u['email']) ?></p></td>
          <td><p><?= h($u['telephone'] ?: '—') ?></p></td>
          <td><span class="badge-role <?= h($u['role']) ?>"><?= h($u['role']) ?></span></td>
          <td><span class="badge-statut <?= $u['statut'] === 'actif' ? 'actif' : 'bloque' ?>"><?= h($u['statut']) ?></span></td>
          <td><p><?= h($u['dateInscription']) ?></p></td>
          <td>
            <div class="action d-flex align-items-center gap-2">
              <a class="text-primary" title="Modifier" href="<?= admin_url(['page' => 'user-form', 'id' => (int)$u['idUtilisateur']]) ?>"><i class="lni lni-pencil"></i></a>
              <?php if (!$mine): ?>
                <form method="post" action="<?= admin_url(['page' => 'user-toggle']) ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$u['idUtilisateur'] ?>">
                  <button class="text-warning" title="<?= $u['statut'] === 'actif' ? 'Bloquer' : 'Débloquer' ?>">
                    <i class="lni <?= $u['statut'] === 'actif' ? 'lni-lock-alt' : 'lni-unlock' ?>"></i>
                  </button>
                </form>
                <form method="post" action="<?= admin_url(['page' => 'user-delete']) ?>" class="d-inline"
                      onsubmit="return confirm('Supprimer cet utilisateur ?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$u['idUtilisateur'] ?>">
                  <button class="text-danger" title="Supprimer"><i class="lni lni-trash-can"></i></button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
