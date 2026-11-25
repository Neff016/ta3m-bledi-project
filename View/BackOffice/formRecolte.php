<?php
/*
 * Back Office : ajouter ou modifier une récolte (formRecolte.php ou formRecolte.php?id=12)
 * L'admin choisit le terrain (donc l'agriculteur) et, si besoin, la parcelle.
 * Mêmes règles que le Front Office : RecolteController::validateRecolte() avec le propriétaire du terrain.
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';
require_once __DIR__ . '/../../Controller/ParcelleController.php';
require_once __DIR__ . '/../../Controller/CultureController.php';
require_once __DIR__ . '/../../Controller/BadgeAutoController.php';

requireAdmin();

$recolteC = new RecolteController();
$id       = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$recolte  = $id ? $recolteC->getRecolteAdmin($id) : null;
if ($id && !$recolte) {
    setFlash("Cette récolte n'existe pas.", 'danger');
    header('Location: listeRecoltes.php');
    exit;
}

$terrains  = $recolteC->terrainsPourAdmin();
$proprio   = array_column($terrains, 'idUtilisateur', 'id');                 // idTerrain → idUtilisateur
$parcelles = [];
foreach ($terrains as $t) {
    foreach ((new ParcelleController())->listParTerrain((int) $t['id']) as $p) {
        $parcelles[] = $p;
    }
}

$valeurs = [
    'idTerrain'      => $recolte['idTerrain'] ?? '',
    'idParcelle'     => $recolte['idParcelle'] ?? '',
    'culture'        => $recolte['culture'] ?? '',
    'datePlantation' => $recolte['datePlantation'] ?? '',
    'dateRecolte'    => $recolte['dateRecolte'] ?? '',
    'quantite'       => isset($recolte['quantite']) ? RecolteController::nombreCourt((float) $recolte['quantite']) : '',
    'unite'          => $recolte['unite'] ?? 'kg',
];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valeurs = [
        'idTerrain'      => $_POST['idTerrain'] ?? '',
        'idParcelle'     => trim($_POST['idParcelle'] ?? ''),
        'culture'        => trim($_POST['culture'] ?? ''),
        'datePlantation' => $_POST['datePlantation'] ?? '',
        'dateRecolte'    => $_POST['dateRecolte'] ?? '',
        'quantite'       => trim($_POST['quantite'] ?? ''),
        'unite'          => $_POST['unite'] ?? '',
    ];
    // Le terrain doit être celui d'un agriculteur actif : on valide avec son propriétaire
    $idProprio = $proprio[(int) $valeurs['idTerrain']] ?? null;
    $erreurs = $idProprio
        ? $recolteC->validateRecolte($valeurs, (int) $idProprio)
        : ['idTerrain' => 'Veuillez choisir un terrain.'] + $recolteC->validateRecolte($valeurs + ['idTerrain' => ''], 0);

    if (empty($erreurs)) {
        $objet = new Recolte(
            $valeurs['culture'], $valeurs['datePlantation'], $valeurs['dateRecolte'],
            (float) str_replace(',', '.', $valeurs['quantite']), $valeurs['unite'], (int) $valeurs['idTerrain'],
            $id, $valeurs['idParcelle'] !== '' ? (int) $valeurs['idParcelle'] : null
        );
        if ($id) {
            $recolteC->updateRecolte($objet);
            setFlash('Récolte modifiée.');
        } else {
            $recolteC->addRecolte($objet);
            (new BadgeAutoController())->verifier((int) $idProprio);
            setFlash('Récolte ajoutée.');
        }
        header('Location: listeRecoltes.php');
        exit;
    }
}

$nomsCultures = (new CultureController())->nomsPourAutocompletion();
$titrePage = $id ? 'Modifier une récolte' : 'Ajouter une récolte';
$boActif   = 'listeRecoltes.php';
$jsSupp    = ['js/validation-recolte-bo.js'];
require __DIR__ . '/partials/boHaut.php';
?>
                    <h1 class="h3 mb-4 text-gray-800"><?= e($titrePage) ?><?= $recolte ? ' : ' . e($recolte['culture']) . ' (' . e($recolte['terrainNom']) . ')' : '' ?></h1>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" id="formRecolteBo" novalidate>
                                <?= champCsrf() ?>
                                <div class="form-row">
                                    <div class="form-group col-md-5">
                                        <label for="idTerrain">Terrain (agriculteur)</label>
                                        <select class="form-control <?= isset($erreurs['idTerrain']) ? 'is-invalid' : '' ?>" id="idTerrain" name="idTerrain">
                                            <option value="">-- Choisir un terrain --</option>
                                            <?php foreach ($terrains as $t): ?>
                                                <option value="<?= e($t['id']) ?>" <?= (string) $valeurs['idTerrain'] === (string) $t['id'] ? 'selected' : '' ?>>
                                                    <?= e($t['nom']) ?> — <?= e($t['agriculteur']) ?> (<?= e($t['localisation']) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="text-danger small" id="erreur-idTerrain"><?= e($erreurs['idTerrain'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="idParcelle">Parcelle (facultatif)</label>
                                        <select class="form-control <?= isset($erreurs['idParcelle']) ? 'is-invalid' : '' ?>" id="idParcelle" name="idParcelle">
                                            <option value="">-- Tout le terrain --</option>
                                            <?php foreach ($parcelles as $p): ?>
                                                <option value="<?= e($p['id']) ?>" data-terrain="<?= e($p['idTerrain']) ?>" <?= (string) $valeurs['idParcelle'] === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['nom']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="text-danger small" id="erreur-idParcelle"><?= e($erreurs['idParcelle'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="culture">Culture</label>
                                        <input type="text" class="form-control <?= isset($erreurs['culture']) ? 'is-invalid' : '' ?>" id="culture" name="culture" value="<?= e($valeurs['culture']) ?>" list="listeCultures" autocomplete="off">
                                        <datalist id="listeCultures"><?php foreach ($nomsCultures as $n): ?><option value="<?= e($n) ?>"></option><?php endforeach; ?></datalist>
                                        <div class="text-danger small" id="erreur-culture"><?= e($erreurs['culture'] ?? '') ?></div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <label for="datePlantation">Date de plantation</label>
                                        <input type="date" class="form-control <?= isset($erreurs['datePlantation']) ? 'is-invalid' : '' ?>" id="datePlantation" name="datePlantation" value="<?= e($valeurs['datePlantation']) ?>">
                                        <div class="text-danger small" id="erreur-datePlantation"><?= e($erreurs['datePlantation'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="dateRecolte">Date de récolte</label>
                                        <input type="date" class="form-control <?= isset($erreurs['dateRecolte']) ? 'is-invalid' : '' ?>" id="dateRecolte" name="dateRecolte" value="<?= e($valeurs['dateRecolte']) ?>">
                                        <div class="text-danger small" id="erreur-dateRecolte"><?= e($erreurs['dateRecolte'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="quantite">Quantité</label>
                                        <input type="text" class="form-control <?= isset($erreurs['quantite']) ? 'is-invalid' : '' ?>" id="quantite" name="quantite" value="<?= e($valeurs['quantite']) ?>" placeholder="Ex : 250">
                                        <div class="text-danger small" id="erreur-quantite"><?= e($erreurs['quantite'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="unite">Unité</label>
                                        <select class="form-control <?= isset($erreurs['unite']) ? 'is-invalid' : '' ?>" id="unite" name="unite">
                                            <?php foreach (RecolteController::UNITES as $u): ?>
                                                <option <?= $valeurs['unite'] === $u ? 'selected' : '' ?>><?= e($u) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="text-danger small" id="erreur-unite"><?= e($erreurs['unite'] ?? '') ?></div>
                                    </div>
                                </div>
                                <a href="listeRecoltes.php" class="btn btn-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $id ? 'Enregistrer' : 'Ajouter la récolte' ?></button>
                            </form>
                        </div>
                    </div>
<?php require __DIR__ . '/partials/boBas.php'; ?>
