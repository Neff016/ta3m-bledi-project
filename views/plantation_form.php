<?php
$e = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="page-hero page-hero-compact"><div><p class="eyebrow">Mes plantations</p><h1><?= $editing ? 'Modifier une plantation' : 'Ajouter une plantation' ?></h1></div></section>
<section class="form-panel">
    <?php if ($errors): ?><div class="flash flash-error" role="alert"><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form id="plantation-form" method="post" action="index.php?action=<?= $editing ? 'plantation_update&amp;id=' . (int) $recordId : 'plantation_create' ?>" data-validated-form>
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
        <div class="form-field"><label for="nom">Nom <span>*</span></label><input id="nom" name="nom" required maxlength="100" value="<?= $e($old['nom'] ?? '') ?>"></div>
        <div class="form-field"><label for="adresse">Adresse <span>*</span></label><input id="adresse" name="adresse" required maxlength="100" value="<?= $e($old['adresse'] ?? '') ?>"></div>
        <div class="choice-grid">
            <div class="form-field"><label for="surface">Surface (m²) <span>*</span></label><input id="surface" name="surface" type="number" min="0.01" max="10000000" step="any" required value="<?= $e($old['surface'] ?? '') ?>"></div>
            <div class="form-field"><label for="typeSupport">Type de support <span>*</span></label><select id="typeSupport" name="typeSupport" required><option value="">Choisir…</option><?php foreach (['toit' => 'Toit', 'balcon' => 'Balcon', 'jardin' => 'Jardin'] as $value => $label): ?><option value="<?= $e($value) ?>" <?= ($old['typeSupport'] ?? '') === $value ? 'selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-field"><label for="dateCreation">Date de création <span>*</span></label><input id="dateCreation" name="dateCreation" type="date" required value="<?= $e(substr((string) ($old['dateCreation'] ?? ''), 0, 10)) ?>"></div>
        <div class="form-field"><label for="idVariete">Variété</label><select id="idVariete" name="idVariete"><option value="">Non précisée</option><?php foreach ($varieties as $variety): ?><option value="<?= (int) $variety['idVariete'] ?>" <?= (string) ($old['idVariete'] ?? '') === (string) $variety['idVariete'] ? 'selected' : '' ?>><?= $e($variety['nom']) ?></option><?php endforeach; ?></select><?php if (!$varieties): ?><small>Aucune variété n’est encore référencée. Vous pourrez l’associer ultérieurement.</small><?php endif; ?></div>
        <div class="choice-grid"><div class="form-field"><label for="latitude">Latitude (facultatif)</label><input id="latitude" name="latitude" type="number" min="-90" max="90" step="any" value="<?= $e($old['latitude'] ?? '') ?>"></div><div class="form-field"><label for="longitude">Longitude (facultatif)</label><input id="longitude" name="longitude" type="number" min="-180" max="180" step="any" value="<?= $e($old['longitude'] ?? '') ?>"></div></div>
        <div class="form-actions"><button class="button button-primary" type="submit"><?= $editing ? 'Enregistrer les changements' : 'Créer la plantation' ?></button><a class="button button-light" href="index.php?action=plantations">Annuler</a></div>
    </form>
</section>
