<?php
/*
 * Controller : Certification bio d'un terrain
 * Règle principale : une récolte est « bio » seulement si son terrain était CERTIFIÉ
 * à la date de la récolte (pas pendant la conversion, pas après l'expiration).
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Model/CertificationBio.php';
require_once __DIR__ . '/RecolteController.php';

class CertificationBioController
{
    // Quelques organismes certificateurs (suggestions, la saisie reste libre)
    public const ORGANISMES = ['Ecocert', 'Bureau Veritas', 'CCPB', 'Suolo e Salute', 'Control Union', 'BCS Öko-Garantie'];
    public const DUREES = [24 => '24 mois (cultures annuelles)', 36 => '36 mois (arbres et cultures pérennes)'];
    public const STATUTS = [
        'AVANT'      => 'Pas encore en conversion',
        'CONVERSION' => 'En conversion',
        'CERTIFIE'   => 'Certifié bio',
        'EXPIRE'     => 'Certificat expiré',
    ];
    public const PRIME_BIO = 1.25;   // hypothèse : le bio se vend en moyenne 25 % plus cher

    /* ---------------- Lecture ---------------- */

    public function getParTerrain(int $idTerrain)
    {
        $req = config::getConnexion()->prepare("SELECT * FROM certification_bio WHERE idTerrain = :t");
        $req->execute(['t' => $idTerrain]);
        return $req->fetch();
    }

    public function parTerrainDeUtilisateur(int $idUtilisateur): array
    {
        $req = config::getConnexion()->prepare(
            "SELECT b.* FROM certification_bio b JOIN terrain t ON t.id = b.idTerrain WHERE t.idUtilisateur = :u");
        $req->execute(['u' => $idUtilisateur]);
        $resultat = [];
        foreach ($req->fetchAll() as $b) {
            $resultat[(int) $b['idTerrain']] = $b;
        }
        return $resultat;
    }

    public function listAll(): array
    {
        $sql = "SELECT b.*, t.nom AS terrainNom, t.localisation, CONCAT(u.prenom, ' ', u.nom) AS agriculteur
                FROM certification_bio b JOIN terrain t ON t.id = b.idTerrain JOIN utilisateur u ON u.id = t.idUtilisateur
                WHERE u.statut <> 'SUPPRIME' ORDER BY t.nom";
        return config::getConnexion()->query($sql)->fetchAll();
    }

    /* ---------------- Règles ---------------- */

    public static function versObjet(array $b): CertificationBio
    {
        return new CertificationBio((int) $b['idTerrain'], $b['organisme'], $b['numeroCertificat'] ?: null,
                                    $b['dateDebutConversion'], (int) $b['dureeConversionMois'],
                                    $b['dateExpiration'] ?: null, isset($b['id']) ? (int) $b['id'] : null);
    }

    // Statut du terrain à une date (null = pas de certification)
    public static function statut($certification, ?string $date = null): ?string
    {
        return $certification ? self::versObjet($certification)->statutA($date ?? date('Y-m-d')) : null;
    }

    // Statut d'une RÉCOLTE : 'CERTIFIE' (bio), 'CONVERSION' (« en conversion vers l'AB ») ou null
    public static function statutRecolte($certification, string $dateRecolte): ?string
    {
        $s = self::statut($certification, $dateRecolte);
        return in_array($s, ['CERTIFIE', 'CONVERSION'], true) ? $s : null;
    }

    // Messages pour le tableau de bord : certification proche, certificat à renouveler
    public function alertes(int $idUtilisateur): array
    {
        $req = config::getConnexion()->prepare(
            "SELECT b.*, t.nom AS terrainNom FROM certification_bio b JOIN terrain t ON t.id = b.idTerrain WHERE t.idUtilisateur = :u");
        $req->execute(['u' => $idUtilisateur]);
        $aujourdhui = date('Y-m-d');
        $alertes = [];
        foreach ($req->fetchAll() as $b) {
            $cert = self::versObjet($b);
            $dateCert = $cert->getDateCertification();
            $statut = $cert->statutA($aujourdhui);
            if ($statut === 'CONVERSION' && $dateCert <= date('Y-m-d', strtotime('+60 days'))) {
                $alertes[] = ['success', 'bi-flower1', 'Le terrain {t} sera certifié bio le {d} : fin de la conversion.',
                              ['t' => $b['terrainNom'], 'd' => date('d/m/Y', strtotime($dateCert))]];
            } elseif ($statut === 'CERTIFIE' && $b['dateExpiration'] && $b['dateExpiration'] <= date('Y-m-d', strtotime('+30 days'))) {
                $alertes[] = ['warning', 'bi-patch-exclamation', 'Le certificat bio du terrain {t} expire le {d} : pensez au contrôle annuel.',
                              ['t' => $b['terrainNom'], 'd' => date('d/m/Y', strtotime($b['dateExpiration']))]];
            } elseif ($statut === 'EXPIRE') {
                $alertes[] = ['danger', 'bi-patch-exclamation', 'Le certificat bio du terrain {t} a expiré le {d} : vos récoltes ne sont plus bio.',
                              ['t' => $b['terrainNom'], 'd' => date('d/m/Y', strtotime($b['dateExpiration']))]];
            }
        }
        return $alertes;
    }

    /* ---------------- Écriture ---------------- */

    // ⚠️ Vérifier avant que le terrain appartient à l'utilisateur
    public function enregistrer(CertificationBio $b): void
    {
        $params = ['t' => $b->getIdTerrain(), 'o' => $b->getOrganisme(), 'n' => $b->getNumeroCertificat(),
                   'd' => $b->getDateDebutConversion(), 'm' => $b->getDureeConversionMois(), 'e' => $b->getDateExpiration()];
        if ($this->getParTerrain($b->getIdTerrain())) {
            $sql = "UPDATE certification_bio SET organisme = :o, numeroCertificat = :n, dateDebutConversion = :d,
                           dureeConversionMois = :m, dateExpiration = :e WHERE idTerrain = :t";
        } else {
            $sql = "INSERT INTO certification_bio (idTerrain, organisme, numeroCertificat, dateDebutConversion, dureeConversionMois, dateExpiration)
                    VALUES (:t, :o, :n, :d, :m, :e)";
        }
        config::getConnexion()->prepare($sql)->execute($params);
    }

    public function supprimer(int $idTerrain): bool
    {
        $req = config::getConnexion()->prepare("DELETE FROM certification_bio WHERE idTerrain = :t");
        $req->execute(['t' => $idTerrain]);
        return $req->rowCount() > 0;
    }

    /* ---------------- Contrôle de saisie ---------------- */

    public function valider(array $d): array
    {
        $erreurs = [];
        $organisme = trim($d['organisme'] ?? '');
        if ($organisme === '') {
            $erreurs['organisme'] = 'L’organisme certificateur est obligatoire.';
        } elseif (mb_strlen($organisme) < 2 || mb_strlen($organisme) > 100) {
            $erreurs['organisme'] = 'L’organisme doit contenir entre 2 et 100 caractères.';
        } elseif (!preg_match("/^[\p{L}0-9\s'’\-.&]+$/u", $organisme)) {
            $erreurs['organisme'] = 'L’organisme ne doit contenir que des lettres, chiffres, espaces, points ou tirets.';
        }

        $numero = trim($d['numeroCertificat'] ?? '');
        if ($numero !== '' && !preg_match('/^[A-Za-z0-9\-\/ ]{3,50}$/', $numero)) {
            $erreurs['numeroCertificat'] = 'Le numéro de certificat contient 3 à 50 lettres, chiffres, tirets ou barres obliques.';
        }

        $debut = RecolteController::dateValide($d['dateDebutConversion'] ?? '');
        if (!$debut) {
            $erreurs['dateDebutConversion'] = 'Veuillez saisir la date de début de conversion.';
        } elseif ($debut > new DateTime('today')) {
            $erreurs['dateDebutConversion'] = 'La conversion ne peut pas commencer dans le futur.';
        } elseif ($debut < new DateTime('1990-01-01')) {
            $erreurs['dateDebutConversion'] = 'La date de début de conversion n’est pas réaliste.';
        }

        $duree = (int) ($d['dureeConversionMois'] ?? 0);
        if (!isset(self::DUREES[$duree])) {
            $erreurs['dureeConversionMois'] = 'Veuillez choisir la durée de conversion.';
        }

        if (trim($d['dateExpiration'] ?? '') !== '') {
            $exp = RecolteController::dateValide($d['dateExpiration']);
            if (!$exp) {
                $erreurs['dateExpiration'] = 'Date d’expiration invalide.';
            } elseif ($debut && isset(self::DUREES[$duree]) && $exp <= (clone $debut)->modify("+$duree months")) {
                $erreurs['dateExpiration'] = 'Le certificat doit expirer après la fin de la conversion.';
            }
        }
        return $erreurs;
    }
}
