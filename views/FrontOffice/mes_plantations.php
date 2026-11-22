<section class="page-hero">
    <div>
        <p class="eyebrow">Agriculture intelligente</p>
        <h1>Mes plantations</h1>
    </div>
    <a class="button button-dark" href="<?= e(app_url('ajouter_plantation')) ?>">Ajouter une plantation</a>
</section>

<?php if (!$plantations): ?>
    <section class="empty-state">
        <h2>Aucune plantation pour le moment</h2>
        <p>Enregistrez votre première parcelle pour commencer.</p>
        <a class="button button-primary" href="<?= e(app_url('ajouter_plantation')) ?>">Ajouter une plantation</a>
    </section>
<?php else: ?>
    <section class="plantation-carousel" aria-label="Mes plantations">
        <button class="carousel-arrow carousel-previous" type="button" aria-label="Plantations précédentes">‹</button>
        <div class="plantation-track" data-carousel-track tabindex="0">
            <?php foreach ($plantations as $plantation): ?>
                <article class="plantation-card">
                    <div class="card-content">
                        <div class="card-heading">
                            <h2><?= e($plantation->nom) ?></h2>
                            <span class="status-badge"><?= e($plantation->etat) ?></span>
                        </div>
                        <p class="variety"><?= e($plantation->variete) ?></p>
                        <ul class="plantation-details">
                            <li><span class="detail-icon icon-pin" aria-hidden="true"></span><?= e($plantation->region) ?></li>
                            <li><span class="detail-icon" aria-hidden="true">▤</span><?= e(format_surface($plantation->surface)) ?> m²</li>
                            <li><span class="detail-icon icon-calendar" aria-hidden="true"></span>Plantée le <?= e($plantation->date) ?></li>
                        </ul>
                    </div>
                    <a class="card-footer-link" href="<?= e(app_url('historique', ['plantation' => $plantation->id])) ?>">Voir l’historique</a>
                </article>
            <?php endforeach; ?>
        </div>
        <button class="carousel-arrow carousel-next" type="button" aria-label="Plantations suivantes">›</button>
    </section>
<?php endif; ?>
