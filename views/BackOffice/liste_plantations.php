<section class="page-hero page-hero-compact">
    <div>
        <p class="eyebrow">Espace administration</p>
        <h1>Plantations</h1>
    </div>
    <a class="button button-dark" href="<?= e(app_url('ajouter_plantation')) ?>">Ajouter une plantation</a>
</section>
<?php if (!$plantations): ?>
    <section class="empty-state"><p>Aucune plantation n’est enregistrée.</p></section>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nom</th><th>Variété</th><th>Région</th><th>Surface</th><th>Date</th><th>État</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($plantations as $plantation): ?>
                    <tr>
                        <th><?= e($plantation->nom) ?></th>
                        <td><?= e($plantation->variete) ?></td>
                        <td><?= e($plantation->region) ?></td>
                        <td><?= e(format_surface($plantation->surface)) ?> m²</td>
                        <td><?= e($plantation->date) ?></td>
                        <td><?= e($plantation->etat) ?></td>
                        <td><a href="<?= e(app_url('historique', ['plantation' => $plantation->id])) ?>">Historique</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
