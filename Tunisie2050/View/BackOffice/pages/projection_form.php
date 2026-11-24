<?php $isEdit = !empty($p['idProjection']); ?>
<div class="title-wrapper pt-30">
  <div class="row align-items-center">
    <div class="col-md-6"><div class="title"><h2><?= $isEdit ? 'Modifier la projection' : 'Nouvelle projection' ?></h2></div></div>
    <div class="col-md-6">
      <div class="breadcrumb-wrapper"><nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= admin_url(['page' => 'projections']) ?>">Projections</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Modifier' : 'Ajouter' ?></li>
      </ol></nav></div>
    </div>
  </div>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert-box danger-alert"><div class="alert"><p class="text-medium"><?= h($err) ?></p></div></div>
<?php endforeach; ?>

<div class="card-style mb-30">
  <form method="post" action="<?= admin_url(['page' => 'projection-save']) ?>" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="idProjection" value="<?= (int)($p['idProjection'] ?? 0) ?>">
    <div class="row">
      <div class="col-md-6">
        <div class="select-style-1"><label>Région</label>
          <div class="select-position">
            <select name="idVille" required>
              <?php foreach ($villes as $v): ?>
                <option value="<?= (int)$v['idVille'] ?>" <?= (int)($p['idVille'] ?? 0) === (int)$v['idVille'] ? 'selected' : '' ?>><?= h($v['nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="select-style-1"><label>Stress hydrique</label>
          <div class="select-position">
            <select name="stressHydrique" required>
              <?php foreach (STRESS as $s): ?>
                <option <?= ($p['stressHydrique'] ?? '') === $s ? 'selected' : '' ?>><?= h($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="col-md-4"><div class="input-style-1"><label>Année</label>
        <input type="number" name="annee" required step="1" min="2000" max="2100" value="<?= h($p['annee'] ?? '') ?>" /></div></div>
      <div class="col-md-4"><div class="input-style-1"><label>Température moy. (°C)</label>
        <input type="number" name="temperatureMoy" required step="any" min="-10" max="60" value="<?= h($p['temperatureMoy'] ?? '') ?>" /></div></div>
      <div class="col-md-4"><div class="input-style-1"><label>Pluviométrie (mm)</label>
        <input type="number" name="pluviometrie" required step="any" min="0" max="3000" value="<?= h($p['pluviometrie'] ?? '') ?>" /></div></div>
      <div class="col-12"><div class="input-style-1"><label>Cultures menacées (séparées par des virgules)</label>
        <input type="text" name="culturesMenacees" maxlength="200" value="<?= h($p['culturesMenacees'] ?? '') ?>" placeholder="Blé dur, Orge" /></div></div>
      <div class="col-12"><div class="input-style-1"><label>Description</label>
        <textarea name="description" rows="3" maxlength="300"><?= h($p['description'] ?? '') ?></textarea></div></div>
      <div class="col-12">
        <button class="main-btn primary-btn btn-hover">Enregistrer</button>
        <a class="main-btn light-btn btn-hover ms-2" href="<?= admin_url(['page' => 'projections']) ?>">Annuler</a>
      </div>
    </div>
  </form>
</div>
