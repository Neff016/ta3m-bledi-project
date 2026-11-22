<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Espace administration</p>
        <h1>Statistiques</h1>
    </div>
</section>
<section class="stats-grid">
    <article class="stat-card"><span>Plantations</span><strong><?= count($plantations) ?></strong></article>
    <article class="stat-card"><span>Suivis enregistrés</span><strong><?= (int) $totalSuivis ?></strong></article>
    <article class="stat-card"><span>Région la plus active</span><strong class="stat-region"><?= e($regionActive) ?></strong></article>
</section>
<section class="form-panel">
    <h2 class="section-title">Plantations par région</h2>
    <?php if (!$regions): ?>
        <p>Aucune donnée à afficher.</p>
    <?php else: ?>
        <div class="table-wrap table-wrap-inner">
            <table>
                <thead><tr><th>Région</th><th>Nombre de plantations</th></tr></thead>
                <tbody><?php foreach ($regions as $region => $count): ?><tr><th><?= e($region) ?></th><td><?= (int) $count ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
