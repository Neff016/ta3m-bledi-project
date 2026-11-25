<?php
/*
 * Controller : Santé du terrain vue par satellite (indice NDVI)
 * Source : Agromonitoring (images Sentinel-2 et Landsat 8) — clé gratuite dans Config/api.php (AGRO_CLE)
 *
 * NDVI = indice de végétation entre -1 et 1 :
 *   < 0,2 sol nu ou plante en souffrance · 0,2-0,5 végétation moyenne · > 0,5 végétation dense et saine
 */
require_once __DIR__ . '/HttpHelper.php';
require_once __DIR__ . '/TerrainController.php';

class SatelliteController
{
    public static function estConfigure(): bool
    {
        return cleApi('AGRO_CLE') !== '';
    }

    private static function url(string $chemin, array $params = []): string
    {
        $base = getenv('AGRO_URL') ?: 'https://api.agromonitoring.com/agro/1.0';
        return $base . $chemin . '?' . http_build_query($params + ['appid' => cleApi('AGRO_CLE')]);
    }

    /**
     * Retourne ['ok' => true, 'historique' => [...], 'image' => [...]|null] ou ['ok' => false, 'erreur' => '...']
     * Le polygone est envoyé une seule fois à Agromonitoring (son identifiant est gardé dans terrain.agroPolygonId).
     */
    public function ndvi(array $terrain): array
    {
        if (!self::estConfigure()) {
            return ['ok' => false, 'erreur' => "Le suivi satellite n'est pas configuré : ajoutez votre clé Agromonitoring dans Config/api.php."];
        }
        $points = TerrainController::lirePolygone($terrain['polygone'] ?? null);
        if ($points === null) {
            return ['ok' => false, 'erreur' => 'Dessinez d’abord la parcelle sur la carte (Modifier le terrain) pour activer le suivi satellite.'];
        }

        // 1) Enregistrer la parcelle chez Agromonitoring (une seule fois)
        $idPolygone = $terrain['agroPolygonId'] ?? null;
        if (!$idPolygone) {
            $anneau = array_map(fn($p) => [$p[1], $p[0]], $points);   // GeoJSON : [longitude, latitude]
            $anneau[] = $anneau[0];                                    // polygone fermé
            $reponse = httpJson(self::url('/polygons', ['duplicated' => 'true']), 'POST', [
                'name'     => 'TaamBledi-' . $terrain['id'],
                'geo_json' => ['type' => 'Feature', 'properties' => new stdClass(),
                               'geometry' => ['type' => 'Polygon', 'coordinates' => [$anneau]]],
            ]);
            if (!$reponse['ok'] || empty($reponse['donnees']['id'])) {
                return ['ok' => false, 'erreur' => 'Impossible d’enregistrer la parcelle : ' . ($reponse['erreur'] ?? 'réponse invalide')
                                                  . ' (la parcelle doit faire entre 1 et 3000 ha).'];
            }
            $idPolygone = (string) $reponse['donnees']['id'];
            (new TerrainController())->updateAgroPolygon((int) $terrain['id'], $idPolygone);
        }

        // 2) Historique du NDVI moyen sur 6 mois (mis en cache 6 heures)
        $cle = 'ndvi_' . $idPolygone;
        if ($cache = cacheLire($cle, 6 * 3600)) {
            return ['ok' => true] + $cache;
        }
        $debut = strtotime('-6 months');
        $fin   = time();
        $histo = httpJson(self::url('/ndvi/history', ['polyid' => $idPolygone, 'start' => $debut, 'end' => $fin]));
        if (!$histo['ok']) {
            return $histo;
        }
        $historique = [];
        foreach ($histo['donnees'] as $mesure) {
            if (isset($mesure['dt'], $mesure['data']['mean']) && ($mesure['cl'] ?? 0) < 60) {   // on ignore les images trop nuageuses
                $historique[] = ['date' => date('Y-m-d', (int) $mesure['dt']), 'ndvi' => round((float) $mesure['data']['mean'], 3),
                                 'min' => round((float) ($mesure['data']['min'] ?? 0), 3), 'max' => round((float) ($mesure['data']['max'] ?? 0), 3)];
            }
        }
        usort($historique, fn($a, $b) => strcmp($a['date'], $b['date']));

        // 3) Dernière image NDVI colorée (moins de 50 % de nuages)
        $image = null;
        $images = httpJson(self::url('/image/search', ['polyid' => $idPolygone, 'start' => strtotime('-60 days'), 'end' => $fin, 'clouds_max' => 50]));
        if ($images['ok'] && !empty($images['donnees'])) {
            $derniere = end($images['donnees']);
            if (!empty($derniere['image']['ndvi'])) {
                $image = ['url' => (string) $derniere['image']['ndvi'], 'date' => date('Y-m-d', (int) ($derniere['dt'] ?? time())),
                          'source' => (string) ($derniere['type'] ?? 'Sentinel-2')];
            }
        }

        $resultat = ['historique' => $historique, 'image' => $image];
        cacheEcrire($cle, $resultat);
        return ['ok' => true] + $resultat;
    }

    // Interprétation simple d'une valeur NDVI
    public static function interpreter(float $ndvi): array
    {
        return match (true) {
            $ndvi < 0.2  => ['danger', 'Sol nu ou végétation en souffrance'],
            $ndvi < 0.4  => ['warning', 'Végétation faible à moyenne'],
            $ndvi < 0.6  => ['info', 'Végétation en bonne santé'],
            default      => ['success', 'Végétation dense et très saine'],
        };
    }
}
