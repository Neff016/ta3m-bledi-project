<?php $isEdit = !empty($r['idVille']); ?>
<div class="title-wrapper pt-30">
  <div class="row align-items-center">
    <div class="col-md-6"><div class="title"><h2><?= $isEdit ? 'Modifier la région' : 'Nouvelle région' ?></h2></div></div>
    <div class="col-md-6">
      <div class="breadcrumb-wrapper"><nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= admin_url(['page' => 'regions']) ?>">Régions</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Modifier' : 'Ajouter' ?></li>
      </ol></nav></div>
    </div>
  </div>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert-box danger-alert"><div class="alert"><p class="text-medium"><?= h($err) ?></p></div></div>
<?php endforeach; ?>

<div class="card-style mb-30">
  <form method="post" action="<?= admin_url(['page' => 'region-save']) ?>" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="idVille" value="<?= (int)($r['idVille'] ?? 0) ?>">
    <div class="row">
      <div class="col-md-6"><div class="input-style-1"><label>Nom</label>
        <input type="text" name="nom" required minlength="2" maxlength="60" value="<?= h($r['nom'] ?? '') ?>" /></div></div>
      <div class="col-md-6"><div class="input-style-1"><label>Gouvernorat</label>
        <input type="text" name="gouvernorat" required minlength="2" maxlength="60" value="<?= h($r['gouvernorat'] ?? '') ?>" /></div></div>
      <div class="col-md-6"><div class="input-style-1"><label>Latitude</label>
        <input type="number" name="latitude" required step="any" min="-90" max="90" value="<?= h($r['latitude'] ?? '') ?>" /></div></div>
      <div class="col-md-6"><div class="input-style-1"><label>Longitude</label>
        <input type="number" name="longitude" required step="any" min="-180" max="180" value="<?= h($r['longitude'] ?? '') ?>" /></div></div>
      <div class="col-12"><div class="input-style-1"><label>Description</label>
        <textarea name="description" rows="3" maxlength="300"><?= h($r['description'] ?? '') ?></textarea></div></div>
      <div class="col-12"><div class="input-style-1"><label>Image (nom du fichier, ex. tunis.jpg)</label>
        <input type="text" name="image" maxlength="80" pattern="[\w\-]+\.(jpg|jpeg|png|webp)" data-msg="Nom de fichier invalide (ex. tunis.jpg)." value="<?= h($r['image'] ?? '') ?>" /></div></div>
      <div class="col-12">
        <button class="main-btn primary-btn btn-hover">Enregistrer</button>
        <a class="main-btn light-btn btn-hover ms-2" href="<?= admin_url(['page' => 'regions']) ?>">Annuler</a>
      </div>
    </div>
  </form>
</div>
