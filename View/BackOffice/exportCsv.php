<?php
/*
 * Back Office : export CSV (ouvrable avec Excel) des terrains ou des récoltes, avec les filtres de la liste
 * exportCsv.php?type=terrains&wilaya=Nabeul   ·   exportCsv.php?type=recoltes&wilaya=Sfax
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/RecolteController.php';

requireAdmin();

$type = ($_GET['type'] ?? '') === 'recoltes' ? 'recoltes' : 'terrains';
$tri  = $_GET['tri'] ?? '';
$ordre = ($_GET['ordre'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

if ($type === 'terrains') {
    $lignes = (new TerrainController())->listAllTerrains(
        ['q' => trim($_GET['q'] ?? ''), 'wilaya' => $_GET['wilaya'] ?? '', 'sol' => $_GET['sol'] ?? ''],
        isset(TerrainController::TRIS[$tri]) ? $tri : 'id', $ordre)['lignes'];
    $entetes = ['ID', 'Nom', 'Propriétaire', 'Wilaya', 'Superficie (ha)', 'Type de sol', 'Nb récoltes', 'Nb parcelles', 'Contrat foncier', 'Latitude', 'Longitude'];
    $valeurs = fn($t) => [$t['id'], $t['nom'], $t['proprietaire'], $t['localisation'], $t['superficie'], $t['typeSol'],
                          $t['nbRecoltes'], $t['nbParcelles'], $t['typeContrat'] ?? '', $t['latitude'], $t['longitude']];
} else {
    $lignes = (new RecolteController())->listAllRecoltes(
        ['q' => trim($_GET['q'] ?? ''), 'wilaya' => $_GET['wilaya'] ?? '',
         'du' => $_GET['du'] ?? '', 'au' => $_GET['au'] ?? ''],
        isset(RecolteController::TRIS[$tri]) ? $tri : 'dateRecolte', $ordre)['lignes'];
    $entetes = ['ID', 'Culture', 'Terrain', 'Wilaya', 'Agriculteur', 'Plantation', 'Récolte', 'Quantité', 'Unité', 'Quantité (kg)', 'Classe qualité', 'Code traçabilité'];
    $valeurs = fn($r) => [$r['id'], $r['culture'], $r['terrainNom'], $r['localisation'], $r['proprietaire'],
                          $r['datePlantation'], $r['dateRecolte'],
                          $r['quantite'], $r['unite'], $r['quantiteKg'], $r['classe'] ?? '', $r['codeTracabilite']];
}

// Protection contre l'injection de formules dans Excel (=, +, -, @ en début de cellule)
$propre = fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="taambledi-' . $type . '-' . date('Y-m-d') . '.csv"');
$sortie = fopen('php://output', 'w');
fwrite($sortie, "\xEF\xBB\xBF");                       // BOM : Excel lit bien les accents
fputcsv($sortie, $entetes, ';');                       // ; = séparateur d'Excel en français
foreach ($lignes as $ligne) {
    fputcsv($sortie, array_map($propre, $valeurs($ligne)), ';');
}
fclose($sortie);
exit;
