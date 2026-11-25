<?php
/*
 * Controller : Météo et besoin en eau
 * Source : Open-Meteo (gratuit, sans clé) — https://open-meteo.com
 *  - prévisions sur 7 jours pour la position du terrain
 *  - alertes : gel, chaleur, forte pluie, vent fort
 *  - besoin en eau d'une culture (méthode FAO-56) : ETc = ET0 × kc
 */
require_once __DIR__ . '/HttpHelper.php';

class MeteoController
{
    // Codes météo WMO → libellé + icône Bootstrap Icons
    private const CODES = [
        0 => ['Ciel dégagé', 'bi-sun'], 1 => ['Plutôt dégagé', 'bi-sun'], 2 => ['Partiellement nuageux', 'bi-cloud-sun'],
        3 => ['Couvert', 'bi-clouds'], 45 => ['Brouillard', 'bi-cloud-fog'], 48 => ['Brouillard givrant', 'bi-cloud-fog'],
        51 => ['Bruine légère', 'bi-cloud-drizzle'], 53 => ['Bruine', 'bi-cloud-drizzle'], 55 => ['Bruine forte', 'bi-cloud-drizzle'],
        61 => ['Pluie faible', 'bi-cloud-rain'], 63 => ['Pluie', 'bi-cloud-rain'], 65 => ['Forte pluie', 'bi-cloud-rain-heavy'],
        71 => ['Neige faible', 'bi-cloud-snow'], 73 => ['Neige', 'bi-cloud-snow'], 75 => ['Forte neige', 'bi-cloud-snow'],
        80 => ['Averses', 'bi-cloud-rain'], 81 => ['Averses', 'bi-cloud-rain'], 82 => ['Violentes averses', 'bi-cloud-rain-heavy'],
        95 => ['Orage', 'bi-cloud-lightning-rain'], 96 => ['Orage et grêle', 'bi-cloud-hail'], 99 => ['Orage et grêle', 'bi-cloud-hail'],
    ];

    /**
     * Prévisions 7 jours. Retourne ['ok' => true, 'jours' => [...]] ou ['ok' => false, 'erreur' => '...']
     * Chaque jour : date, tmax, tmin, pluie (mm), et0 (mm), vent (km/h), code, libelle, icone
     */
    public function previsions(float $lat, float $lng): array
    {
        $cle = sprintf('meteo_%.2f_%.2f', $lat, $lng);
        if ($cache = cacheLire($cle, 3600)) {          // 1 heure
            return ['ok' => true, 'jours' => $cache];
        }

        $base = getenv('OPEN_METEO_URL') ?: 'https://api.open-meteo.com/v1/forecast';
        $url  = $base . '?' . http_build_query([
            'latitude'      => round($lat, 4),
            'longitude'     => round($lng, 4),
            'daily'         => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,et0_fao_evapotranspiration,wind_speed_10m_max',
            'timezone'      => 'Africa/Tunis',
            'forecast_days' => 7,
        ]);
        $reponse = httpJson($url);
        if (!$reponse['ok']) {
            return $reponse;
        }

        $d = $reponse['donnees']['daily'] ?? null;
        if (!is_array($d) || empty($d['time'])) {
            return ['ok' => false, 'erreur' => 'Données météo incomplètes.'];
        }
        $jours = [];
        foreach ($d['time'] as $i => $date) {
            $code = (int) ($d['weather_code'][$i] ?? 0);
            [$libelle, $icone] = self::CODES[$code] ?? ['Variable', 'bi-cloud'];
            $jours[] = [
                'date'    => $date,
                'tmax'    => (float) ($d['temperature_2m_max'][$i] ?? 0),
                'tmin'    => (float) ($d['temperature_2m_min'][$i] ?? 0),
                'pluie'   => (float) ($d['precipitation_sum'][$i] ?? 0),
                'et0'     => (float) ($d['et0_fao_evapotranspiration'][$i] ?? 0),
                'vent'    => (float) ($d['wind_speed_10m_max'][$i] ?? 0),
                'code'    => $code,
                'libelle' => $libelle,
                'icone'   => $icone,
            ];
        }
        cacheEcrire($cle, $jours);
        return ['ok' => true, 'jours' => $jours];
    }

