<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Agriculture intelligente</p>
        <h1>Ajouter une plantation</h1>
    </div>
</section>

<section class="form-panel">
    <p class="form-intro">Renseignez les informations de votre parcelle.</p>
    <?php if (!empty($erreurs['_form'])): ?><div class="field-error" role="alert"><?= e($erreurs['_form']) ?></div><?php endif; ?>

    <form method="post" action="<?= e(app_url('ajouter_plantation')) ?>" id="plantation-form">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">

        <div class="form-field">
            <label for="nom">Nom de la plantation <span>*</span></label>
            <input id="nom" name="nom" type="text" value="<?= e($valeurs['nom']) ?>" maxlength="100" required>
            <?php if (isset($erreurs['nom'])): ?><p class="field-error"><?= e($erreurs['nom']) ?></p><?php endif; ?>
        </div>

        <fieldset class="form-field">
            <legend>Variété(s) <span>*</span></legend>
            <div class="choice-grid">
                <?php foreach ($varietes as $index => $variete): ?>
                    <label class="choice" for="variete-<?= (int) $index ?>">
                        <input class="variety-choice" id="variete-<?= (int) $index ?>" type="checkbox" name="variete[]" value="<?= e($variete) ?>" <?= in_array($variete, $valeurs['variete'], true) ? 'checked' : '' ?>>
                        <span><?= e($variete === 'Autre' ? 'Autre variété' : $variete) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (isset($erreurs['variete'])): ?><p class="field-error"><?= e($erreurs['variete']) ?></p><?php endif; ?>
            <div class="other-variety">
                <label for="variete_autre">Précisez la variété si vous avez choisi « Autre »</label>
                <input id="variete_autre" name="variete_autre" type="text" value="<?= e($valeurs['variete_autre']) ?>" maxlength="80">
                <?php if (isset($erreurs['variete_autre'])): ?><p class="field-error"><?= e($erreurs['variete_autre']) ?></p><?php endif; ?>
            </div>
        </fieldset>

        <div class="form-field">
            <label for="region">Région <span>*</span></label>
            <select id="region" name="region" required>
                <option value="">Choisir une région</option>
                <?php foreach ($regions as $region): ?>
                    <option value="<?= e($region) ?>" <?= $valeurs['region'] === $region ? 'selected' : '' ?>><?= e($region) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($erreurs['region'])): ?><p class="field-error"><?= e($erreurs['region']) ?></p><?php endif; ?>
        </div>

        <div class="form-field">
            <label for="surface">Surface (m²) <span>*</span></label>
            <input id="surface" name="surface" type="number" value="<?= e($valeurs['surface']) ?>" min="1" max="1000000" step="0.1" required>
            <?php if (isset($erreurs['surface'])): ?><p class="field-error"><?= e($erreurs['surface']) ?></p><?php endif; ?>
        </div>

        <div class="form-field">
            <label for="date">Date de plantation <span>*</span></label>
            <input id="date" name="date" type="date" value="<?= e($valeurs['date']) ?>" max="<?= e(date('Y-m-d')) ?>" required>
            <?php if (isset($erreurs['date'])): ?><p class="field-error"><?= e($erreurs['date']) ?></p><?php endif; ?>
        </div>

        <div class="form-actions">
            <button class="button button-primary" type="submit">Enregistrer la plantation</button>
            <a class="button button-light" href="<?= e(app_url('mes_plantations')) ?>">Annuler</a>
        </div>
    </form>
</section>
