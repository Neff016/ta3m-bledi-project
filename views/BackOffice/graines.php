<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Espace administration</p>
        <h1>Graines et variétés</h1>
    </div>
</section>
<section class="form-panel">
    <p class="form-intro">Variétés disponibles dans le formulaire d’ajout de plantation.</p>
    <ul class="seed-list">
        <?php foreach ($varietes as $variete): ?><li><?= e($variete === 'Autre' ? 'Autre variété (à préciser)' : $variete) ?></li><?php endforeach; ?>
    </ul>
</section>