    // Alertes agricoles à partir des prévisions
    // Chaque alerte : [couleur Bootstrap, icône, modèle de phrase, valeurs] (traduisible avec te())
    public static function alertes(array $jours): array
    {
        $alertes = [];
        $n = fn(float $x) => str_replace('.', ',', (string) round($x, 1));   // 1.5 → « 1,5 »
        foreach ($jours as $j) {
            $v = ['date' => date('d/m', strtotime($j['date']))];
            if ($j['tmin'] <= 2) {
                $alertes[] = ['danger', 'bi-snow', 'Risque de gel le {date} ({t} °C) : protégez les jeunes plants et les vergers.', $v + ['t' => $n($j['tmin'])]];
            }
            if ($j['tmax'] >= 40) {
                $alertes[] = ['danger', 'bi-thermometer-high', 'Canicule le {date} ({t} °C) : arrosez tôt le matin ou le soir.', $v + ['t' => $n($j['tmax'])]];
            } elseif ($j['tmax'] >= 35) {
                $alertes[] = ['warning', 'bi-thermometer-sun', 'Forte chaleur le {date} ({t} °C).', $v + ['t' => $n($j['tmax'])]];
            }
            if ($j['pluie'] >= 20) {
                $alertes[] = ['warning', 'bi-cloud-rain-heavy', 'Forte pluie le {date} ({mm} mm) : évitez les traitements et les engrais.', $v + ['mm' => $n($j['pluie'])]];
            }
            if ($j['vent'] >= 50) {
                $alertes[] = ['warning', 'bi-wind', 'Vent fort le {date} ({v} km/h) : ne pulvérisez pas.', $v + ['v' => round($j['vent'])]];
            }
        }
        return $alertes;
    }

    /**
     * Besoin en eau d'une culture sur un terrain pour les 7 jours (méthode FAO-56)
     *  - ETc (mm) = ET0 × kc       → eau consommée par la culture
     *  - pluie utile = 80 % de la pluie
     *  - besoin net (mm) = ETc − pluie utile (jamais négatif)
     *  - volume (m³) = mm × superficie (ha) × 10      (1 mm sur 1 ha = 10 m³)
     */
    public static function besoinEau(array $jours, float $kc, float $superficieHa): array
    {
        $lignes = [];
        $total  = 0.0;
        $reserve = 0.0;   // pluie en trop reportée au jour suivant (sol humide)
        foreach ($jours as $j) {
            $etc       = $j['et0'] * $kc;
            $pluieUtile = 0.8 * $j['pluie'] + $reserve;
            $net       = max(0, $etc - $pluieUtile);
            $reserve   = max(0, $pluieUtile - $etc);
            $m3        = $net * $superficieHa * 10;
            $total    += $m3;
            $lignes[]  = $j + ['etc' => round($etc, 1), 'netMm' => round($net, 1), 'm3' => round($m3, 1)];
        }
        return ['jours' => $lignes, 'totalM3' => round($total, 1), 'moyenneM3' => round($total / max(1, count($lignes)), 1)];
    }

    // Conseil d'irrigation pour aujourd'hui : retourne [modèle de phrase, valeurs] (traduisible avec te())
    public static function conseilIrrigation(array $besoin): array
    {
        $jours = $besoin['jours'];
        if (empty($jours)) {
            return ['', []];
        }
        $demain = $jours[1] ?? $jours[0];
        if ($demain['pluie'] >= 5) {
            return ["Pas besoin d'arroser aujourd'hui : {mm} mm de pluie sont prévus demain.", ['mm' => str_replace('.', ',', (string) round($demain['pluie'], 1))]];
        }
        $aujourdhui = $jours[0];
        if ($aujourdhui['m3'] <= 0) {
            return ["Pas besoin d'arroser aujourd'hui : la pluie couvre les besoins de la culture.", []];
        }
        $volume = number_format($aujourdhui['m3'], 1, ',', ' ');
        return $aujourdhui['tmax'] >= 30
            ? ["Arrosez environ {m3} m³ aujourd'hui, tôt le matin ou après 18 h (moins d'évaporation).", ['m3' => $volume]]
            : ["Arrosez environ {m3} m³ aujourd'hui.", ['m3' => $volume]];
    }

    // « Aujourd'hui », « Demain » ou le nom du jour (pour l'affichage traduit)
    public static function libelleJour(string $date): string
    {
        if ($date === date('Y-m-d')) {
            return "Aujourd'hui";
        }
        if ($date === date('Y-m-d', strtotime('+1 day'))) {
            return 'Demain';
        }
        return ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'][(int) date('w', strtotime($date))];
    }
}
