<?php
/*
 * Formulaire Terrain (utilisé par ajouterTerrain.php ET modifierTerrain.php)
 * Variables attendues : $valeurs (tableau), $erreurs (tableau), $texteBouton (texte), $photoActuelle (ou null)
 * ⚠️ Pas de validation HTML5 (required, type="number"...) : contrôle en JS + PHP.
 */
?>
<form action="" method="POST" id="formTerrain" enctype="multipart/form-data" novalidate>
  <?= champCsrf() ?>
  <div class="row">

    <div class="col-md-6 mb-4">
      <label for="nom" class="form-label"><?= te('Nom du terrain') ?></label>
      <input type="text" class="form-control <?= isset($erreurs['nom']) ? 'is-invalid' : '' ?>"
             id="nom" name="nom" value="<?= e($valeurs['nom']) ?>" placeholder="<?= te('Ex : Terrain Zaghouan') ?>">
      <div class="erreur" id="erreur-nom"><?= te($erreurs['nom'] ?? '') ?></div>
    </div>

    <div class="col-md-6 mb-4">
      <label for="localisation" class="form-label"><?= te('Wilaya') ?></label>
      <select class="form-select <?= isset($erreurs['localisation']) ? 'is-invalid' : '' ?>"
              id="localisation" name="localisation">
        <option value=""><?= te('-- Choisir une wilaya --') ?></option>
        <?php foreach (TerrainController::WILAYAS as $region): ?>
          <option value="<?= e($region) ?>" <?= $valeurs['localisation'] === $region ? 'selected' : '' ?>>
            <?= e($region) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="erreur" id="erreur-localisation"><?= te($erreurs['localisation'] ?? '') ?></div>
    </div>

    <div class="col-md-6 mb-4">
      <label for="superficie" class="form-label"><?= te('Superficie (ha)') ?></label>
      <input type="text" class="form-control <?= isset($erreurs['superficie']) ? 'is-invalid' : '' ?>"
             id="superficie" name="superficie" value="<?= e($valeurs['superficie']) ?>" placeholder="<?= te('Ex : 2.5') ?>">
      <small class="text-muted"><?= te('Calculée automatiquement si vous dessinez la parcelle.') ?></small>
      <div class="erreur" id="erreur-superficie"><?= te($erreurs['superficie'] ?? '') ?></div>
    </div>

    <div class="col-md-6 mb-4">
      <label for="typeSol" class="form-label"><?= te('Type de sol') ?></label>
      <select class="form-select <?= isset($erreurs['typeSol']) ? 'is-invalid' : '' ?>"
              id="typeSol" name="typeSol">
        <option value=""><?= te('-- Choisir --') ?></option>
        <?php foreach (TerrainController::TYPES_SOL as $type): ?>
          <option value="<?= e($type) ?>" <?= $valeurs['typeSol'] === $type ? 'selected' : '' ?>>
            <?= te($type) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="erreur" id="erreur-typeSol"><?= te($erreurs['typeSol'] ?? '') ?></div>
    </div>

    <!-- Carte : placer le terrain ou dessiner la parcelle -->
    <div class="col-12 mb-4">
      <label class="form-label"><?= te('Position du terrain (facultatif)') ?></label>
      <div class="tb-outils-carte">
        <button type="button" class="tb-outil actif" data-mode="point"><i class="bi bi-geo-alt"></i> <?= te('Placer un point') ?></button>
        <button type="button" class="tb-outil" data-mode="parcelle"><i class="bi bi-pentagon"></i> <?= te('Dessiner la parcelle') ?></button>
        <button type="button" class="tb-outil" id="btnTerminerParcelle" hidden><i class="bi bi-check2"></i> <?= te('Terminer la parcelle') ?></button>
        <button type="button" class="tb-outil" id="btnMaPosition"><i class="bi bi-crosshair"></i> <?= te('Ma position') ?></button>
        <button type="button" class="tb-outil danger" id="btnEffacerCarte"><i class="bi bi-eraser"></i> <?= te('Effacer') ?></button>
      </div>
      <div id="carteSaisie" class="tb-carte tb-carte-saisie"
           data-centres='<?= e(json_encode(TerrainController::CENTRES)) ?>'
           data-texte-aide-point="<?= te('Cliquez sur la carte pour placer votre terrain.') ?>"
           data-texte-aide-parcelle="<?= te('Cliquez sur chaque coin de la parcelle, puis sur « Terminer la parcelle ».') ?>"
           data-texte-surface="<?= te('Surface calculée : {ha} ha') ?>"></div>
      <p id="aideCarte" class="tb-aide-carte"><i class="bi bi-info-circle"></i> <?= te('Cliquez sur la carte pour placer votre terrain.') ?></p>
      <input type="hidden" id="latitude" name="latitude" value="<?= e($valeurs['latitude']) ?>">
      <input type="hidden" id="longitude" name="longitude" value="<?= e($valeurs['longitude']) ?>">
      <input type="hidden" id="polygone" name="polygone" value="<?= e($valeurs['polygone']) ?>">
      <div class="erreur" id="erreur-position"><?= te($erreurs['position'] ?? '') ?></div>
    </div>

    <!-- Photo -->
    <div class="col-md-8 mb-4">
      <label for="photo" class="form-label"><?= te('Photo du terrain (facultatif)') ?></label>
      <input type="file" class="form-control <?= isset($erreurs['photo']) ? 'is-invalid' : '' ?>" id="photo" name="photo" accept="image/*">
      <small class="text-muted"><?= te('JPG, PNG, WEBP ou GIF, 2 Mo maximum.') ?></small>
      <div class="erreur" id="erreur-photo"><?= te($erreurs['photo'] ?? '') ?></div>
      <?php if (!empty($photoActuelle)): ?>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" id="supprimerPhoto" name="supprimerPhoto" value="1">
          <label class="form-check-label" for="supprimerPhoto"><?= te('Retirer la photo actuelle') ?></label>
        </div>
      <?php endif; ?>
    </div>
    <div class="col-md-4 mb-4 text-center">
      <img id="apercuPhotoTerrain" class="tb-apercu-photo <?= empty($photoActuelle) ? 'd-none' : '' ?>" alt=""
           src="<?= !empty($photoActuelle) ? 'assets/img/uploads/terrains/' . e($photoActuelle) : '' ?>">
    </div>

  </div>

  <div class="pied">
    <a href="mesTerrains.php" class="lien-details"><?= te('Annuler') ?></a>
    <button type="submit" class="btn-hero border-0"><?= te($texteBouton) ?></button>
  </div>
</form>
