<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Espace administration</p>
        <h1>Tableau de bord</h1>
    </div>
</section>
<section class="stats-grid">
    <article class="stat-card"><span>Plantations</span><strong><?= (int) $total ?></strong></article>
    <article class="stat-card"><span>Régions représentées</span><strong><?= (int) $totalRegions ?></strong></article>
    <article class="stat-card"><span>Suivis enregistrés</span><strong><?= (int) $totalSuivis ?></strong></article>
</section>
<section class="form-panel">
    <h2 class="section-title">Administration</h2>
    <div class="form-actions">
        <a class="button button-primary" href="<?= e(app_url('liste_plantations')) ?>">Liste des plantations</a>
        <a class="button button-light" href="<?= e(app_url('graines')) ?>">Graines</a>
        <a class="button button-light" href="<?= e(app_url('statistiques')) ?>">Statistiques</a>
    </div>
</section>
