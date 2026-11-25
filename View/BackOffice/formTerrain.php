<?php
/*
 * Back Office : ajouter ou modifier un terrain (formTerrain.php ou formTerrain.php?id=3)
 * L'admin choisit l'agriculteur propriétaire. Mêmes règles que le Front Office (TerrainController::validateTerrain).
 * Si l'agriculteur a dessiné la parcelle sur la carte, sa superficie est calculée : l'admin ne peut pas la changer.
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/BadgeAutoController.php';

requireAdmin();

$terrainC = new TerrainController();
$id       = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$terrain  = $id ? $terrainC->getTerrainAvecProprietaire($id) : null;
if ($id && !$terrain) {
    setFlash("Ce terrain n'existe pas.", 'danger');
    header('Location: listeTerrains.php');
    exit;
}
$polygone = $terrain ? TerrainController::lirePolygone($terrain['polygone']) : null;

$valeurs = [
    'idUtilisateur' => $terrain['idUtilisateur'] ?? '',
    'nom'           => $terrain['nom'] ?? '',
    'localisation'  => $terrain['localisation'] ?? '',
    'superficie'    => $terrain['superficie'] ?? '',
    'typeSol'       => $terrain['typeSol'] ?? '',
    'latitude'      => $terrain['latitude'] ?? '',
    'longitude'     => $terrain['longitude'] ?? '',
];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valeurs = [
        'idUtilisateur' => $_POST['idUtilisateur'] ?? '',
        'nom'           => trim($_POST['nom'] ?? ''),
        'localisation'  => $_POST['localisation'] ?? '',
        'superficie'    => $polygone ? (string) $terrain['superficie'] : trim($_POST['superficie'] ?? ''),
        'typeSol'       => $_POST['typeSol'] ?? '',
        'latitude'      => trim($_POST['latitude'] ?? ''),
        'longitude'     => trim($_POST['longitude'] ?? ''),
    ];
    $erreurs = $terrainC->validateTerrain($valeurs) + $terrainC->validateProprietaire($valeurs['idUtilisateur']);

    if (empty($erreurs)) {
        $objet = new Terrain(
            $valeurs['nom'],
            $valeurs['localisation'],
            (float) str_replace(',', '.', $valeurs['superficie']),
            $valeurs['typeSol'],
            (int) $valeurs['idUtilisateur'],
            $id,
            $valeurs['latitude'] !== '' ? (float) $valeurs['latitude'] : null,
            $valeurs['longitude'] !== '' ? (float) $valeurs['longitude'] : null,
            $terrain['polygone'] ?? null
        );
        if ($id) {
            $terrainC->updateTerrainAdmin($objet);
            setFlash('Terrain « ' . $valeurs['nom'] . ' » modifié.');
        } else {
            $terrainC->addTerrain($objet);
            (new BadgeAutoController())->verifier((int) $valeurs['idUtilisateur']);   // ex. badge « Premier terrain »
            setFlash('Terrain « ' . $valeurs['nom'] . ' » ajouté.');
        }
        header('Location: listeTerrains.php');
        exit;
    }
}

$agriculteurs = $terrainC->listAgriculteurs();
$titrePage = $id ? 'Modifier un terrain' : 'Ajouter un terrain';
$boActif   = 'listeTerrains.php';
$jsSupp    = ['js/validation-terrain-bo.js'];
require __DIR__ . '/partials/boHaut.php';
?>
                    <h1 class="h3 mb-4 text-gray-800"><?= e($titrePage) ?><?= $terrain ? ' : ' . e($terrain['nom']) : '' ?></h1>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" id="formTerrainBo" novalidate>
                                <?= champCsrf() ?>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="idUtilisateur">Agriculteur propriétaire</label>
                                        <select class="form-control <?= isset($erreurs['idUtilisateur']) ? 'is-invalid' : '' ?>" id="idUtilisateur" name="idUtilisateur">
                                            <option value="">-- Choisir un agriculteur --</option>
                                            <?php foreach ($agriculteurs as $a): ?>
                                                <option value="<?= e($a['id']) ?>" <?= (string) $valeurs['idUtilisateur'] === (string) $a['id'] ? 'selected' : '' ?>>
                                                    <?= e($a['prenom'] . ' ' . $a['nom']) ?> (<?= e($a['email']) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="text-danger small" id="erreur-idUtilisateur"><?= e($erreurs['idUtilisateur'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="nom">Nom du terrain</label>
                                        <input type="text" class="form-control <?= isset($erreurs['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" value="<?= e($valeurs['nom']) ?>" placeholder="Ex : Terrain Zaghouan">
                                        <div class="text-danger small" id="erreur-nom"><?= e($erreurs['nom'] ?? '') ?></div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="localisation">Wilaya</label>
                                        <select class="form-control <?= isset($erreurs['localisation']) ? 'is-invalid' : '' ?>" id="localisation" name="localisation">
                                            <option value="">-- Choisir --</option>
                                            <?php foreach (TerrainController::WILAYAS as $w): ?>
                                                <option <?= $valeurs['localisation'] === $w ? 'selected' : '' ?>><?= e($w) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="text-danger small" id="erreur-localisation"><?= e($erreurs['localisation'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="superficie">Superficie (ha)</label>
                                        <input type="text" class="form-control <?= isset($erreurs['superficie']) ? 'is-invalid' : '' ?>" id="superficie" name="superficie"
                                               value="<?= e($valeurs['superficie']) ?>" placeholder="Ex : 2.5" <?= $polygone ? 'readonly' : '' ?>>
                                        <?php if ($polygone): ?><small class="text-muted">Calculée à partir de la parcelle dessinée par l'agriculteur.</small><?php endif; ?>
                                        <div class="text-danger small" id="erreur-superficie"><?= e($erreurs['superficie'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="typeSol">Type de sol</label>
                                        <select class="form-control <?= isset($erreurs['typeSol']) ? 'is-invalid' : '' ?>" id="typeSol" name="typeSol">
                                            <option value="">-- Choisir --</option>
                                            <?php foreach (TerrainController::TYPES_SOL as $s): ?>
                                                <option <?= $valeurs['typeSol'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="text-danger small" id="erreur-typeSol"><?= e($erreurs['typeSol'] ?? '') ?></div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="latitude">Latitude (facultatif)</label>
                                        <input type="text" class="form-control <?= isset($erreurs['position']) ? 'is-invalid' : '' ?>" id="latitude" name="latitude" value="<?= e($valeurs['latitude']) ?>" placeholder="36.4021">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="longitude">Longitude (facultatif)</label>
                                        <input type="text" class="form-control <?= isset($erreurs['position']) ? 'is-invalid' : '' ?>" id="longitude" name="longitude" value="<?= e($valeurs['longitude']) ?>" placeholder="10.1423">
                                    </div>
                                    <div class="form-group col-md-4 d-flex align-items-end">
                                        <div class="text-danger small" id="erreur-position"><?= e($erreurs['position'] ?? '') ?></div>
                                    </div>
                                </div>
                                <a href="listeTerrains.php" class="btn btn-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $id ? 'Enregistrer' : 'Ajouter le terrain' ?></button>
                            </form>
                        </div>
                    </div>
<?php require __DIR__ . '/partials/boBas.php'; ?>
