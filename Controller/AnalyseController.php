<?php
/*
 * Controller : Analyse (tableau de bord de l'agriculteur)
 *  - production par mois, par culture, comparaison avec l'année précédente
 *  - rendement (t/ha) par terrain
 *  - prévision de la prochaine récolte (régression linéaire)
 *  - comparaison anonyme avec les agriculteurs de la même région
 *  - rentabilité par terrain : prix du marché × qualité × prime bio − dépenses − loyer ou part du propriétaire
 * Les calculs par mois sont faits en PHP (requêtes simples, compatibles MySQL/MariaDB).
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/CultureController.php';
require_once __DIR__ . '/ContratFoncierController.php';
require_once __DIR__ . '/CertificationBioController.php';
require_once __DIR__ . '/ControleQualiteController.php';

class AnalyseController
{
    public const MOIS = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

    private CultureController $cultureC;

    public function __construct()
    {
        $this->cultureC = new CultureController();
    }

    // Récoltes terminées d'un utilisateur (avec superficie et wilaya du terrain)
    private function recoltesTerminees(int $idUtilisateur): array
    {
        $sql = "SELECT r.*, t.nom AS terrainNom, t.superficie, t.localisation
                FROM recolte r JOIN terrain t ON t.id = r.idTerrain
                WHERE t.idUtilisateur = :u AND r.quantiteKg IS NOT NULL
                ORDER BY r.dateRecolte";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['u' => $idUtilisateur]);
        return $req->fetchAll();
    }

    /* =========================================================
     *  TABLEAU DE BORD
     * ========================================================= */

    public function tableauDeBord(int $idUtilisateur): array
    {
        $recoltes = $this->recoltesTerminees($idUtilisateur);
        $annee    = (int) date('Y');

        // Production des 12 derniers mois
        $parMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $cle = date('Y-m', strtotime("first day of -$i month"));
            $parMois[$cle] = 0.0;
        }
        // Comparaison année en cours / année précédente (janvier → décembre)
        $anneeN  = array_fill(1, 12, 0.0);
        $anneeN1 = array_fill(1, 12, 0.0);
        $parCulture = [];
        $kgAnnee = 0.0;

        foreach ($recoltes as $r) {
            $kg  = (float) $r['quantiteKg'];
            $cle = substr($r['dateRecolte'], 0, 7);
            if (isset($parMois[$cle])) {
                $parMois[$cle] += $kg;
            }
            $a = (int) substr($r['dateRecolte'], 0, 4);
            $m = (int) substr($r['dateRecolte'], 5, 2);
            if ($a === $annee) {
                $anneeN[$m] += $kg;
                $kgAnnee += $kg;
            } elseif ($a === $annee - 1) {
                $anneeN1[$m] += $kg;
            }
            $nom = ($this->cultureC->trouver($r['culture'])['nom'] ?? null) ?: ucfirst(trim($r['culture']));
            $parCulture[$nom] = ($parCulture[$nom] ?? 0) + $kg;
        }
        arsort($parCulture);

        $libellesMois = array_map(fn($c) => self::MOIS[(int) substr($c, 5, 2) - 1] . ' ' . substr($c, 2, 2), array_keys($parMois));

        return [
            'kgTotal'       => array_sum(array_column($recoltes, 'quantiteKg')),
            'kgAnnee'       => $kgAnnee,
            'nbRecoltes'    => count($recoltes),
            'parMois'       => ['libelles' => $libellesMois, 'valeurs' => array_values($parMois)],
            'anneeN'        => array_values($anneeN),
            'anneeN1'       => array_values($anneeN1),
            'parCulture'    => $parCulture,
            'rendements'    => $this->rendementParTerrain($idUtilisateur),
            'previsions'    => $this->previsions($idUtilisateur, $recoltes),
            'comparaisons'  => $this->comparaisonRegionale($idUtilisateur, $recoltes),
            'rentabilite'   => $this->rentabilite($idUtilisateur),
        ];
    }

    // Rendement des 12 derniers mois par terrain (t/ha)
    public function rendementParTerrain(int $idUtilisateur): array
    {
        $sql = "SELECT t.id, t.nom, t.superficie,
                       COALESCE(SUM(CASE WHEN r.dateRecolte >= :debut THEN r.quantiteKg END), 0) AS kg
                FROM terrain t LEFT JOIN recolte r ON r.idTerrain = t.id
                WHERE t.idUtilisateur = :u
                GROUP BY t.id, t.nom, t.superficie
                ORDER BY t.nom";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['u' => $idUtilisateur, 'debut' => date('Y-m-d', strtotime('-12 months'))]);
        $resultat = [];
        foreach ($req->fetchAll() as $t) {
            $resultat[] = ['nom' => $t['nom'], 'kg' => (float) $t['kg'],
                           'tha' => round((float) $t['kg'] / 1000 / max(0.01, (float) $t['superficie']), 2)];
        }
        return $resultat;
    }

    /* =========================================================
     *  PRÉVISION DE RÉCOLTE (régression linéaire)
     * ========================================================= */

    /**
     * Pour chaque culture déjà récoltée : quantité et date probables de la prochaine récolte.
     *  - quantité : droite de régression y = a·x + b sur les récoltes passées (x = 1, 2, 3…)
     *  - date : dernière date + intervalle moyen entre deux récoltes
     */
    public function previsions(int $idUtilisateur, ?array $recoltes = null): array
    {
        $recoltes ??= $this->recoltesTerminees($idUtilisateur);
        // Une prévision par culture ET par terrain (un champ ne produit pas comme un autre)
        $groupes = [];
        foreach ($recoltes as $r) {
            $nom = ($this->cultureC->trouver($r['culture'])['nom'] ?? null) ?: ucfirst(trim($r['culture']));
            $groupes[$nom . '|' . $r['idTerrain']][] = $r;
        }

        $previsions = [];
        foreach ($groupes as $cle => $liste) {
            $nom = explode('|', $cle)[0];
            $y = array_map(fn($r) => (float) $r['quantiteKg'], $liste);
            $n = count($y);
            if ($n >= 2) {
                [$a, $b] = self::regression(range(1, $n), $y);
                // Prévision gardée dans des limites réalistes (entre la moitié du minimum et 1,5 × le maximum)
                $kg = min(max($a * ($n + 1) + $b, 0.5 * min($y)), 1.5 * max($y));
                $tendance = $a > 0.02 * (array_sum($y) / $n) ? 'hausse' : ($a < -0.02 * (array_sum($y) / $n) ? 'baisse' : 'stable');
            } else {
                $kg = $y[0];
                $tendance = 'stable';
            }

            $derniere = end($liste)['dateRecolte'];
            $intervalle = 365;
            if ($n >= 2) {
                $ecarts = [];
                for ($i = 1; $i < $n; $i++) {
                    $ecarts[] = (strtotime($liste[$i]['dateRecolte']) - strtotime($liste[$i - 1]['dateRecolte'])) / 86400;
                }
                $intervalle = max(60, (int) round(array_sum($ecarts) / count($ecarts)));
            }
            $date = date('Y-m-d', strtotime("$derniere +$intervalle days"));
            while ($date < date('Y-m-d')) {                     // jamais dans le passé
                $date = date('Y-m-d', strtotime("$date +$intervalle days"));
            }
            $source = 'estimée';

            $previsions[] = [
                'culture'   => $nom,
                'terrain'   => end($liste)['terrainNom'],
                'kg'        => round($kg),
                'date'      => $date,
                'source'    => $source,
                'tendance'  => $tendance,
                'fiabilite' => $n >= 4 ? 'bonne' : ($n >= 2 ? 'moyenne' : 'faible (1 seule récolte)'),
                'n'         => $n,
            ];
        }
        usort($previsions, fn($p, $q) => strcmp($p['date'], $q['date']));
        return $previsions;
    }

    // Méthode des moindres carrés : retourne [pente a, ordonnée b]
    public static function regression(array $x, array $y): array
    {
        $n  = count($x);
        $mx = array_sum($x) / $n;
        $my = array_sum($y) / $n;
        $num = 0.0;
        $den = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $num += ($x[$i] - $mx) * ($y[$i] - $my);
            $den += ($x[$i] - $mx) ** 2;
        }
        $a = $den == 0 ? 0.0 : $num / $den;
        return [$a, $my - $a * $mx];
    }

    /* =========================================================
     *  COMPARAISON RÉGIONALE ANONYME
     * ========================================================= */

    /**
     * Pour chaque culture × wilaya de l'utilisateur : son rendement moyen (t/ha)
     * comparé à la moyenne des AUTRES agriculteurs de la même wilaya (aucun nom affiché).
     * S'il n'y a pas d'autres agriculteurs : comparaison avec la moyenne nationale du catalogue.
     */
    public function comparaisonRegionale(int $idUtilisateur, ?array $recoltes = null): array
    {
        $recoltes ??= $this->recoltesTerminees($idUtilisateur);
        $moi = [];
        foreach ($recoltes as $r) {
            $c   = $this->cultureC->trouver($r['culture']);
            $nom = $c['nom'] ?? ucfirst(trim($r['culture']));
            $moi[$nom . '|' . $r['localisation']][] = (float) $r['quantiteKg'] / 1000 / max(0.01, (float) $r['superficie']);
        }

        $sql = "SELECT r.culture, r.quantiteKg, t.superficie, t.localisation, t.idUtilisateur
                FROM recolte r JOIN terrain t ON t.id = r.idTerrain JOIN utilisateur u ON u.id = t.idUtilisateur
                WHERE r.quantiteKg IS NOT NULL AND u.statut = 'ACTIF' AND t.idUtilisateur <> :u";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['u' => $idUtilisateur]);
        $autres = [];
        foreach ($req->fetchAll() as $r) {
            $nom = ($this->cultureC->trouver($r['culture'])['nom'] ?? null) ?: ucfirst(trim($r['culture']));
            $cle = $nom . '|' . $r['localisation'];
            $autres[$cle]['valeurs'][] = (float) $r['quantiteKg'] / 1000 / max(0.01, (float) $r['superficie']);
            $autres[$cle]['agriculteurs'][$r['idUtilisateur']] = true;
        }

        $resultat = [];
        foreach ($moi as $cle => $valeurs) {
            [$nom, $wilaya] = explode('|', $cle);
            $monRendement = array_sum($valeurs) / count($valeurs);
            if (!empty($autres[$cle])) {
                $reference = array_sum($autres[$cle]['valeurs']) / count($autres[$cle]['valeurs']);
                $base = ['moyenne de {n} agriculteur(s) de {w}', ['n' => count($autres[$cle]['agriculteurs']), 'w' => $wilaya]];
            } else {
                $c = $this->cultureC->trouver($nom);
                if (!$c) {
                    continue;
                }
                $reference = (float) $c['rendement'];
                $base = ['moyenne nationale', []];
            }
            $ecart = $reference > 0 ? ($monRendement - $reference) / $reference * 100 : 0;
            $resultat[] = ['culture' => $nom, 'wilaya' => $wilaya, 'moi' => round($monRendement, 2),
                           'reference' => round($reference, 2), 'ecart' => (int) round($ecart), 'base' => $base];
        }
        return $resultat;
    }

    /* =========================================================
     *  RENTABILITÉ
     * ========================================================= */

    // Par terrain : chiffre d'affaires estimé (kg × prix du marché) − dépenses
    public function rentabilite(int $idUtilisateur, ?int $idTerrain = null): array
    {
        $sql = "SELECT t.id, t.nom, t.superficie FROM terrain t WHERE t.idUtilisateur = :u";
        $params = ['u' => $idUtilisateur];
        if ($idTerrain !== null) {
            $sql .= " AND t.id = :t";
            $params['t'] = $idTerrain;
        }
        $req = config::getConnexion()->prepare($sql . " ORDER BY t.nom");
        $req->execute($params);
        $terrains = $req->fetchAll();

        $resultat = [];
        $parCulture = [];
        $contratC = new ContratFoncierController();
        $bioC     = new CertificationBioController();
        foreach ($terrains as $t) {
            // Qualité (classe, déchets) jointe à chaque récolte
            $r = config::getConnexion()->prepare(
                "SELECT r.culture, r.quantiteKg, r.dateRecolte, q.classe, q.tauxDechets
                 FROM recolte r LEFT JOIN controle_qualite q ON q.idRecolte = r.id
                 WHERE r.idTerrain = :t AND r.quantiteKg IS NOT NULL");
            $r->execute(['t' => $t['id']]);
            $certification = $bioC->getParTerrain((int) $t['id']);
            $revenu = 0.0;
            foreach ($r->fetchAll() as $rec) {
                $c = $this->cultureC->trouver($rec['culture']);
                if ($c) {
                    // prix du marché × classe et part vendable × prime bio si le terrain était certifié
                    $coef = ControleQualiteController::coefficient($rec['classe'] ? $rec : null)
                          * (CertificationBioController::statutRecolte($certification, $rec['dateRecolte']) === 'CERTIFIE'
                              ? CertificationBioController::PRIME_BIO : 1.0);
                    $montant = (float) $rec['quantiteKg'] * (float) $c['prixKg'] * $coef;
                    $revenu += $montant;
                    $parCulture[$c['nom']]['revenu'] = ($parCulture[$c['nom']]['revenu'] ?? 0) + $montant;
                    $parCulture[$c['nom']]['ha'] = ($parCulture[$c['nom']]['ha'] ?? 0) + (float) $t['superficie'];
                }
            }
            $d = config::getConnexion()->prepare("SELECT COALESCE(SUM(montant), 0) FROM depense WHERE idTerrain = :t");
            $d->execute(['t' => $t['id']]);
            $depenses = (float) $d->fetchColumn();
            // Loyer ou part du propriétaire (contrat foncier)
            $foncier = ContratFoncierController::chargeFonciere($contratC->getParTerrain((int) $t['id']), $revenu);
            $resultat[] = ['id' => $t['id'], 'nom' => $t['nom'], 'revenu' => round($revenu, 3),
                           'depenses' => round($depenses, 3), 'foncier' => $foncier,
                           'benefice' => round($revenu - $depenses - $foncier, 3)];
        }

        // Culture la plus rentable (revenu par hectare)
        $meilleure = null;
        foreach ($parCulture as $nom => $v) {
            $parHa = $v['revenu'] / max(0.01, $v['ha']);
            if ($meilleure === null || $parHa > $meilleure['parHa']) {
                $meilleure = ['culture' => $nom, 'parHa' => round($parHa, 3)];
            }
        }
        return ['terrains' => $resultat, 'meilleure' => $meilleure,
                'totalRevenu' => array_sum(array_column($resultat, 'revenu')),
                'totalDepenses' => array_sum(array_column($resultat, 'depenses')),
                'totalFoncier' => array_sum(array_column($resultat, 'foncier'))];
    }
}
