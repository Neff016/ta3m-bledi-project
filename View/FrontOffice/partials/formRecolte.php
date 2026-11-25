<?php
/*
 * Formulaire Récolte (utilisé par ajouterRecolte.php ET modifierRecolte.php)
 * Variables attendues : $valeurs, $erreurs, $texteBouton, $terrains (terrains de l'utilisateur), $parcelles,
 *                       $nomsCultures (autocomplétion), $photoActuelle (ou null), $avertissement (ou null)
 * ⚠️ Pas de validation HTML5 (required…) : contrôle en JS + PHP.
 */
?>
<!-- Saisie vocale -->
<div class="tb-voix" id="zoneVoix">
  <button type="button" class="tb-btn-micro" id="btnMicro" title="<?= te('Dicter la récolte') ?>"><i class="bi bi-mic-fill"></i></button>
  <div class="flex-grow-1">
    <strong><?= te('Saisie vocale') ?></strong>
    <select id="langueVoix" class="form-select form-select-sm d-inline-block w-auto ms-2" aria-label="<?= te('Langue') ?>">
      <option value="ar-TN"><?= te('Derja / arabe') ?></option>
      <option value="fr-FR"><?= te('Français') ?></option>
    </select>
    <p class="mb-0 small text-muted" id="texteVoix"><?= te('Exemple : « zid récolte 200 kilo tmatem terrain Nabeul » ou « 2 tonnes de blé récoltées aujourd’hui »') ?></p>
  </div>
</div>

