<?php
$e = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$needsAttention = static function (array $suivi): bool {
    $state = strtolower((string) ($suivi['etat'] ?? ''));
    return str_contains($state, 'stress') || str_contains($state, 'maladie') || str_contains($state, 'attention') || str_contains($state, 'surveill');
};
ob_start();
?>
<section class="page-hero dashboard-welcome">
    <div><p class="eyebrow">Votre jardin, en un regard</p><h1>Bonjour ! 🌱</h1><p>Voici l’état des plantations et les dernières observations.</p></div>
</section>
<section class="stats-grid dashboard-stats" aria-label="Résumé des plantations">
    <article class="stat-card"><span>Mes plantations</span><strong><?= (int) $plantationSummary['plantations'] ?></strong></article>
    <article class="stat-card"><span>Suivis enregistrés</span><strong><?= (int) $plantationSummary['suivis'] ?></strong></article>
    <article class="stat-card stat-warning"><span>Points à vérifier</span><strong><?= (int) $plantationSummary['alertes'] ?></strong></article>
    <article class="stat-card"><span>Variétés référencées</span><strong><?= (int) $plantationSummary['varietes'] ?></strong></article>
</section>
<section class="dashboard-grid">
    <article class="dashboard-panel dashboard-plantations"><div class="panel-heading"><h2>Mes plantations</h2><a href="index.php?action=plantations">Voir toutes →</a></div>
        <?php if (!$plantationSummary['plantationList']): ?><p class="dashboard-empty">Ajoutez votre première plantation pour commencer son suivi.</p><?php else: ?>
            <?php foreach ($plantationSummary['plantationList'] as $p): ?><div class="dashboard-plant-row"><span class="plant-icon" aria-hidden="true">🌱</span><div class="plant-row-main"><strong><?= $e($p['nom']) ?></strong><small><?= $e($p['varieteNom'] ?: 'Variété non précisée') ?> · <?= $e($p['adresse']) ?></small><small>Surface <?= $e($p['surface']) ?> m² · <?= $e($p['dateCreation']) ?></small></div><a class="small-action" href="index.php?action=suivis&amp;plantation=<?= (int) $p['idPlantation'] ?>">Suivi</a></div><?php endforeach; ?>
        <?php endif; ?>
        <a class="button button-primary dashboard-add" href="index.php?action=plantation_create">+ Ajouter une plantation</a>
    </article>
    <article class="dashboard-panel"><div class="panel-heading"><h2>Derniers suivis</h2><a href="index.php?action=suivis">Voir tout →</a></div>
        <?php if (!$plantationSummary['suiviRecent']): ?><p class="dashboard-empty">Les observations de vos plantations apparaîtront ici.</p><?php else: ?>
            <?php foreach ($plantationSummary['suiviRecent'] as $s): ?><a class="dashboard-follow-row" href="index.php?action=suivis&amp;plantation=<?= (int) $s['idPlantation'] ?>"><span class="follow-dot <?= $needsAttention($s) ? 'dot-warning' : '' ?>"></span><span><strong><?= $e($s['plantationNom']) ?> · <?= $e($s['etat']) ?></strong><small><?= $e($s['dateSuivi']) ?><?= $s['observation'] !== '' ? ' · ' . $e($s['observation']) : '' ?></small></span><span aria-hidden="true">›</span></a><?php endforeach; ?>
        <?php endif; ?>
    </article>
    <article class="dashboard-panel dashboard-alerts"><div class="panel-heading"><h2>À surveiller</h2><span class="alert-count"><?= (int) $plantationSummary['alertes'] ?></span></div>
        <?php if (!$plantationSummary['alertesRecent']): ?><p class="dashboard-empty">Aucun état signalé dans vos suivis.</p><?php else: ?>
            <?php foreach ($plantationSummary['alertesRecent'] as $s): ?><a class="alert-row" href="index.php?action=suivis&amp;plantation=<?= (int) $s['idPlantation'] ?>"><strong><?= $e($s['plantationNom']) ?></strong><span><?= $e($s['etat']) ?></span></a><?php endforeach; ?>
        <?php endif; ?>
        <a class="button button-light dashboard-add" href="index.php?action=suivis">Consulter mes suivis</a>
    </article>
</section>
<?php
$content = ob_get_clean();
$title = 'Tableau de bord | Taam Bledi';
require __DIR__ . '/layout.php';
