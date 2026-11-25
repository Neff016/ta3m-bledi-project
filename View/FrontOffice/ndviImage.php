<?php
/*
 * Image NDVI d'un terrain, servie par le serveur
 * (l'adresse de l'image chez Agromonitoring contient la clé secrète : elle ne doit jamais aller dans le navigateur)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/TerrainController.php';
require_once __DIR__ . '/../../Controller/SatelliteController.php';

$idUtilisateur = requireLogin();
$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$terrain = $id ? (new TerrainController())->getTerrainOfUser($id, $idUtilisateur) : false;
$donnees = $terrain ? (new SatelliteController())->ndvi($terrain) : null;
$url     = $donnees['image']['url'] ?? null;

// On accepte seulement une image venant d'Agromonitoring
if (!$url || (!preg_match('#^https?://[a-z0-9.-]*agromonitoring\.com/#i', $url) && !getenv('AGRO_URL'))) {
    http_response_code(404);
    exit;
}
$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
foreach (['C:\\xampp\\apache\\bin\\curl-ca-bundle.crt', 'C:\\xampp\\php\\extras\\ssl\\cacert.pem'] as $ca) {
    if (is_file($ca)) {
        curl_setopt($ch, CURLOPT_CAINFO, $ca);
        break;
    }
}
$image = curl_exec($ch);
$type  = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);
if ($image === false || !str_starts_with($type, 'image/')) {
    http_response_code(502);
    exit;
}
header('Content-Type: ' . $type);
header('Cache-Control: private, max-age=21600');
echo $image;