<form action="" method="POST" id="formRecolte" enctype="multipart/form-data" novalidate>
  <?= champCsrf() ?>

  <?php if (!empty($avertissement)): ?>
    <div class="alert alert-warning d-flex gap-2"><i class="bi bi-arrow-repeat fs-5"></i>
      <div><?= e($avertissement) ?>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" id="confirmerRotation" name="confirmerRotation" value="1">
          <label class="form-check-label" for="confirmerRotation"><?= te('Je confirme quand même') ?></label>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row">

    <div class="col-md-4 mb-4">
      <label for="idTerrain" class="form-label"><?= te('Terrain') ?></label>
      <select class="form-select <?= isset($erreurs['idTerrain']) ? 'is-invalid' : '' ?>" id="idTerrain" name="idTerrain">
        <option value=""><?= te('-- Choisir un terrain --') ?></option>
        <?php foreach ($terrains as $t): ?>
          <option value="<?= e($t['id']) ?>" data-wilaya="<?= e($t['localisation']) ?>"
                  <?= (string) $valeurs['idTerrain'] === (string) $t['id'] ? 'selected' : '' ?>>
            <?= e($t['nom']) ?> (<?= e($t['localisation']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <div class="erreur" id="erreur-idTerrain"><?= te($erreurs['idTerrain'] ?? '') ?></div>
    </div>

    <div class="col-md-4 mb-4">
      <label for="idParcelle" class="form-label"><?= te('Parcelle (facultatif)') ?></label>
      <select class="form-select <?= isset($erreurs['idParcelle']) ? 'is-invalid' : '' ?>" id="idParcelle" name="idParcelle">
        <option value=""><?= te('-- Tout le terrain --') ?></option>
        <?php foreach ($parcelles as $p): ?>
          <option value="<?= e($p['id']) ?>" data-terrain="<?= e($p['idTerrain']) ?>"
                  <?= (string) $valeurs['idParcelle'] === (string) $p['id'] ? 'selected' : '' ?>>
            <?= e($p['nom']) ?> (<?= e(CultureController::nombre((float) $p['surface'], 2)) ?> ha)
          </option>
        <?php endforeach; ?>
      </select>
      <small class="text-muted"><?= te('Sert au plan de rotation des cultures.') ?></small>
      <div class="erreur" id="erreur-idParcelle"><?= te($erreurs['idParcelle'] ?? '') ?></div>
    </div>

    <div class="col-md-4 mb-4">
      <label for="culture" class="form-label"><?= te('Culture') ?></label>
      <input type="text" class="form-control <?= isset($erreurs['culture']) ? 'is-invalid' : '' ?>"
             id="culture" name="culture" value="<?= e($valeurs['culture']) ?>" list="listeCultures"
             autocomplete="off" placeholder="<?= te('Tapez pour chercher : Blé dur, Tomate…') ?>">
      <datalist id="listeCultures">
        <?php foreach ($nomsCultures as $nom): ?>
          <option value="<?= e($nom) ?>"></option>
        <?php endforeach; ?>
      </datalist>
      <small class="text-muted"><?= te('Liste : catalogue des cultures et variétés du Musée.') ?></small>
      <div class="erreur" id="erreur-culture"><?= te($erreurs['culture'] ?? '') ?></div>
    </div>

    <div class="col-md-6 mb-4">
      <label for="datePlantation" class="form-label"><?= te('Date de plantation') ?></label>
      <input type="date" class="form-control <?= isset($erreurs['datePlantation']) ? 'is-invalid' : '' ?>"
             id="datePlantation" name="datePlantation" value="<?= e($valeurs['datePlantation']) ?>">
      <div class="erreur" id="erreur-datePlantation"><?= te($erreurs['datePlantation'] ?? '') ?></div>
    </div>

    <div class="col-md-6 mb-4">
      <label for="dateRecolte" class="form-label"><?= te('Date de récolte') ?></label>
      <input type="date" class="form-control <?= isset($erreurs['dateRecolte']) ? 'is-invalid' : '' ?>"
             id="dateRecolte" name="dateRecolte" value="<?= e($valeurs['dateRecolte']) ?>">
      <div class="erreur" id="erreur-dateRecolte"><?= te($erreurs['dateRecolte'] ?? '') ?></div>
    </div>

    <div class="col-md-4 mb-4">
      <label for="quantite" class="form-label"><?= te('Quantité') ?></label>
      <input type="text" class="form-control <?= isset($erreurs['quantite']) ? 'is-invalid' : '' ?>"
             id="quantite" name="quantite" value="<?= e($valeurs['quantite']) ?>" placeholder="<?= te('Ex : 250') ?>">
      <div class="erreur" id="erreur-quantite"><?= te($erreurs['quantite'] ?? '') ?></div>
    </div>

    <div class="col-md-2 mb-4">
      <label for="unite" class="form-label"><?= te('Unité') ?></label>
      <select class="form-select <?= isset($erreurs['unite']) ? 'is-invalid' : '' ?>" id="unite" name="unite">
        <?php foreach (RecolteController::UNITES as $u): ?>
          <option value="<?= e($u) ?>" <?= $valeurs['unite'] === $u ? 'selected' : '' ?>><?= te($u) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="erreur" id="erreur-unite"><?= te($erreurs['unite'] ?? '') ?></div>
    </div>

    <div class="col-md-6 mb-4">
      <label for="photo" class="form-label"><?= te('Photo de la récolte (facultatif)') ?></label>
      <input type="file" class="form-control <?= isset($erreurs['photo']) ? 'is-invalid' : '' ?>" id="photo" name="photo" accept="image/*">
      <small class="text-muted"><?= te('Elle apparaît sur la page publique du QR code.') ?></small>
      <div class="erreur" id="erreur-photo"><?= te($erreurs['photo'] ?? '') ?></div>
      <?php if (!empty($photoActuelle)): ?>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" id="supprimerPhoto" name="supprimerPhoto" value="1">
          <label class="form-check-label" for="supprimerPhoto"><?= te('Retirer la photo actuelle') ?></label>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <div class="pied">
    <a href="mesRecoltes.php" class="lien-details"><?= te('Annuler') ?></a>
    <button type="submit" class="btn-hero border-0"><?= te($texteBouton) ?></button>
  </div>
</form>
