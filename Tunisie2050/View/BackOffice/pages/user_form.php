<?php $isEdit = !empty($u['idUtilisateur']); ?>
<div class="title-wrapper pt-30">
  <div class="row align-items-center">
    <div class="col-md-6"><div class="title"><h2><?= $isEdit ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' ?></h2></div></div>
    <div class="col-md-6">
      <div class="breadcrumb-wrapper"><nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= admin_url(['page' => 'users']) ?>">Utilisateurs</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Modifier' : 'Ajouter' ?></li>
      </ol></nav></div>
    </div>
  </div>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert-box danger-alert"><div class="alert"><p class="text-medium"><?= h($err) ?></p></div></div>
<?php endforeach; ?>

<div class="card-style mb-30">
  <form method="post" action="<?= admin_url(['page' => 'user-save']) ?>" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="idUtilisateur" value="<?= (int)($u['idUtilisateur'] ?? 0) ?>">
    <div class="row">
      <div class="col-md-6"><div class="input-style-1"><label>Nom</label>
        <input type="text" name="nom" required minlength="2" maxlength="50"
               pattern="\p{L}[\p{L} '\-]+" data-msg="Lettres, espaces, apostrophes et tirets uniquement."
               value="<?= h($u['nom'] ?? '') ?>" /></div></div>
      <div class="col-md-6"><div class="input-style-1"><label>Prénom</label>
        <input type="text" name="prenom" required minlength="2" maxlength="50"
               pattern="\p{L}[\p{L} '\-]+" data-msg="Lettres, espaces, apostrophes et tirets uniquement."
               value="<?= h($u['prenom'] ?? '') ?>" /></div></div>
      <div class="col-md-6"><div class="input-style-1"><label>E-mail</label>
        <input type="email" name="email" required maxlength="100" value="<?= h($u['email'] ?? '') ?>" /></div></div>
      <div class="col-md-6"><div class="input-style-1"><label>Téléphone (8 chiffres, facultatif)</label>
        <input type="text" name="telephone" maxlength="8" pattern="\d{8}" data-msg="Le téléphone doit contenir 8 chiffres."
               value="<?= h($u['telephone'] ?? '') ?>" /></div></div>
      <div class="col-md-4">
        <div class="select-style-1"><label>Rôle</label>
          <div class="select-position">
            <select name="role" required <?= !empty($isMe) ? 'disabled' : '' ?>>
              <?php foreach (Utilisateur::ROLES as $r): ?>
                <option <?= ($u['role'] ?? 'utilisateur') === $r ? 'selected' : '' ?>><?= h($r) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="select-style-1"><label>Statut</label>
          <div class="select-position">
            <select name="statut" required <?= !empty($isMe) ? 'disabled' : '' ?>>
              <?php foreach (Utilisateur::STATUTS as $s): ?>
                <option <?= ($u['statut'] ?? 'actif') === $s ? 'selected' : '' ?>><?= h($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="col-md-4"><div class="input-style-1">
        <label>Mot de passe<?= $isEdit ? ' (laisser vide pour ne pas changer)' : '' ?></label>
        <input type="password" name="motDePasse" maxlength="72" autocomplete="new-password"
               <?= $isEdit ? '' : 'required' ?> minlength="8"
               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,72}"
               data-msg="8 caractères minimum avec majuscule, minuscule et chiffre." /></div></div>
      <?php if (!empty($isMe)): ?>
        <input type="hidden" name="role" value="<?= h($u['role']) ?>">
        <input type="hidden" name="statut" value="<?= h($u['statut']) ?>">
        <div class="col-12"><p class="text-sm text-muted">Vous ne pouvez pas modifier votre propre rôle ni votre statut.</p></div>
      <?php endif; ?>
      <div class="col-12">
        <button class="main-btn primary-btn btn-hover">Enregistrer</button>
        <a class="main-btn light-btn btn-hover ms-2" href="<?= admin_url(['page' => 'users']) ?>">Annuler</a>
      </div>
    </div>
  </form>
</div>
