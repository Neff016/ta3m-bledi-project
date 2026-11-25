<?php
/*
 * Controller : Terrain
 * Toutes les requêtes SQL du module Terrain (requêtes préparées PDO).
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Model/Terrain.php';

class TerrainController
{
    public const WILAYAS = [
        'Ariana', 'Béja', 'Ben Arous', 'Bizerte', 'Gabès', 'Gafsa', 'Jendouba', 'Kairouan',
        'Kasserine', 'Kébili', 'Le Kef', 'Mahdia', 'La Manouba', 'Médenine', 'Monastir', 'Nabeul',
        'Sfax', 'Sidi Bouzid', 'Siliana', 'Sousse', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan',
    ];

    // Centre de chaque wilaya (pour centrer la carte et pour la météo si pas de GPS)
    public const CENTRES = [
        'Ariana' => [36.8665, 10.1647], 'Béja' => [36.7256, 9.1817], 'Ben Arous' => [36.7531, 10.2189],
        'Bizerte' => [37.2744, 9.8739], 'Gabès' => [33.8815, 10.0982], 'Gafsa' => [34.4250, 8.7842],
        'Jendouba' => [36.5011, 8.7802], 'Kairouan' => [35.6781, 10.0963], 'Kasserine' => [35.1676, 8.8365],
        'Kébili' => [33.7044, 8.9690], 'Le Kef' => [36.1826, 8.7149], 'Mahdia' => [35.5047, 11.0622],
        'La Manouba' => [36.8101, 10.0956], 'Médenine' => [33.3549, 10.5055], 'Monastir' => [35.7643, 10.8113],
        'Nabeul' => [36.4513, 10.7357], 'Sfax' => [34.7406, 10.7603], 'Sidi Bouzid' => [35.0382, 9.4849],
        'Siliana' => [36.0849, 9.3708], 'Sousse' => [35.8256, 10.6084], 'Tataouine' => [32.9297, 10.4518],
        'Tozeur' => [33.9197, 8.1335], 'Tunis' => [36.8065, 10.1815], 'Zaghouan' => [36.4029, 10.1429],
    ];

    public const TYPES_SOL = ['Argileux', 'Sableux', 'Calcaire', 'Limoneux', 'Humifère'];

    // Colonnes autorisées pour le tri du Back Office (jamais de texte utilisateur dans le SQL)
    public const TRIS = [
        'id' => 't.id', 'nom' => 't.nom', 'localisation' => 't.localisation', 'superficie' => 't.superficie',
        'typeSol' => 't.typeSol', 'proprietaire' => 'proprietaire', 'nbRecoltes' => 'nbRecoltes',
    ];

    /* =========================================================
     *  LECTURE
     * ========================================================= */

    // Front : les terrains d'un seul utilisateur (+ nombre de récoltes et production en kg)
    public function listTerrainsByUser(int $idUtilisateur): array
    {
        $sql = "SELECT t.*,
                       (SELECT COUNT(*) FROM recolte r WHERE r.idTerrain = t.id) AS nbRecoltes,
                       (SELECT COALESCE(SUM(r.quantiteKg), 0) FROM recolte r
                         WHERE r.idTerrain = t.id) AS productionKg
                FROM terrain t
                WHERE t.idUtilisateur = :idU
                ORDER BY t.id DESC";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['idU' => $idUtilisateur]);
        return $req->fetchAll();
    }

    // Back : tous les terrains avec filtres, tri et pagination
    // Retourne ['lignes' => [...], 'total' => nombre de résultats]
    public function listAllTerrains(array $filtres = [], string $tri = 'id', string $ordre = 'DESC',
                                    int $page = 1, int $parPage = 0): array
    {
        $where  = ["u.statut <> 'SUPPRIME'"];   // comptes supprimés : données cachées
        $params = [];

        if (($filtres['q'] ?? '') !== '') {
            $where[] = "(t.nom LIKE :q1 OR t.localisation LIKE :q2 OR u.nom LIKE :q3 OR u.prenom LIKE :q4)";
            $like = '%' . $filtres['q'] . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        if (in_array($filtres['wilaya'] ?? '', self::WILAYAS, true)) {
            $where[] = "t.localisation = :wilaya";
            $params['wilaya'] = $filtres['wilaya'];
        }
        if (in_array($filtres['sol'] ?? '', self::TYPES_SOL, true)) {
            $where[] = "t.typeSol = :sol";
            $params['sol'] = $filtres['sol'];
        }

        $base = "FROM terrain t JOIN utilisateur u ON u.id = t.idUtilisateur WHERE " . implode(' AND ', $where);

        $total = config::getConnexion()->prepare("SELECT COUNT(*) $base");
        $total->execute($params);

        $colonne = self::TRIS[$tri] ?? 't.id';
        $sens    = strtoupper($ordre) === 'ASC' ? 'ASC' : 'DESC';
        $sql = "SELECT t.*, CONCAT(u.prenom, ' ', u.nom) AS proprietaire,
                       (SELECT COUNT(*) FROM recolte r WHERE r.idTerrain = t.id) AS nbRecoltes,
                       (SELECT COUNT(*) FROM parcelle p WHERE p.idTerrain = t.id) AS nbParcelles,
                       (SELECT c.type FROM contrat_foncier c WHERE c.idTerrain = t.id) AS typeContrat
                $base ORDER BY $colonne $sens, t.id DESC";
        if ($parPage > 0) {
            $sql .= " LIMIT :limite OFFSET :decalage";
        }
        $req = config::getConnexion()->prepare($sql);
        foreach ($params as $cle => $valeur) {
            $req->bindValue($cle, $valeur);
        }
        if ($parPage > 0) {
            $req->bindValue('limite', $parPage, PDO::PARAM_INT);
            $req->bindValue('decalage', max(0, ($page - 1) * $parPage), PDO::PARAM_INT);
        }
        $req->execute();
        return ['lignes' => $req->fetchAll(), 'total' => (int) $total->fetchColumn()];
    }

    // Un terrain SEULEMENT s'il appartient à cet utilisateur (sinon false)
    public function getTerrainOfUser(int $id, int $idUtilisateur)
    {
        $sql = "SELECT * FROM terrain WHERE id = :id AND idUtilisateur = :idU";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['id' => $id, 'idU' => $idUtilisateur]);
        return $req->fetch();
    }

    // Terrain avec son propriétaire (page publique de traçabilité, Back Office)
    public function getTerrainAvecProprietaire(int $id)
    {
        $sql = "SELECT t.*, u.prenom, u.nom AS nomProprietaire
                FROM terrain t JOIN utilisateur u ON u.id = t.idUtilisateur
                WHERE t.id = :id AND u.statut <> 'SUPPRIME'";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['id' => $id]);
        return $req->fetch();
    }

    // Position à utiliser pour la carte et la météo : GPS du terrain, sinon centre de la wilaya
    public static function position(array $terrain): array
    {
        if ($terrain['latitude'] !== null && $terrain['longitude'] !== null) {
            return [(float) $terrain['latitude'], (float) $terrain['longitude'], true];
        }
        [$lat, $lng] = self::CENTRES[$terrain['localisation']] ?? [36.8065, 10.1815];
        return [$lat, $lng, false];
    }

    /* =========================================================
     *  AJOUT / MODIFICATION / SUPPRESSION
     * ========================================================= */

    public function addTerrain(Terrain $terrain): int
    {
        $sql = "INSERT INTO terrain (nom, localisation, superficie, typeSol, idUtilisateur, latitude, longitude, polygone)
                VALUES (:nom, :localisation, :superficie, :typeSol, :idU, :lat, :lng, :poly)";
        $req = config::getConnexion()->prepare($sql);
        $req->execute($this->parametres($terrain) + ['idU' => $terrain->getIdUtilisateur()]);
        return (int) config::getConnexion()->lastInsertId();
    }

    // Modifie seulement si le terrain appartient à l'utilisateur
    public function updateTerrain(Terrain $terrain): void
    {
        $sql = "UPDATE terrain
                SET nom = :nom, localisation = :localisation, superficie = :superficie, typeSol = :typeSol,
                    latitude = :lat, longitude = :lng, polygone = :poly
                WHERE id = :id AND idUtilisateur = :idU";
        $req = config::getConnexion()->prepare($sql);
        $req->execute($this->parametres($terrain) + ['id' => $terrain->getId(), 'idU' => $terrain->getIdUtilisateur()]);
    }

    private function parametres(Terrain $t): array
    {
        return [
            'nom'          => $t->getNom(),
            'localisation' => $t->getLocalisation(),
            'superficie'   => $t->getSuperficie(),
            'typeSol'      => $t->getTypeSol(),
            'lat'          => $t->getLatitude(),
            'lng'          => $t->getLongitude(),
            'poly'         => $t->getPolygone(),
        ];
    }

    public function updatePhoto(int $id, int $idUtilisateur, ?string $photo): void
    {
        $req = config::getConnexion()->prepare("UPDATE terrain SET photo = :p WHERE id = :id AND idUtilisateur = :idU");
        $req->execute(['p' => $photo, 'id' => $id, 'idU' => $idUtilisateur]);
    }

    public function updateAgroPolygon(int $id, ?string $agroId): void
    {
        $req = config::getConnexion()->prepare("UPDATE terrain SET agroPolygonId = :a WHERE id = :id");
        $req->execute(['a' => $agroId, 'id' => $id]);
    }

    // Front : $idUtilisateur obligatoire (il ne supprime que les siens)
    // Back (admin) : $idUtilisateur = null → supprime n'importe quel terrain
    // Les récoltes et dépenses sont supprimées automatiquement (ON DELETE CASCADE)
    public function deleteTerrain(int $id, ?int $idUtilisateur = null): bool
    {
        if ($idUtilisateur === null) {
            $req = config::getConnexion()->prepare("DELETE FROM terrain WHERE id = :id");
            $req->execute(['id' => $id]);
        } else {
            $req = config::getConnexion()->prepare("DELETE FROM terrain WHERE id = :id AND idUtilisateur = :idU");
            $req->execute(['id' => $id, 'idU' => $idUtilisateur]);
        }
        return $req->rowCount() > 0;
    }

    /* =========================================================
     *  CONTRÔLE DE SAISIE (côté serveur)
     *  Retourne un tableau d'erreurs : ['champ' => 'message']
     *  Tableau vide = tout est correct.
     * ========================================================= */

    public function validateTerrain(array $data): array
    {
        $erreurs = [];

        // Nom : obligatoire, 3 à 100 caractères, lettres / chiffres / espaces / ' -
        $nom = trim($data['nom'] ?? '');
        if ($nom === '') {
            $erreurs['nom'] = 'Le nom du terrain est obligatoire.';
        } elseif (mb_strlen($nom) < 3 || mb_strlen($nom) > 100) {
            $erreurs['nom'] = 'Le nom doit contenir entre 3 et 100 caractères.';
        } elseif (!preg_match("/^[\p{L}0-9\s'\-]+$/u", $nom)) {
            $erreurs['nom'] = 'Le nom ne doit contenir que des lettres, chiffres, espaces, apostrophes ou tirets.';
        }

        // Localisation : une des 24 wilayas
        if (!in_array($data['localisation'] ?? '', self::WILAYAS, true)) {
            $erreurs['localisation'] = 'Veuillez choisir une wilaya.';
        }

        // Superficie : nombre > 0 et raisonnable
        $superficie = str_replace(',', '.', trim($data['superficie'] ?? ''));
        if ($superficie === '') {
            $erreurs['superficie'] = 'La superficie est obligatoire.';
        } elseif (!is_numeric($superficie)) {
            $erreurs['superficie'] = 'La superficie doit être un nombre.';
        } elseif ((float) $superficie <= 0) {
            $erreurs['superficie'] = 'La superficie doit être supérieure à 0.';
        } elseif ((float) $superficie > 100000) {
            $erreurs['superficie'] = 'La superficie ne peut pas dépasser 100 000 ha.';
        }

        // Type de sol : une valeur de la liste
        if (!in_array($data['typeSol'] ?? '', self::TYPES_SOL, true)) {
            $erreurs['typeSol'] = 'Veuillez choisir un type de sol.';
        }

        // Position GPS (facultative) : les deux ou aucune, et en Tunisie
        $lat = trim((string) ($data['latitude'] ?? ''));
        $lng = trim((string) ($data['longitude'] ?? ''));
        if ($lat !== '' || $lng !== '') {
            if (!is_numeric($lat) || !is_numeric($lng) || !self::enTunisie((float) $lat, (float) $lng)) {
                $erreurs['position'] = 'La position doit être un point en Tunisie (cliquez sur la carte).';
            }
        }

        // Parcelle dessinée (facultative) : liste de 3 à 200 points en Tunisie
        if (trim((string) ($data['polygone'] ?? '')) !== '' && self::lirePolygone($data['polygone']) === null) {
            $erreurs['position'] = 'La parcelle dessinée est invalide : dessinez au moins 3 points en Tunisie.';
        }

        return $erreurs;
    }

    public static function enTunisie(float $lat, float $lng): bool
    {
        return $lat >= 30.2 && $lat <= 37.6 && $lng >= 7.5 && $lng <= 11.7;
    }

    // Retourne la liste des points [[lat, lng], ...] ou null si invalide
    public static function lirePolygone(?string $json): ?array
    {
        $points = json_decode((string) $json, true);
        if (!is_array($points) || count($points) < 3 || count($points) > 200) {
            return null;
        }
        $propres = [];
        foreach ($points as $p) {
            if (!is_array($p) || count($p) !== 2 || !is_numeric($p[0]) || !is_numeric($p[1])
                || !self::enTunisie((float) $p[0], (float) $p[1])) {
                return null;
            }
            $propres[] = [round((float) $p[0], 6), round((float) $p[1], 6)];
        }
        return $propres;
    }

    // Surface d'un polygone sur la Terre, en hectares (formule sphérique, comme Leaflet/Google)
    public static function surfaceHectares(array $points): float
    {
        $rayon = 6378137.0;   // rayon de la Terre en mètres
        $aire  = 0.0;
        $n     = count($points);
        for ($i = 0; $i < $n; $i++) {
            [$lat1, $lng1] = $points[$i];
            [$lat2, $lng2] = $points[($i + 1) % $n];
            $aire += deg2rad($lng2 - $lng1) * (2 + sin(deg2rad($lat1)) + sin(deg2rad($lat2)));
        }
        return round(abs($aire * $rayon * $rayon / 2) / 10000, 2);
    }

    // Centre (moyenne des points) d'une parcelle
    public static function centre(array $points): array
    {
        $lat = array_sum(array_column($points, 0)) / count($points);
        $lng = array_sum(array_column($points, 1)) / count($points);
        return [round($lat, 6), round($lng, 6)];
    }

    /* =========================================================
     *  STATISTIQUES (Back Office)
     * ========================================================= */

    private const ACTIFS = "(SELECT id FROM utilisateur WHERE statut <> 'SUPPRIME')";

    public function countTerrains(): int
    {
        return (int) config::getConnexion()->query("SELECT COUNT(*) FROM terrain WHERE idUtilisateur IN " . self::ACTIFS)->fetchColumn();
    }

    public function sumSuperficie(): float
    {
        return (float) config::getConnexion()->query("SELECT COALESCE(SUM(superficie), 0) FROM terrain WHERE idUtilisateur IN " . self::ACTIFS)->fetchColumn();
    }

    public function countAgriculteurs(): int
    {
        return (int) config::getConnexion()->query("SELECT COUNT(DISTINCT idUtilisateur) FROM terrain WHERE idUtilisateur IN " . self::ACTIFS)->fetchColumn();
    }

    public function terrainsParWilaya(): array
    {
        $sql = "SELECT localisation, COUNT(*) AS nb, SUM(superficie) AS superficie
                FROM terrain
                WHERE idUtilisateur IN " . self::ACTIFS . "
                GROUP BY localisation
                ORDER BY nb DESC, localisation";
        return config::getConnexion()->query($sql)->fetchAll();
    }

    // Tous les terrains pour la carte du Back Office
    public function terrainsPourCarte(): array
    {
        $sql = "SELECT t.id, t.nom, t.localisation, t.superficie, t.typeSol, t.latitude, t.longitude, t.polygone,
                       CONCAT(u.prenom, ' ', u.nom) AS proprietaire
                FROM terrain t JOIN utilisateur u ON u.id = t.idUtilisateur
                WHERE u.statut <> 'SUPPRIME'";
        return config::getConnexion()->query($sql)->fetchAll();
    }
}
