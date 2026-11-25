<?php
/*
 * Back Office : ajouter ou modifier une culture du catalogue (formCulture.php ou formCulture.php?id=3)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/CultureController.php';

requireAdmin();

$cultureC = new CultureController();
$id       = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$culture  = $id ? $cultureC->getCulture($id) : null;
if ($id && !$culture) {
    setFlash("Cette culture n'existe pas.", 'danger');
    header('Location: listeCultures.php');
    exit;
}

$valeurs = [
    'nom'        => $culture['nom'] ?? '',
    'categorie'  => $culture['categorie'] ?? '',
    'sols'       => $culture ? explode(',', $culture['sols']) : [],
    'saisons'    => $culture ? explode(',', $culture['saisons']) : [],
    'regions'    => $culture && $culture['regions'] !== '*' ? explode(',', $culture['regions']) : [],
    'besoinEau'  => $culture['besoinEau'] ?? '',
    'kc'         => $culture['kc'] ?? '',
    'rendement'  => $culture['rendement'] ?? '',
    'dureeJours' => $culture['dureeJours'] ?? '',
    'prixKg'     => $culture['prixKg'] ?? '',
    'conseil'    => $culture['conseil'] ?? '',
];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valeurs = [
        'nom'        => trim($_POST['nom'] ?? ''),
        'categorie'  => $_POST['categorie'] ?? '',
        'sols'       => (array) ($_POST['sols'] ?? []),
        'saisons'    => (array) ($_POST['saisons'] ?? []),
        'regions'    => (array) ($_POST['regions'] ?? []),
        'besoinEau'  => $_POST['besoinEau'] ?? '',
        'kc'         => trim($_POST['kc'] ?? ''),
        'rendement'  => trim($_POST['rendement'] ?? ''),
        'dureeJours' => trim($_POST['dureeJours'] ?? ''),
        'prixKg'     => trim($_POST['prixKg'] ?? ''),
        'conseil'    => trim($_POST['conseil'] ?? ''),
    ];
    $erreurs = $cultureC->validateCulture($valeurs, $id);
    if (empty($erreurs)) {
        if ($id) {
            $cultureC->updateCulture($id, $valeurs);
            setFlash('Culture « ' . $culture['nom'] . ' » modifiée.');
        } else {
            $cultureC->addCulture($valeurs);
            setFlash('Culture « ' . $valeurs['nom'] . ' » ajoutée au catalogue.');
        }
        header('Location: listeCultures.php');
        exit;
    }
}

$cases = function (string $nom, array $options, array $coches) use (&$erreurs): string {
    $html = '';
    foreach ($options as $o) {
        $idCase = $nom . '-' . md5($o);
        $html .= '<div class="custom-control custom-checkbox custom-control-inline">'
               . '<input type="checkbox" class="custom-control-input" id="' . $idCase . '" name="' . $nom . '[]" value="' . e($o) . '"'
               . (in_array($o, $coches, true) ? ' checked' : '') . '>'
               . '<label class="custom-control-label" for="' . $idCase . '">' . e($o) . '</label></div>';
    }
    return $html;
};

$titrePage = $id ? 'Modifier une culture' : 'Ajouter une culture';
$boActif   = 'listeCultures.php';
require __DIR__ . '/partials/boHaut.php';
?>
                    <h1 class="h3 mb-4 text-gray-800"><?= e($titrePage) ?><?= $culture ? ' : ' . e($culture['nom']) : '' ?></h1>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" id="formCulture" novalidate>
                                <?= champCsrf() ?>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="nom">Nom</label>
                                        <input type="text" class="form-control <?= isset($erreurs['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom"
                                               value="<?= e($valeurs['nom']) ?>" <?= $id ? 'readonly' : '' ?>>
                                        <div class="text-danger small" id="erreur-nom"><?= e($erreurs['nom'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="categorie">Catégorie</label>
                                        <select class="form-control <?= isset($erreurs['categorie']) ? 'is-invalid' : '' ?>" id="categorie" name="categorie">
                                            <option value="">-- Choisir --</option>
                                            <?php foreach (CultureController::CATEGORIES as $c): ?>
                                                <option <?= $valeurs['categorie'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="text-danger small" id="erreur-categorie"><?= e($erreurs['categorie'] ?? '') ?></div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="besoinEau">Besoin en eau</label>
                                        <select class="form-control <?= isset($erreurs['besoinEau']) ? 'is-invalid' : '' ?>" id="besoinEau" name="besoinEau">
                                            <option value="">-- Choisir --</option>
                                            <?php foreach (CultureController::EAU as $code => $libelle): ?>
                                                <option value="<?= $code ?>" <?= $valeurs['besoinEau'] === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="text-danger small" id="erreur-besoinEau"><?= e($erreurs['besoinEau'] ?? '') ?></div>
                                    </div>
                                </div>

                                <div class="form-group" id="groupe-sols">
                                    <label class="d-block">Sols adaptés</label>
                                    <?= $cases('sols', TerrainController::TYPES_SOL, $valeurs['sols']) ?>
                                    <div class="text-danger small" id="erreur-sols"><?= e($erreurs['sols'] ?? '') ?></div>
                                </div>
                                <div class="form-group" id="groupe-saisons">
                                    <label class="d-block">Saisons de plantation</label>
                                    <?= $cases('saisons', CultureController::SAISONS, $valeurs['saisons']) ?>
                                    <div class="text-danger small" id="erreur-saisons"><?= e($erreurs['saisons'] ?? '') ?></div>
                                </div>
                                <div class="form-group">
                                    <label class="d-block">Régions typiques <small class="text-muted">(rien de coché = toute la Tunisie)</small></label>
                                    <?= $cases('regions', TerrainController::WILAYAS, $valeurs['regions']) ?>
                                    <div class="text-danger small"><?= e($erreurs['regions'] ?? '') ?></div>
                                </div>

                                <div class="form-row">
                                    <?php foreach (['kc' => ['Coefficient kc', '0,2 à 1,5'], 'rendement' => ['Rendement (t/ha)', 'Ex : 3'],
                                                    'dureeJours' => ['Durée du cycle (jours)', 'Ex : 120'], 'prixKg' => ['Prix du marché (DT/kg)', 'Ex : 1,200']] as $champ => [$libelle, $exemple]): ?>
                                        <div class="form-group col-md-3">
                                            <label for="<?= $champ ?>"><?= e($libelle) ?></label>
                                            <input type="text" class="form-control <?= isset($erreurs[$champ]) ? 'is-invalid' : '' ?>" id="<?= $champ ?>" name="<?= $champ ?>"
                                                   value="<?= e($valeurs[$champ]) ?>" placeholder="<?= e($exemple) ?>">
                                            <div class="text-danger small" id="erreur-<?= $champ ?>"><?= e($erreurs[$champ] ?? '') ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-group">
                                    <label for="conseil">Conseil pour l'agriculteur</label>
                                    <input type="text" class="form-control <?= isset($erreurs['conseil']) ? 'is-invalid' : '' ?>" id="conseil" name="conseil" value="<?= e($valeurs['conseil']) ?>">
                                    <div class="text-danger small" id="erreur-conseil"><?= e($erreurs['conseil'] ?? '') ?></div>
                                </div>
                                <a href="listeCultures.php" class="btn btn-light">Annuler</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                            </form>
                        </div>
                    </div>
<?php
$jsSupp = ['js/validation-culture.js'];
require __DIR__ . '/partials/boBas.php';
