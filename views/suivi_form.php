<?php
$e = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="page-hero page-hero-compact"><div><p class="eyebrow">Suivi · <?= $e($plantation['nom']) ?></p><h1><?= $editing ? 'Modifier le suivi' : 'Ajouter une observation' ?></h1></div></section>
<section class="form-panel">
    <?php if ($errors): ?><div class="flash flash-error" role="alert"><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="index.php?action=<?= $editing ? 'suivi_update&amp;id=' . (int) $recordId : 'suivi_create&amp;plantation=' . (int) $plantation['idPlantation'] ?>" data-validated-form>
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="idPlantation" value="<?= (int) $plantation['idPlantation'] ?>">
        <div class="form-field"><label for="dateSuivi">Date du suivi <span>*</span></label><input id="dateSuivi" name="dateSuivi" type="date" required value="<?= $e(substr((string) ($old['dateSuivi'] ?? ''), 0, 10)) ?>"></div>
        <div class="form-field"><label for="etat">État observé <span>*</span></label><input id="etat" name="etat" required maxlength="100" value="<?= $e($old['etat'] ?? '') ?>"></div>
        <div class="form-field"><label for="arrosage">Arrosage</label><input id="arrosage" name="arrosage" maxlength="100" value="<?= $e($old['arrosage'] ?? '') ?>"></div>
        <div class="form-field"><label for="observation">Observation</label><textarea id="observation" name="observation" maxlength="2000" rows="4"><?= $e($old['observation'] ?? '') ?></textarea></div>
        <div class="form-field"><label for="photo">Photo (chemin ou URL)</label><input id="photo" name="photo" maxlength="500" value="<?= $e($old['photo'] ?? '') ?>"></div>
        <div class="form-field"><label for="diagnosticIA">Diagnostic IA</label><textarea id="diagnosticIA" name="diagnosticIA" maxlength="2000" rows="3"><?= $e($old['diagnosticIA'] ?? '') ?></textarea><small>Ce champ conserve le diagnostic prévu par le diagramme ; aucun diagnostic n’est généré ici.</small></div>
        <div class="form-actions"><button class="button button-primary" type="submit"><?= $editing ? 'Enregistrer' : 'Ajouter le suivi' ?></button><a class="button button-light" href="index.php?action=plantation_show&amp;id=<?= (int) $plantation['idPlantation'] ?>">Annuler</a></div>
    </form>
</section>
