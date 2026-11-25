<?php
/*
 * Controller : Recolte
 * Toutes les requêtes SQL du module Récolte (requêtes préparées PDO).
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Model/Recolte.php';
require_once __DIR__ . '/TerrainController.php';

class RecolteController
{
    public const UNITES   = ['kg', 'quintal', 'tonne'];
    public const FACTEURS = ['kg' => 1, 'quintal' => 100, 'tonne' => 1000];   // conversion en kg

    public const TRIS = [
        'dateRecolte' => 'r.dateRecolte', 'culture' => 'r.culture', 'terrain' => 'terrainNom',
        'quantiteKg' => 'r.quantiteKg', 'proprietaire' => 'proprietaire',
    ];

    /* =========================================================
     *  LECTURE
     * ========================================================= */

    // Front : toutes les récoltes des terrains d'un utilisateur
    public function listRecoltesByUser(int $idUtilisateur): array
    {
        $sql = "SELECT r.*, t.nom AS terrainNom, t.localisation, p.nom AS parcelleNom
                FROM recolte r
                JOIN terrain t ON t.id = r.idTerrain
                LEFT JOIN parcelle p ON p.id = r.idParcelle
                WHERE t.idUtilisateur = :idU
                ORDER BY r.dateRecolte DESC, r.id DESC";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['idU' => $idUtilisateur]);
        return $req->fetchAll();
    }

    // Les récoltes d'un terrain (page Détails terrain)
    public function listRecoltesByTerrain(int $idTerrain): array
    {
        $sql = "SELECT r.*, p.nom AS parcelleNom FROM recolte r LEFT JOIN parcelle p ON p.id = r.idParcelle
                WHERE r.idTerrain = :idT ORDER BY r.dateRecolte DESC, r.id DESC";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['idT' => $idTerrain]);
        return $req->fetchAll();
    }

    // Back : toutes les récoltes avec filtres, tri et pagination → ['lignes' => [...], 'total' => n]
    public function listAllRecoltes(array $filtres = [], string $tri = 'dateRecolte', string $ordre = 'DESC',
                                    int $page = 1, int $parPage = 0): array
    {
        $where  = ["u.statut <> 'SUPPRIME'"];   // comptes supprimés : données cachées
        $params = [];

        if (($filtres['q'] ?? '') !== '') {
            $where[] = "(r.culture LIKE :q1 OR t.nom LIKE :q2 OR u.nom LIKE :q3 OR u.prenom LIKE :q4)";
            $like = '%' . $filtres['q'] . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        if (in_array($filtres['wilaya'] ?? '', TerrainController::WILAYAS, true)) {
            $where[] = "t.localisation = :wilaya";
            $params['wilaya'] = $filtres['wilaya'];
        }
        if (self::dateValide($filtres['du'] ?? '')) {
            $where[] = "r.dateRecolte >= :du";
            $params['du'] = $filtres['du'];
        }
        if (self::dateValide($filtres['au'] ?? '')) {
            $where[] = "r.dateRecolte <= :au";
            $params['au'] = $filtres['au'];
        }

        $base = "FROM recolte r JOIN terrain t ON t.id = r.idTerrain JOIN utilisateur u ON u.id = t.idUtilisateur
                 WHERE " . implode(' AND ', $where);

        $total = config::getConnexion()->prepare("SELECT COUNT(*) $base");
        $total->execute($params);

        $colonne = self::TRIS[$tri] ?? 'r.dateRecolte';
        $sens    = strtoupper($ordre) === 'ASC' ? 'ASC' : 'DESC';
        $sql = "SELECT r.*, t.nom AS terrainNom, t.localisation, CONCAT(u.prenom, ' ', u.nom) AS proprietaire,
                       (SELECT q.classe FROM controle_qualite q WHERE q.idRecolte = r.id) AS classe
                $base ORDER BY $colonne $sens, r.id DESC";
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

    // Une récolte SEULEMENT si son terrain appartient à cet utilisateur (sinon false)
    public function getRecolteOfUser(int $id, int $idUtilisateur)
    {
        $sql = "SELECT r.*, t.nom AS terrainNom, t.localisation
                FROM recolte r
                JOIN terrain t ON t.id = r.idTerrain
                WHERE r.id = :id AND t.idUtilisateur = :idU";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['id' => $id, 'idU' => $idUtilisateur]);
        return $req->fetch();
    }

    // Back Office : une récolte (n'importe quel agriculteur actif) avec son terrain et son propriétaire
    public function getRecolteAdmin(int $id)
    {
        $sql = "SELECT r.*, t.nom AS terrainNom, t.localisation, t.idUtilisateur
                FROM recolte r JOIN terrain t ON t.id = r.idTerrain JOIN utilisateur u ON u.id = t.idUtilisateur
                WHERE r.id = :id AND u.statut <> 'SUPPRIME'";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['id' => $id]);
        return $req->fetch();
    }

    // Back Office : terrains de tous les agriculteurs actifs (liste du formulaire récolte admin)
    public function terrainsPourAdmin(): array
    {
        return config::getConnexion()->query(
            "SELECT t.id, t.nom, t.localisation, t.idUtilisateur, CONCAT(u.prenom, ' ', u.nom) AS agriculteur
             FROM terrain t JOIN utilisateur u ON u.id = t.idUtilisateur
             WHERE u.statut = 'ACTIF' ORDER BY u.nom, u.prenom, t.nom")->fetchAll();
    }

    // Page publique du QR code : récolte + terrain + agriculteur (sans email ni téléphone)
    public function getParCode(string $code)
    {
        if (!preg_match('/^[A-F0-9]{12}$/', $code)) {
            return false;
        }
        $sql = "SELECT r.id, r.culture, r.datePlantation, r.dateRecolte, r.quantite, r.unite, r.quantiteKg, r.photo,
                       r.idTerrain, r.idParcelle, t.nom AS terrainNom, t.localisation, t.typeSol, t.superficie,
                       t.latitude, t.longitude, t.polygone, u.prenom, u.nom,
                       p.nom AS parcelleNom, p.surface AS parcelleSurface, p.polygone AS parcellePolygone
                FROM recolte r
                JOIN terrain t ON t.id = r.idTerrain
                LEFT JOIN parcelle p ON p.id = r.idParcelle
                JOIN utilisateur u ON u.id = t.idUtilisateur
                WHERE r.codeTracabilite = :code AND u.statut = 'ACTIF'";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['code' => $code]);
        return $req->fetch();
    }

    /* =========================================================
     *  AJOUT / MODIFICATION / SUPPRESSION
     * ========================================================= */

    public function addRecolte(Recolte $recolte): int
    {
        $sql = "INSERT INTO recolte (culture, datePlantation, dateRecolte, quantite, unite, idTerrain,
                                     idParcelle, quantiteKg, codeTracabilite)
                VALUES (:culture, :datePlantation, :dateRecolte, :quantite, :unite, :idT,
                        :idP, :kg, :code)";
        $req = config::getConnexion()->prepare($sql);
        $req->execute($this->parametres($recolte) + ['code' => self::nouveauCode()]);
        return (int) config::getConnexion()->lastInsertId();
    }

    // ⚠️ Appeler seulement après avoir vérifié (getRecolteOfUser + validateRecolte)
    //    que la récolte ET le nouveau terrain appartiennent à l'utilisateur.
    public function updateRecolte(Recolte $recolte): void
    {
        $sql = "UPDATE recolte
                SET culture = :culture, datePlantation = :datePlantation, dateRecolte = :dateRecolte,
                    quantite = :quantite, unite = :unite, idTerrain = :idT,
                    idParcelle = :idP, quantiteKg = :kg
                WHERE id = :id";
        $req = config::getConnexion()->prepare($sql);
        $req->execute($this->parametres($recolte) + ['id' => $recolte->getId()]);
    }

    private function parametres(Recolte $r): array
    {
        return [
            'culture'        => $r->getCulture(),
            'datePlantation' => $r->getDatePlantation(),
            'dateRecolte'    => $r->getDateRecolte(),
            'quantite'       => $r->getQuantite(),
            'unite'          => $r->getUnite(),
            'idT'            => $r->getIdTerrain(),
            'idP'            => $r->getIdParcelle(),
            'kg'             => $r->getQuantiteKg(),
        ];
    }

    public function updatePhoto(int $id, ?string $photo): void
    {
        $req = config::getConnexion()->prepare("UPDATE recolte SET photo = :p WHERE id = :id");
        $req->execute(['p' => $photo, 'id' => $id]);
    }

    // Code de traçabilité unique (12 caractères hexadécimaux, impossible à deviner)
    public static function nouveauCode(): string
    {
        return strtoupper(bin2hex(random_bytes(6)));
    }

    // Front : $idUtilisateur obligatoire / Back (admin) : null
    public function deleteRecolte(int $id, ?int $idUtilisateur = null): bool
    {
        if ($idUtilisateur === null) {
            $req = config::getConnexion()->prepare("DELETE FROM recolte WHERE id = :id");
            $req->execute(['id' => $id]);
        } else {
            // Sous-requête : fonctionne avec MySQL et MariaDB
            $sql = "DELETE FROM recolte
                    WHERE id = :id AND idTerrain IN (SELECT id FROM terrain WHERE idUtilisateur = :idU)";
            $req = config::getConnexion()->prepare($sql);
            $req->execute(['id' => $id, 'idU' => $idUtilisateur]);
        }
        return $req->rowCount() > 0;
    }

    /* =========================================================
     *  CONTRÔLE DE SAISIE (côté serveur)
     * ========================================================= */

    public function validateRecolte(array $data, int $idUtilisateur): array
    {
        $erreurs = [];

        // Terrain : obligatoire ET doit appartenir à l'utilisateur
        $idTerrain = filter_var($data['idTerrain'] ?? '', FILTER_VALIDATE_INT);
        if (!$idTerrain) {
            $erreurs['idTerrain'] = 'Veuillez choisir un terrain.';
        } elseif (!(new TerrainController())->getTerrainOfUser($idTerrain, $idUtilisateur)) {
            $erreurs['idTerrain'] = 'Ce terrain ne vous appartient pas.';
        }

        // Culture : obligatoire, 2 à 100 caractères (lettres, chiffres, espaces, ' - ( ) .)
        $culture = trim($data['culture'] ?? '');
        if ($culture === '') {
            $erreurs['culture'] = 'La culture est obligatoire.';
        } elseif (mb_strlen($culture) < 2 || mb_strlen($culture) > 100) {
            $erreurs['culture'] = 'La culture doit contenir entre 2 et 100 caractères.';
        } elseif (!preg_match("/^[\p{L}0-9\s'’\-().]+$/u", $culture)) {
            $erreurs['culture'] = 'La culture ne doit contenir que des lettres, chiffres, espaces, apostrophes, tirets ou parenthèses.';
        }

        // Dates : la récolte est déjà faite → pas de date dans le futur
        $datePlantation = self::dateValide($data['datePlantation'] ?? '');
        $dateRecolte    = self::dateValide($data['dateRecolte'] ?? '');
        $aujourdhui     = new DateTime('today');

        if (!$datePlantation) {
            $erreurs['datePlantation'] = 'Veuillez saisir une date de plantation valide.';
        } elseif ($datePlantation > $aujourdhui) {
            $erreurs['datePlantation'] = 'La date de plantation ne peut pas être dans le futur.';
        } elseif ($datePlantation < new DateTime('2000-01-01')) {
            $erreurs['datePlantation'] = 'La date de plantation n’est pas réaliste.';
        }

        if (!$dateRecolte) {
            $erreurs['dateRecolte'] = 'Veuillez saisir une date de récolte valide.';
        } elseif ($datePlantation && $dateRecolte <= $datePlantation) {
            $erreurs['dateRecolte'] = 'La date de récolte doit être après la date de plantation.';
        } elseif ($dateRecolte > $aujourdhui) {
            $erreurs['dateRecolte'] = 'La date de récolte ne peut pas être dans le futur.';
        }

        // Quantité : obligatoire, nombre > 0
        $quantite = str_replace(',', '.', trim((string) ($data['quantite'] ?? '')));
        if ($quantite === '') {
            $erreurs['quantite'] = 'La quantité est obligatoire.';
        } elseif (!is_numeric($quantite)) {
            $erreurs['quantite'] = 'La quantité doit être un nombre.';
        } elseif ((float) $quantite <= 0) {
            $erreurs['quantite'] = 'La quantité doit être supérieure à 0.';
        } elseif ((float) $quantite > 1000000) {
            $erreurs['quantite'] = 'La quantité ne peut pas dépasser 1 000 000.';
        }

        // Unité : une valeur de la liste
        if (!in_array($data['unite'] ?? '', self::UNITES, true)) {
            $erreurs['unite'] = 'Veuillez choisir une unité.';
        }

        // Parcelle (facultative) : doit être une parcelle du terrain choisi
        $idParcelle = trim((string) ($data['idParcelle'] ?? ''));
        if ($idParcelle !== '' && !isset($erreurs['idTerrain'])) {
            $req = config::getConnexion()->prepare("SELECT COUNT(*) FROM parcelle WHERE id = :p AND idTerrain = :t");
            $req->execute(['p' => (int) $idParcelle, 't' => (int) $idTerrain]);
            if (!ctype_digit($idParcelle) || $req->fetchColumn() == 0) {
                $erreurs['idParcelle'] = 'Cette parcelle n’appartient pas au terrain choisi.';
            }
        }

        return $erreurs;
    }

    // Retourne un DateTime si la chaîne est une vraie date AAAA-MM-JJ, sinon null
    public static function dateValide(string $valeur): ?DateTime
    {
        $date = DateTime::createFromFormat('!Y-m-d', $valeur);
        if ($date && $date->format('Y-m-d') === $valeur) {
            return $date;
        }
        return null;
    }

    // Rotation des cultures : même culture que la récolte précédente sur ce terrain ?
    // Retourne un message d'avertissement (pas une erreur) ou null
    public function avertissementRotation(int $idTerrain, string $culture, string $datePlantation, ?int $exceptId = null,
                                          ?int $idParcelle = null): ?string
    {
        // Avec une parcelle : on compare avec la récolte précédente de CETTE parcelle
        $sql = "SELECT culture, dateRecolte FROM recolte
                WHERE idTerrain = :t AND dateRecolte <= :d AND id <> :ex"
             . ($idParcelle ? " AND idParcelle = :p" : "") . "
                ORDER BY dateRecolte DESC LIMIT 1";
        $params = ['t' => $idTerrain, 'd' => $datePlantation, 'ex' => $exceptId ?? 0];
        if ($idParcelle) {
            $params['p'] = $idParcelle;
        }
        $req = config::getConnexion()->prepare($sql);
        $req->execute($params);
        $precedente = $req->fetch();
        if ($precedente && self::normaliser($precedente['culture']) === self::normaliser($culture)) {
            return 'Rotation des cultures : « ' . $precedente['culture'] . ' » a déjà été cultivé sur ce terrain '
                 . '(récolte du ' . date('d/m/Y', strtotime($precedente['dateRecolte'])) . '). '
                 . 'Replanter la même culture fatigue le sol et favorise les maladies : pensez à une légumineuse (fève, pois chiche).';
        }
        return null;
    }

    // 100.00 → "100" ; 1.20 → "1.2" (affichage sans zéros inutiles)
    public static function nombreCourt(float $n): string
    {
        $texte = number_format($n, 2, '.', '');
        return str_contains($texte, '.') ? rtrim(rtrim($texte, '0'), '.') : $texte;
    }

    // "Blé Dur " → "ble dur" (comparaison sans accents ni majuscules)
    public static function normaliser(string $texte): string
    {
        $texte = mb_strtolower(trim($texte));
        return strtr($texte, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a',
                              'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'û' => 'u', 'ù' => 'u', 'ç' => 'c']);
    }

    /* =========================================================
     *  STATISTIQUES (Back Office)
     * ========================================================= */

    public function countRecoltes(): int
    {
        return (int) config::getConnexion()->query("SELECT COUNT(*) FROM recolte r JOIN terrain t ON t.id = r.idTerrain WHERE t.idUtilisateur IN (SELECT id FROM utilisateur WHERE statut <> 'SUPPRIME')")->fetchColumn();
    }

    public function dernieresRecoltes(int $limite = 5): array
    {
        $sql = "SELECT r.culture, r.dateRecolte, t.nom AS terrainNom
                FROM recolte r
                JOIN terrain t ON t.id = r.idTerrain
                WHERE t.idUtilisateur IN (SELECT id FROM utilisateur WHERE statut <> 'SUPPRIME')
                ORDER BY r.dateRecolte DESC
                LIMIT :limite";
        $req = config::getConnexion()->prepare($sql);
        $req->bindValue('limite', $limite, PDO::PARAM_INT);
        $req->execute();
        return $req->fetchAll();
    }
}
