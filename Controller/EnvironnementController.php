<?php
/*
 * Controller : Bilan environnemental d'une récolte (calculé, pas de table)
 *
 * 1) Empreinte eau (litres par kg récolté)
 *    eau consommée par la culture = Σ ET0(jour) × kc     (méthode FAO-56, comme la page Consommation d'eau)
 *    ET0 et pluie : moyennes mensuelles de la Tunisie (pluie × 0,35 dans les régions sèches)
 *    litres / kg = mm × hectares × 10 000 / kg
 *    Note A → E : rendement de référence du catalogue ÷ votre rendement
 *    (meilleur rendement = moins d'eau par kg)
 *
 * 2) Carbone (kg CO₂e par kg récolté)
 *    dépenses du terrain entre la plantation et la récolte × facteur d'émission par dinar
 *    (ordre de grandeur : engrais azotés, carburant, pompage électrique, produits de traitement)
 *
 * 3) Note globale = moyenne des deux notes, arrondie vers la moins bonne
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/CultureController.php';

class EnvironnementController
{
    public const NOTES = ['A', 'B', 'C', 'D', 'E'];
    public const COULEURS = ['A' => '#038141', 'B' => '#85bb2f', 'C' => '#fecb02', 'D' => '#ee8100', 'E' => '#e63e11'];

    // ET0 moyenne en Tunisie (mm par jour), de janvier à décembre
    public const ET0_MOIS = [1.5, 2.2, 3.2, 4.3, 5.5, 6.8, 7.5, 7.0, 5.3, 3.6, 2.2, 1.5];
    // Pluie moyenne (mm par mois), de janvier à décembre
    public const PLUIE_MOIS = [50, 40, 40, 30, 18, 7, 2, 7, 30, 45, 50, 55];
    public const FACTEUR_PLUIE_ARIDE = 0.35;

    // kg CO₂e par dinar dépensé, selon la catégorie de la dépense (ordre de grandeur)
    public const CO2_PAR_DT = [
        'Engrais' => 1.5, 'Eau' => 1.2, 'Matériel' => 1.0, 'Traitement' => 0.4,
        'Semences' => 0.3, 'Autre' => 0.2, "Main-d'oeuvre" => 0.0,
    ];
    // Limites des notes carbone (kg CO₂e par kg)
    public const SEUILS_CO2 = [0.15, 0.30, 0.60, 1.00];
    // Limites des notes eau (rendement de référence ÷ votre rendement)
    public const SEUILS_EAU = [0.80, 1.00, 1.25, 1.60];

    private CultureController $cultureC;

    public function __construct()
    {
        $this->cultureC = new CultureController();
    }

    // Toutes les récoltes d'un utilisateur avec leur bilan
    public function bilansUtilisateur(int $idUtilisateur, ?int $idTerrain = null): array
    {
        $sql = "SELECT r.*, t.nom AS terrainNom, t.superficie, t.localisation, p.nom AS parcelleNom, p.surface AS parcelleSurface
                FROM recolte r JOIN terrain t ON t.id = r.idTerrain LEFT JOIN parcelle p ON p.id = r.idParcelle
                WHERE t.idUtilisateur = :u";
        $params = ['u' => $idUtilisateur];
        if ($idTerrain !== null) {
            $sql .= " AND t.id = :t";
            $params['t'] = $idTerrain;
        }
        $req = config::getConnexion()->prepare($sql . " ORDER BY r.dateRecolte DESC, r.id DESC");
        $req->execute($params);
        $resultat = [];
        foreach ($req->fetchAll() as $r) {
            $resultat[] = $r + ['bilan' => $this->bilan($r)];
        }
        return $resultat;
    }

    /**
     * Bilan d'une récolte. $r doit contenir : culture, datePlantation, dateRecolte, quantiteKg, idTerrain,
     * superficie et localisation du terrain, parcelleSurface (ou null).
     */
    public function bilan(array $r): array
    {
        $kg      = max(0.01, (float) $r['quantiteKg']);
        $terrainHa = max(0.01, (float) $r['superficie']);
        $hectares = !empty($r['parcelleSurface']) ? (float) $r['parcelleSurface'] : $terrainHa;
        $culture = $this->cultureC->trouver($r['culture']);

        // ---- Eau ----
        $eau = null;
        if ($culture) {
            $aride = in_array($r['localisation'], CultureController::REGIONS_ARIDES, true);
            [$etcMm, $irrigationMm] = self::eauCycle($r['datePlantation'], $r['dateRecolte'], (float) $culture['kc'], $aride);
            $rendement = $kg / 1000 / $hectares;                         // t/ha
            $ratio = (float) $culture['rendement'] / max(0.001, $rendement);
            $eau = [
                'litresParKg'  => round($etcMm * $hectares * 10000 / $kg),
                'partIrrigation' => $etcMm > 0 ? round($irrigationMm / $etcMm * 100) : 0,
                'rendement'    => round($rendement, 2),
                'rendementRef' => (float) $culture['rendement'],
                'ratio'        => round($ratio, 2),
                'note'         => self::note($ratio, self::SEUILS_EAU),
            ];
        }

        // ---- Carbone : dépenses du terrain pendant la culture, au prorata de la surface ----
        $part = $hectares / $terrainHa;
        $req = config::getConnexion()->prepare(
            "SELECT categorie, SUM(montant) AS total FROM depense
             WHERE idTerrain = :t AND dateDepense BETWEEN :d AND :f GROUP BY categorie");
        $req->execute(['t' => $r['idTerrain'], 'd' => $r['datePlantation'], 'f' => $r['dateRecolte']]);
        $co2 = 0.0;
        $detail = [];
        foreach ($req->fetchAll() as $d) {
            $kgCo2 = (float) $d['total'] * $part * (self::CO2_PAR_DT[$d['categorie']] ?? 0.2);
            $co2 += $kgCo2;
            $detail[$d['categorie']] = round($kgCo2, 1);
        }
        arsort($detail);
        $carbone = empty($detail) ? null : [
            'total'      => round($co2, 1),
            'parKg'      => round($co2 / $kg, 3),
            'detail'     => $detail,
            'note'       => self::note($co2 / $kg, self::SEUILS_CO2),
        ];

        // ---- Note globale ----
        $notes = array_filter([$eau['note'] ?? null, $carbone['note'] ?? null]);
        $globale = null;
        if ($notes) {
            $moyenne = array_sum(array_map(fn($n) => array_search($n, self::NOTES, true), $notes)) / count($notes);
            $globale = self::NOTES[(int) ceil($moyenne)];
        }
        return ['eau' => $eau, 'carbone' => $carbone, 'note' => $globale, 'complet' => $eau !== null && $carbone !== null];
    }

    // Eau consommée (ETc) et irrigation nécessaire, en mm, entre deux dates
    public static function eauCycle(string $debut, string $fin, float $kc, bool $aride): array
    {
        $etc = 0.0;
        $irrigation = 0.0;
        $jour = new DateTime($debut);
        $dernier = new DateTime($fin);
        while ($jour < $dernier) {
            $m = (int) $jour->format('n') - 1;
            $besoin = self::ET0_MOIS[$m] * $kc;
            $pluieUtile = 0.8 * self::PLUIE_MOIS[$m] / (int) $jour->format('t') * ($aride ? self::FACTEUR_PLUIE_ARIDE : 1);
            $etc += $besoin;
            $irrigation += max(0, $besoin - $pluieUtile);
            $jour->modify('+1 day');
        }
        return [$etc, $irrigation];
    }

    public static function note(float $valeur, array $seuils): string
    {
        foreach ($seuils as $i => $limite) {
            if ($valeur <= $limite) {
                return self::NOTES[$i];
            }
        }
        return 'E';
    }
}
