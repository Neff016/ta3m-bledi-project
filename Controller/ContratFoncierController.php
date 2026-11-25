<?php
/*
 * Controller : Contrat foncier d'un terrain (propriété, location, khammès, métayage)
 * Le contrat sert à :
 *  - déduire le loyer ou la part du propriétaire dans la rentabilité ;
 *  - prévenir l'agriculteur 30 jours avant la fin du contrat.
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Model/ContratFoncier.php';
require_once __DIR__ . '/RecolteController.php';

class ContratFoncierController
{
    public const TYPES = [
        'PROPRIETE' => 'Propriété',
        'LOCATION'  => 'Location',
        'KHAMMES'   => 'Association (khammès)',
        'METAYAGE'  => 'Métayage',
    ];
    // Part du propriétaire proposée par défaut (le khammès garde traditionnellement 1/5 de la récolte)
    public const PARTS_CONSEILLEES = ['KHAMMES' => 80, 'METAYAGE' => 50];
    public const JOURS_ALERTE = 30;

    /* ---------------- Lecture ---------------- */

    public function getParTerrain(int $idTerrain)
    {
        $req = config::getConnexion()->prepare("SELECT * FROM contrat_foncier WHERE idTerrain = :t");
        $req->execute(['t' => $idTerrain]);
        return $req->fetch();
    }

    // Tous les contrats d'un utilisateur, indexés par terrain
    public function parTerrainDeUtilisateur(int $idUtilisateur): array
    {
        $req = config::getConnexion()->prepare(
            "SELECT c.* FROM contrat_foncier c JOIN terrain t ON t.id = c.idTerrain WHERE t.idUtilisateur = :u");
        $req->execute(['u' => $idUtilisateur]);
        $resultat = [];
        foreach ($req->fetchAll() as $c) {
            $resultat[(int) $c['idTerrain']] = $c;
        }
        return $resultat;
    }

    // Contrats qui se terminent dans les $jours prochains jours (ou terminés depuis moins de 30 jours)
    public function alertesFin(int $idUtilisateur, int $jours = self::JOURS_ALERTE): array
    {
        $sql = "SELECT c.*, t.nom AS terrainNom FROM contrat_foncier c JOIN terrain t ON t.id = c.idTerrain
                WHERE t.idUtilisateur = :u AND c.dateFin IS NOT NULL AND c.dateFin BETWEEN :debut AND :fin
                ORDER BY c.dateFin";
        $req = config::getConnexion()->prepare($sql);
        $req->execute(['u' => $idUtilisateur,
                       'debut' => date('Y-m-d', strtotime('-30 days')),
                       'fin' => date('Y-m-d', strtotime("+$jours days"))]);
        $alertes = [];
        foreach ($req->fetchAll() as $c) {
            $c['joursRestants'] = $this->versObjet($c)->joursRestants();
            $alertes[] = $c;
        }
        return $alertes;
    }

    // Back Office : tous les contrats (comptes supprimés exclus)
    public function listAll(string $type = ''): array
    {
        $sql = "SELECT c.*, t.nom AS terrainNom, t.localisation, t.superficie,
                       CONCAT(u.prenom, ' ', u.nom) AS agriculteur
                FROM contrat_foncier c JOIN terrain t ON t.id = c.idTerrain JOIN utilisateur u ON u.id = t.idUtilisateur
                WHERE u.statut <> 'SUPPRIME'";
        $params = [];
        if (isset(self::TYPES[$type])) {
            $sql .= " AND c.type = :type";
            $params['type'] = $type;
        }
        $req = config::getConnexion()->prepare($sql . " ORDER BY (c.dateFin IS NULL), c.dateFin, t.nom");
        $req->execute($params);
        return $req->fetchAll();
    }

    /* ---------------- Écriture ---------------- */

    // Ajoute ou remplace le contrat du terrain
    // ⚠️ Vérifier avant que le terrain appartient à l'utilisateur
    public function enregistrer(ContratFoncier $c): void
    {
        $params = [
            't' => $c->getIdTerrain(), 'type' => $c->getType(), 'p' => $c->getProprietaire(),
            'loyer' => $c->getLoyerAnnuel(), 'part' => $c->getPartProprietaire(),
            'debut' => $c->getDateDebut(), 'fin' => $c->getDateFin(),
        ];
        if ($this->getParTerrain($c->getIdTerrain())) {
            $sql = "UPDATE contrat_foncier SET type = :type, proprietaire = :p, loyerAnnuel = :loyer,
                           partProprietaire = :part, dateDebut = :debut, dateFin = :fin
                    WHERE idTerrain = :t";
        } else {
            $sql = "INSERT INTO contrat_foncier (idTerrain, type, proprietaire, loyerAnnuel, partProprietaire, dateDebut, dateFin)
                    VALUES (:t, :type, :p, :loyer, :part, :debut, :fin)";
        }
        config::getConnexion()->prepare($sql)->execute($params);
    }

    public function supprimer(int $idTerrain): bool
    {
        $req = config::getConnexion()->prepare("DELETE FROM contrat_foncier WHERE idTerrain = :t");
        $req->execute(['t' => $idTerrain]);
        return $req->rowCount() > 0;
    }

    /* ---------------- Contrôle de saisie ---------------- */

    public function valider(array $d): array
    {
        $erreurs = [];
        $type = $d['type'] ?? '';
        if (!isset(self::TYPES[$type])) {
            $erreurs['type'] = 'Veuillez choisir le type de contrat.';
            return $erreurs;
        }

        $debut = RecolteController::dateValide($d['dateDebut'] ?? '');
        if (!$debut) {
            $erreurs['dateDebut'] = 'Veuillez saisir une date de début valide.';
        } elseif ($debut < new DateTime('1950-01-01') || $debut > new DateTime('+1 year')) {
            $erreurs['dateDebut'] = 'La date de début n’est pas réaliste.';
        }

        if ($type === 'PROPRIETE') {
            return $erreurs;                    // ni propriétaire, ni loyer, ni date de fin
        }

        $proprietaire = trim($d['proprietaire'] ?? '');
        if ($proprietaire === '') {
            $erreurs['proprietaire'] = 'Le nom du propriétaire est obligatoire.';
        } elseif (mb_strlen($proprietaire) < 3 || mb_strlen($proprietaire) > 100) {
            $erreurs['proprietaire'] = 'Le nom du propriétaire doit contenir entre 3 et 100 caractères.';
        } elseif (!preg_match("/^[\p{L}\s'’\-.]+$/u", $proprietaire)) {
            $erreurs['proprietaire'] = 'Le nom du propriétaire ne doit contenir que des lettres, espaces, apostrophes ou tirets.';
        }

        $fin = RecolteController::dateValide($d['dateFin'] ?? '');
        if (!$fin) {
            $erreurs['dateFin'] = 'Veuillez saisir la date de fin du contrat.';
        } elseif ($debut && $fin <= $debut) {
            $erreurs['dateFin'] = 'La date de fin doit être après la date de début.';
        } elseif ($debut && $fin > (clone $debut)->modify('+99 years')) {
            $erreurs['dateFin'] = 'Un contrat ne peut pas dépasser 99 ans.';
        }

        if ($type === 'LOCATION') {
            $loyer = str_replace(',', '.', trim((string) ($d['loyerAnnuel'] ?? '')));
            if ($loyer === '') {
                $erreurs['loyerAnnuel'] = 'Le loyer annuel est obligatoire.';
            } elseif (!is_numeric($loyer) || (float) $loyer <= 0 || (float) $loyer > 1000000) {
                $erreurs['loyerAnnuel'] = 'Le loyer doit être un nombre entre 0 et 1 000 000 DT.';
            }
        } else {
            $part = str_replace(',', '.', trim((string) ($d['partProprietaire'] ?? '')));
            if ($part === '') {
                $erreurs['partProprietaire'] = 'La part du propriétaire est obligatoire.';
            } elseif (!is_numeric($part) || (float) $part < 1 || (float) $part > 99) {
                $erreurs['partProprietaire'] = 'La part du propriétaire doit être un pourcentage entre 1 et 99.';
            }
        }
        return $erreurs;
    }

    // Construit l'objet à partir du formulaire (ou d'une ligne de la base) : les champs inutiles sont vidés
    public function versObjet(array $d, ?int $idTerrain = null): ContratFoncier
    {
        $type = $d['type'];
        $nombre = fn($v) => ($v === null || trim((string) $v) === '') ? null : (float) str_replace(',', '.', (string) $v);
        return new ContratFoncier(
            $idTerrain ?? (int) $d['idTerrain'],
            $type,
            $type === 'PROPRIETE' ? null : trim($d['proprietaire'] ?? ''),
            $type === 'LOCATION' ? $nombre($d['loyerAnnuel'] ?? null) : null,
            in_array($type, ['KHAMMES', 'METAYAGE'], true) ? $nombre($d['partProprietaire'] ?? null) : null,
            $d['dateDebut'],
            $type === 'PROPRIETE' ? null : ($d['dateFin'] ?: null),
            isset($d['id']) ? (int) $d['id'] : null
        );
    }

    /* ---------------- Calcul ---------------- */

    /**
     * Ce que l'agriculteur doit au propriétaire (en DT)
     *  - LOCATION : loyer annuel × années écoulées depuis le début du contrat (jusqu'à aujourd'hui ou la fin)
     *  - KHAMMES / METAYAGE : part du propriétaire × chiffre d'affaires du terrain
     *  - PROPRIETE ou pas de contrat : 0
     */
    public static function chargeFonciere($contrat, float $revenuTerrain, ?string $aujourdhui = null): float
    {
        if (!$contrat) {
            return 0.0;
        }
        $aujourdhui ??= date('Y-m-d');
        switch ($contrat['type']) {
            case 'LOCATION':
                $fin = $contrat['dateFin'] && $contrat['dateFin'] < $aujourdhui ? $contrat['dateFin'] : $aujourdhui;
                $jours = max(0, (strtotime($fin) - strtotime($contrat['dateDebut'])) / 86400);
                return round((float) $contrat['loyerAnnuel'] * $jours / 365, 3);
            case 'KHAMMES':
            case 'METAYAGE':
                return round($revenuTerrain * (float) $contrat['partProprietaire'] / 100, 3);
            default:
                return 0.0;
        }
    }
}
