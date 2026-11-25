<?php
/*
 * Controller : Culture
 * - Catalogue des cultures tunisiennes (sols, saisons, régions, eau, rendement, prix du marché)
 * - Moteur de recommandation : score sur 100 avec les raisons
 * - Liste des noms pour l'autocomplétion (catalogue + variétés du Musée)
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/TerrainController.php';
require_once __DIR__ . '/RecolteController.php';

class CultureController
{
    public const CATEGORIES = ['Céréales', 'Oliviers', 'Fruits', 'Légumes', 'Plantes'];
    public const SAISONS    = ['Automne', 'Hiver', 'Printemps', 'Été'];
    public const EAU        = ['FAIBLE' => 'Faible', 'MOYEN' => 'Moyen', 'ELEVE' => 'Élevé'];

    // Régions sèches (centre et sud) : les cultures gourmandes en eau y sont pénalisées
    public const REGIONS_ARIDES = ['Gabès', 'Gafsa', 'Kébili', 'Médenine', 'Tataouine', 'Tozeur',
                                   'Sfax', 'Sidi Bouzid', 'Kairouan', 'Kasserine'];

    // Légumineuses : elles enrichissent le sol (bonne rotation après une céréale)
    public const LEGUMINEUSES = ['Fève', 'Pois chiche', 'Luzerne'];

    /* =========================================================
     *  CATALOGUE
     * ========================================================= */

    public function listCultures(): array
    {
        return config::getConnexion()->query("SELECT * FROM culture ORDER BY categorie, nom")->fetchAll();
    }

    public function getCulture(int $id)
    {
        $req = config::getConnexion()->prepare("SELECT * FROM culture WHERE id = :id");
        $req->execute(['id' => $id]);
        return $req->fetch();
    }

    // Retrouve une culture du catalogue à partir d'un nom saisi ("ble dur", "Blé", "Tomate cerise"…)
    public function trouver(string $nom): ?array
    {
        static $catalogue = null;
        $catalogue ??= $this->listCultures();
        $cherche = RecolteController::normaliser($nom);
        if ($cherche === '') {
            return null;
        }
        foreach ($catalogue as $c) {                                    // 1) nom exact
            if (RecolteController::normaliser($c['nom']) === $cherche) {
                return $c;
            }
        }
        foreach ($catalogue as $c) {                                    // 2) "Tomate cerise" → Tomate
            $n = RecolteController::normaliser($c['nom']);
            if (str_starts_with($cherche, $n . ' ') || str_starts_with($n, $cherche . ' ')) {
                return $c;
            }
        }
        return null;
    }

    // Noms proposés pendant la saisie : catalogue + variétés du Musée (autre base "taam_bledi")
    public function nomsPourAutocompletion(): array
    {
        $noms = array_column($this->listCultures(), 'nom');
        try {
            $varietes = config::getConnexion()->query("SELECT name FROM taam_bledi.varieties ORDER BY name")
                                              ->fetchAll(PDO::FETCH_COLUMN);
            $noms = array_merge($noms, $varietes);
        } catch (Throwable $e) {
            // Base du Musée absente : on garde seulement le catalogue
        }
        $noms = array_values(array_unique(array_filter(array_map('trim', $noms))));
        sort($noms, SORT_LOCALE_STRING);
        return $noms;
    }

    // Modification par l'admin (prix du marché, rendement, besoins…)
    public function updateCulture(int $id, array $d): void
    {
        $sql = "UPDATE culture SET categorie = :cat, sols = :sols, saisons = :saisons, regions = :regions,
                besoinEau = :eau, kc = :kc, rendement = :rdt, dureeJours = :duree, prixKg = :prix, conseil = :conseil
                WHERE id = :id";
        config::getConnexion()->prepare($sql)->execute($this->parametres($d) + ['id' => $id]);
    }

    public function addCulture(array $d): void
    {
        $sql = "INSERT INTO culture (nom, categorie, sols, saisons, regions, besoinEau, kc, rendement, dureeJours, prixKg, conseil)
                VALUES (:nom, :cat, :sols, :saisons, :regions, :eau, :kc, :rdt, :duree, :prix, :conseil)";
        config::getConnexion()->prepare($sql)->execute($this->parametres($d) + ['nom' => trim($d['nom'])]);
    }

    public function deleteCulture(int $id): bool
    {
        $req = config::getConnexion()->prepare("DELETE FROM culture WHERE id = :id");
        $req->execute(['id' => $id]);
        return $req->rowCount() > 0;
    }

    private function parametres(array $d): array
    {
        return [
            'cat'     => $d['categorie'],
            'sols'    => implode(',', $d['sols']),
            'saisons' => implode(',', $d['saisons']),
            'regions' => empty($d['regions']) ? '*' : implode(',', $d['regions']),
            'eau'     => $d['besoinEau'],
            'kc'      => (float) str_replace(',', '.', $d['kc']),
            'rdt'     => (float) str_replace(',', '.', $d['rendement']),
            'duree'   => (int) $d['dureeJours'],
            'prix'    => (float) str_replace(',', '.', $d['prixKg']),
            'conseil' => trim($d['conseil']),
        ];
    }

    public function validateCulture(array $d, ?int $exceptId = null): array
    {
        $e = [];
        if ($exceptId === null) {
            $nom = trim($d['nom'] ?? '');
            if (mb_strlen($nom) < 2 || mb_strlen($nom) > 60 || !preg_match("/^[\p{L}\s'\-]+$/u", $nom)) {
                $e['nom'] = 'Le nom doit contenir 2 à 60 lettres.';
            } else {
                $req = config::getConnexion()->prepare("SELECT COUNT(*) FROM culture WHERE nom = :n");
                $req->execute(['n' => $nom]);
                if ($req->fetchColumn() > 0) {
                    $e['nom'] = 'Cette culture existe déjà.';
                }
            }
        }
        if (!in_array($d['categorie'] ?? '', self::CATEGORIES, true)) {
            $e['categorie'] = 'Choisissez une catégorie.';
        }
        $sols = $d['sols'] ?? [];
        if (!is_array($sols) || empty($sols) || array_diff($sols, TerrainController::TYPES_SOL)) {
            $e['sols'] = 'Cochez au moins un type de sol.';
        }
        $saisons = $d['saisons'] ?? [];
        if (!is_array($saisons) || empty($saisons) || array_diff($saisons, self::SAISONS)) {
            $e['saisons'] = 'Cochez au moins une saison.';
        }
        $regions = $d['regions'] ?? [];
        if (!is_array($regions) || array_diff($regions, TerrainController::WILAYAS)) {
            $e['regions'] = 'Région invalide.';
        }
        if (!isset(self::EAU[$d['besoinEau'] ?? ''])) {
            $e['besoinEau'] = "Choisissez le besoin en eau.";
        }
        $nombres = ['kc' => [0.2, 1.5, 'Le coefficient kc doit être entre 0,2 et 1,5.'],
                    'rendement' => [0.1, 200, 'Le rendement doit être entre 0,1 et 200 t/ha.'],
                    'dureeJours' => [20, 400, 'La durée doit être entre 20 et 400 jours.'],
                    'prixKg' => [0.01, 500, 'Le prix doit être entre 0,01 et 500 DT/kg.']];
        foreach ($nombres as $champ => [$min, $max, $message]) {
            $v = str_replace(',', '.', trim((string) ($d[$champ] ?? '')));
            if (!is_numeric($v) || (float) $v < $min || (float) $v > $max) {
                $e[$champ] = $message;
            }
        }
        $conseil = trim($d['conseil'] ?? '');
        if (mb_strlen($conseil) < 5 || mb_strlen($conseil) > 255) {
            $e['conseil'] = 'Le conseil doit contenir 5 à 255 caractères.';
        }
        return $e;
    }

    /* =========================================================
     *  MOTEUR DE RECOMMANDATION
     * ========================================================= */

    // Saison actuelle (pour pré-remplir le formulaire)
    public static function saisonActuelle(?int $mois = null): string
    {
        $mois ??= (int) date('n');
        return match (true) {
            in_array($mois, [9, 10, 11], true) => 'Automne',
            in_array($mois, [12, 1, 2], true)  => 'Hiver',
            in_array($mois, [3, 4, 5], true)   => 'Printemps',
            default                            => 'Été',
        };
    }

    /**
     * Classe toutes les cultures du catalogue pour un terrain et une saison.
     * Chaque résultat : culture, score (0-100), raisons [[+/-, modèle de texte, valeurs]], production et revenu estimés.
     * (modèle + valeurs : la vue peut traduire la phrase avec te($modele, $valeurs))
     */
    public function recommander(array $terrain, string $saison): array
    {
        $historique = $this->rendementsHistoriques((int) $terrain['idUtilisateur']);
        $derniere   = $this->derniereCulture((int) $terrain['id']);
        $aride      = in_array($terrain['localisation'], self::REGIONS_ARIDES, true);
        $resultats  = [];

        foreach ($this->listCultures() as $c) {
            $score   = 0;
            $raisons = [];
            $sols    = explode(',', $c['sols']);
            $saisons = explode(',', $c['saisons']);
            $regions = $c['regions'] === '*' ? [] : explode(',', $c['regions']);

            // 1) Sol (30 points)
            if (in_array($terrain['typeSol'], $sols, true)) {
                $score += 30;
                $raisons[] = ['+', 'Sol {sol} adapté', ['sol' => $terrain['typeSol']]];
            } else {
                $raisons[] = ['-', 'Sol {sol} peu adapté (préfère : {liste})', ['sol' => $terrain['typeSol'], 'liste' => $sols]];
            }

            // 2) Saison de plantation (25 points)
            if (in_array($saison, $saisons, true)) {
                $score += 25;
                $raisons[] = ['+', 'Bonne saison de plantation', []];
            } else {
                $raisons[] = ['-', 'Hors saison (à planter en : {liste})', ['liste' => $saisons]];
            }

            // 3) Région (20 points)
            if (empty($regions)) {
                $score += 15;
                $raisons[] = ['+', 'Se cultive dans toute la Tunisie', []];
            } elseif (in_array($terrain['localisation'], $regions, true)) {
                $score += 20;
                $raisons[] = ['+', 'Culture typique de la région de {wilaya}', ['wilaya' => $terrain['localisation']]];
            } else {
                $raisons[] = ['-', 'Peu cultivée à {wilaya}', ['wilaya' => $terrain['localisation']]];
            }

            // 4) Eau (15 points) : plus important dans les régions sèches
            $pointsEau = ['FAIBLE' => 15, 'MOYEN' => $aride ? 6 : 10, 'ELEVE' => $aride ? 0 : 4][$c['besoinEau']] ?? 0;
            $score += $pointsEau;
            $raisons[] = [$pointsEau >= 10 ? '+' : '-',
                          $aride && $c['besoinEau'] === 'ELEVE' ? 'Besoin en eau {eau} (région sèche)' : 'Besoin en eau {eau}',
                          ['eau' => self::EAU[$c['besoinEau']] ?? '']];

            // 5) Votre historique (10 points)
            $cle = RecolteController::normaliser($c['nom']);
            if (isset($historique[$cle])) {
                $ratio = $historique[$cle] / max(0.01, (float) $c['rendement']);
                $pts   = $ratio >= 1 ? 10 : ($ratio >= 0.7 ? 6 : 2);
                $score += $pts;
                $raisons[] = [$pts >= 6 ? '+' : '-', 'Votre rendement passé : {moi} t/ha (moyenne : {ref})',
                              ['moi' => self::nombre($historique[$cle]), 'ref' => self::nombre((float) $c['rendement'])]];
            } else {
                $score += 5;
            }

            // 6) Rotation des cultures
            if ($derniere !== null) {
                $precedente = $this->trouver($derniere);
                if (RecolteController::normaliser($derniere) === $cle
                    || ($precedente && RecolteController::normaliser($precedente['nom']) === $cle)) {
                    $score -= 20;
                    $raisons[] = ['-', 'Déjà cultivée juste avant sur ce terrain (rotation conseillée)', []];
                } elseif ($precedente && $precedente['categorie'] === $c['categorie'] && $c['categorie'] !== 'Oliviers') {
                    $score -= 8;
                    $raisons[] = ['-', 'Même famille que la culture précédente ({c})', ['c' => $precedente['nom']]];
                } elseif (in_array($c['nom'], self::LEGUMINEUSES, true) && $precedente && $precedente['categorie'] === 'Céréales') {
                    $score += 5;
                    $raisons[] = ['+', 'Légumineuse après une céréale : excellente rotation', []];
                }
            }

            $productionKg = (float) $c['rendement'] * (float) $terrain['superficie'] * 1000;
            $resultats[] = [
                'culture'      => $c,
                'score'        => max(0, min(100, $score)),
                'raisons'      => $raisons,
                'productionKg' => $productionKg,
                'revenu'       => $productionKg * (float) $c['prixKg'],
            ];
        }

        usort($resultats, fn($a, $b) => [$b['score'], $b['revenu']] <=> [$a['score'], $a['revenu']]);
        return $resultats;
    }

    // Rendement moyen (t/ha) de l'utilisateur par culture, d'après ses récoltes terminées
    private function rendementsHistoriques(int $idUtilisateur): array
    {
        $sql = "SELECT r.culture, r.quantiteKg, t.superficie
                FROM recolte r JOIN terrain t ON t.id = r.idTerrain
                WHERE t.idUtilisateur = :u AND r.quantiteKg IS NOT NULL";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['u' => $idUtilisateur]);
        $valeurs = [];
        foreach ($req->fetchAll() as $r) {
            $c   = $this->trouver($r['culture']);
            $cle = RecolteController::normaliser($c['nom'] ?? $r['culture']);
            $valeurs[$cle][] = (float) $r['quantiteKg'] / 1000 / max(0.01, (float) $r['superficie']);
        }
        return array_map(fn($liste) => array_sum($liste) / count($liste), $valeurs);
    }

    // Dernière culture récoltée sur ce terrain (rotation)
    private function derniereCulture(int $idTerrain): ?string
    {
        $req = config::getConnexion()->prepare(
            "SELECT culture FROM recolte WHERE idTerrain = :t
             ORDER BY datePlantation DESC, id DESC LIMIT 1"
        );
        $req->execute(['t' => $idTerrain]);
        $c = $req->fetchColumn();
        return $c === false ? null : $c;
    }

    public static function nombre(float $n, int $decimales = 1): string
    {
        return number_format($n, $decimales, ',', ' ');
    }
}
