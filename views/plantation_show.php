<?php
$e = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="page-hero page-hero-compact"><div><p class="eyebrow">Détail de ma plantation</p><h1><?= $e($plantation['nom']) ?></h1><p><?= $e($plantation['varieteNom'] ?: 'Variété non précisée') ?></p></div><a class="button button-light" href="index.php?action=plantation_update&amp;id=<?= (int) $plantation['idPlantation'] ?>">Modifier</a></section>
<?php if ($flash): ?><div class="flash flash-success" role="status"><?= $e($flash) ?></div><?php endif; ?>
<section class="form-panel plantation-detail-panel">
    <div class="detail-grid">
        <div><span>Adresse</span><strong><?= $e($plantation['adresse']) ?></strong></div><div><span>Surface</span><strong><?= $e($plantation['surface']) ?> m²</strong></div>
        <div><span>Type de support</span><strong><?= $e($plantation['typeSupport']) ?></strong></div><div><span>Date de création</span><strong><?= $e($plantation['dateCreation']) ?></strong></div>
        <div><span>Latitude</span><strong><?= $e($plantation['latitude'] ?? '—') ?></strong></div><div><span>Longitude</span><strong><?= $e($plantation['longitude'] ?? '—') ?></strong></div>
    </div>
    <div class="form-actions"><a class="button button-primary" href="index.php?action=suivi_create&amp;plantation=<?= (int) $plantation['idPlantation'] ?>">+ Ajouter un suivi</a><form method="post" action="index.php?action=plantation_delete" data-confirm="Supprimer cette plantation et tous ses suivis ?"><input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int) $plantation['idPlantation'] ?>"><button class="button button-danger" type="submit">Supprimer</button></form></div>
</section>
<section class="followup-section"><div class="section-heading"><h2 class="section-title">Suivi de la plantation</h2><span><?= count($suivis) ?> entrée(s) · <a href="index.php?action=suivis&amp;plantation=<?= (int) $plantation['idPlantation'] ?>">Ouvrir le suivi de cette plantation</a></span></div>
    <?php if (!$suivis): ?><div class="empty-state"><p>Aucun suivi enregistré pour le moment.</p></div><?php else: ?>
        <div class="history-list">
        <?php foreach ($suivis as $s): ?>
            <article class="history-entry"><div class="section-heading"><h3><?= $e($s['dateSuivi']) ?> · <?= $e($s['etat']) ?></h3><div><a href="index.php?action=suivi_update&amp;id=<?= (int) $s['idSuivi'] ?>">Modifier</a> <form class="inline-form" method="post" action="index.php?action=suivi_delete" data-confirm="Supprimer ce suivi ?"><input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int) $s['idSuivi'] ?>"><button class="text-button" type="submit">Supprimer</button></form></div></div>
                <?php if ($s['arrosage'] !== ''): ?><p><strong>Arrosage :</strong> <?= $e($s['arrosage']) ?></p><?php endif; ?><?php if ($s['observation'] !== ''): ?><p><strong>Observation :</strong> <?= nl2br($e($s['observation'])) ?></p><?php endif; ?><?php if ($s['photo'] !== ''): ?><p><strong>Photo :</strong> <?= $e($s['photo']) ?></p><?php endif; ?><?php if ($s['diagnosticIA'] !== ''): ?><p><strong>Diagnostic IA :</strong> <?= nl2br($e($s['diagnosticIA'])) ?></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
