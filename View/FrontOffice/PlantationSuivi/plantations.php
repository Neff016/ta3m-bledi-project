<?php
if (!defined('APP_ENTRY')) {
    header('Location: ../index.php?action=plantations');
    exit;
}
$e = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="page-hero page-hero-compact">
    <div><p class="eyebrow">Mon espace Taam Bledi</p><h1>Mes plantations</h1><p>Retrouvez vos cultures et leur évolution.</p></div>
    <a class="button button-light" href="index.php?action=plantation_create">+ Ajouter une plantation</a>
</section>
<?php if ($flash): ?><div class="flash flash-success" role="status"><?= $e($flash) ?></div><?php endif; ?>
<section class="stats-grid" aria-label="Résumé">
    <article class="stat-card"><span>Mes plantations</span><strong><?= (int) $summary['plantations'] ?></strong></article>
    <article class="stat-card"><span>Suivis enregistrés</span><strong><?= (int) $summary['suivis'] ?></strong></article>
    <article class="stat-card"><span>Variétés référencées</span><strong><?= (int) $summary['varietes'] ?></strong></article>
</section>
<?php if (!$plantations): ?>
    <section class="empty-state"><h2>Votre première plantation vous attend</h2><p>Ajoutez une plantation pour commencer son suivi.</p><a class="button button-primary" href="index.php?action=plantation_create">Ajouter une plantation</a></section>
<?php else: ?>
    <section class="plantation-list" aria-label="Liste de mes plantations">
        <?php foreach ($plantations as $p): ?>
            <article class="plantation-card">
                <div class="card-content">
                    <div class="card-heading"><h2><?= $e($p['nom']) ?></h2><span class="status-badge"><?= $e($p['typeSupport']) ?></span></div>
                    <p class="variety"><?= $e($p['varieteNom'] ?: 'Variété non précisée') ?></p>
                    <ul class="plantation-details">
                        <li><span class="detail-icon icon-pin" aria-hidden="true"></span><?= $e($p['adresse']) ?></li>
                        <li><span class="detail-icon" aria-hidden="true">◫</span>Surface : <?= $e($p['surface']) ?> m²</li>
                        <li><span class="detail-icon icon-calendar" aria-hidden="true"></span>Créée le <?= $e($p['dateCreation']) ?></li>
                    </ul>
                </div>
                <div class="card-footer-links"><a class="card-footer-link" href="index.php?action=plantation_show&amp;id=<?= (int) $p['idPlantation'] ?>">Détails →</a><a class="card-footer-link" href="index.php?action=suivis&amp;plantation=<?= (int) $p['idPlantation'] ?>">Suivi →</a></div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
