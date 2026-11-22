<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Agriculture intelligente</p>
        <h1>Ajouter un suivi</h1>
    </div>
</section>

<section class="form-panel">
    <p class="form-intro">Enregistrez l’état et l’arrosage de votre plantation.</p>
    <?php if (!empty($erreurs['_form'])): ?><div class="field-error" role="alert"><?= e($erreurs['_form']) ?></div><?php endif; ?>

    <?php if (!$plantations): ?>
        <p>Aucune plantation n’est encore enregistrée.</p>
        <a class="button button-primary" href="<?= e(app_url('ajouter_plantation')) ?>">Ajouter une plantation</a>
    <?php else: ?>
        <form method="post" action="<?= e(app_url('ajouter_suivi')) ?>">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <div class="form-field">
                <label for="plantation">Plantation <span>*</span></label>
                <select id="plantation" name="plantation" required>
                    <option value="">Choisir une plantation</option>
                    <?php foreach ($plantations as $plantation): ?>
                        <option value="<?= (int) $plantation->id ?>" <?= (string) $plantation->id === $valeurs['plantation'] ? 'selected' : '' ?>><?= e($plantation->nom) ?> — <?= e($plantation->region) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($erreurs['plantation'])): ?><p class="field-error"><?= e($erreurs['plantation']) ?></p><?php endif; ?>
            </div>
            <div class="form-field">
                <label for="date">Date du suivi <span>*</span></label>
                <input id="date" name="date" type="date" value="<?= e($valeurs['date']) ?>" max="<?= e(date('Y-m-d')) ?>" required>
                <?php if (isset($erreurs['date'])): ?><p class="field-error"><?= e($erreurs['date']) ?></p><?php endif; ?>
            </div>
            <div class="form-field">
                <label for="etat">État <span>*</span></label>
                <select id="etat" name="etat" required>
                    <option value="">Choisir un état</option>
                    <?php foreach ($etats as $etat): ?>
                        <option value="<?= e($etat) ?>" <?= $valeurs['etat'] === $etat ? 'selected' : '' ?>><?= e($etat) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($erreurs['etat'])): ?><p class="field-error"><?= e($erreurs['etat']) ?></p><?php endif; ?>
            </div>
            <div class="form-field">
                <label for="arrosage">Arrosage <span>*</span></label>
                <select id="arrosage" name="arrosage" required>
                    <option value="">Choisir un niveau d’arrosage</option>
                    <?php foreach ($arrosages as $arrosage): ?>
                        <option value="<?= e($arrosage) ?>" <?= $valeurs['arrosage'] === $arrosage ? 'selected' : '' ?>><?= e($arrosage) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($erreurs['arrosage'])): ?><p class="field-error"><?= e($erreurs['arrosage']) ?></p><?php endif; ?>
            </div>
            <div class="form-field">
                <label for="observation">Observation</label>
                <textarea id="observation" name="observation" rows="4" maxlength="1000" placeholder="Décrire l’état de la plantation..."><?= e($valeurs['observation']) ?></textarea>
                <small>1 000 caractères maximum.</small>
                <?php if (isset($erreurs['observation'])): ?><p class="field-error"><?= e($erreurs['observation']) ?></p><?php endif; ?>
            </div>
            <div class="form-actions">
                <button class="button button-primary" type="submit">Enregistrer le suivi</button>
                <a class="button button-light" href="<?= e(app_url('mes_plantations')) ?>">Annuler</a>
            </div>
        </form>
    <?php endif; ?>
</section>
