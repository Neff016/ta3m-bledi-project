<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Agriculture intelligente</p>
        <h1>Historique</h1>
    </div>
</section>

<section class="form-panel">
    <form class="filter-form" method="get" action="<?= e(app_url('historique')) ?>">
        <div class="form-field">
            <label for="plantation">Choisir une plantation</label>
            <select id="plantation" name="plantation">
                <option value="">Toutes les plantations</option>
                <?php foreach ($plantations as $option): ?>
                    <option value="<?= (int) $option->id ?>" <?= $plantation && $plantation->id === $option->id ? 'selected' : '' ?>><?= e($option->nom) ?> — <?= e($option->region) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button button-primary" type="submit">Afficher</button>
        <a class="button button-light" href="<?= e(app_url('historique')) ?>">Effacer le filtre</a>
    </form>

    <?php if ($selectionInvalide): ?>
        <p class="field-error" role="alert">La plantation sélectionnée n’existe pas.</p>
    <?php elseif (!$plantation): ?>
        <div class="history-list">
            <?php foreach ($plantations as $option): ?>
                <a class="history-link" href="<?= e(app_url('historique', ['plantation' => $option->id])) ?>">
                    <span><?= e($option->nom) ?><small><?= e($option->region) ?></small></span><span aria-hidden="true">→</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <h2 class="section-title"><?= e($plantation->nom) ?></h2>
        <p class="muted"><?= e($plantation->region) ?> · <?= e(format_surface($plantation->surface)) ?> m²</p>
        <?php if (!$suivis): ?>
            <p>Aucun suivi n’a encore été ajouté à cette plantation.</p>
        <?php else: ?>
            <div class="history-list">
                <?php foreach ($suivis as $suivi): ?>
                    <article class="history-entry">
                        <h3><?= e($suivi->date) ?> · <?= e($suivi->etat) ?></h3>
                        <p><strong>Arrosage :</strong> <?= e($suivi->arrosage) ?></p>
                        <p><strong>Observation :</strong> <?= $suivi->observation !== '' ? nl2br(e($suivi->observation)) : 'Aucune observation.' ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a class="button button-primary" href="<?= e(app_url('ajouter_suivi', ['plantation' => $plantation->id])) ?>">Ajouter un suivi</a>
    <?php endif; ?>
</section>
