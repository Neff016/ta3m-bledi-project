<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Agriculture intelligente</p>
        <h1>Recommandation</h1>
    </div>
</section>

<section class="form-panel">
    <p class="form-intro">Choisissez une région pour consulter les variétés recommandées.</p>
    <form method="get" action="<?= e(app_url('recommandation')) ?>">
        <div class="form-field">
            <label for="region">Région</label>
            <select id="region" name="region" required>
                <option value="">Choisir une région</option>
                <?php foreach ($regions as $option): ?>
                    <option value="<?= e($option) ?>" <?= $region === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button button-primary" type="submit">Voir la recommandation</button>
    </form>

    <?php if ($resultat): ?>
        <div class="recommendation-result" role="status">
            <h2>Suggestions pour <?= e($region) ?></h2>
            <ul><?php foreach ($resultat as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul>
        </div>
    <?php elseif ($message !== ''): ?>
        <p class="form-intro" role="status"><?= e($message) ?></p>
    <?php endif; ?>
</section>
