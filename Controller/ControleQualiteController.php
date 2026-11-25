<?php
/*
 * Controller : Contrôle qualité d'une récolte
 *  - classe Extra / I / II → coefficient sur le prix du marché (× 1,20 / × 1 / × 0,80)
 *  - les déchets ne sont pas vendus : quantité vendable = quantité × (1 − déchets %)
 *  - humidité (céréales) et °Brix (fruits) selon la culture
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Model/ControleQualite.php';
require_once __DIR__ . '/RecolteController.php';
require_once __DIR__ . '/CultureController.php';

class ControleQualiteController
{
    public const CLASSES      = ['EXTRA' => 'Extra', 'I' => 'Classe I', 'II' => 'Classe II'];
    public const COEFFICIENTS = ['EXTRA' => 1.20, 'I' => 1.00, 'II' => 0.80];
    public const HUMIDITE_MAX = 14.5;   // au-dessus : le grain risque de moisir au stockage

    /* ---------------- Lecture ---------------- */

    public function getParRecolte(int $idRecolte)
    {
        $req = config::getConnexion()->prepare("SELECT * FROM controle_qualite WHERE idRecolte = :r");
        $req->execute(['r' => $idRecolte]);
        return $req->fetch();
    }

    // Contrôles des récoltes d'un utilisateur, indexés par récolte
    public function parRecolteDeUtilisateur(int $idUtilisateur): array
    {
        $req = config::getConnexion()->prepare(
            "SELECT q.* FROM controle_qualite q JOIN recolte r ON r.id = q.idRecolte JOIN terrain t ON t.id = r.idTerrain
             WHERE t.idUtilisateur = :u");
        $req->execute(['u' => $idUtilisateur]);
        $resultat = [];
        foreach ($req->fetchAll() as $q) {
            $resultat[(int) $q['idRecolte']] = $q;
        }
        return $resultat;
    }

    /* ---------------- Règles ---------------- */

    // Quelle mesure demander selon la culture : 'humidite' (céréales), 'brix' (fruits) ou null
    public static function mesure(?array $culture): ?string
    {
        if (!$culture) {
            return null;
        }
        return match ($culture['categorie']) {
            'Céréales' => 'humidite',
            'Fruits'   => 'brix',
            default    => null,
        };
    }

    // Coefficient appliqué au chiffre d'affaires : classe × part vendable
    public static function coefficient($qualite): float
    {
        if (!$qualite) {
            return 1.0;
        }
        return (self::COEFFICIENTS[$qualite['classe']] ?? 1.0) * (1 - (float) $qualite['tauxDechets'] / 100);
    }

    // Texte court pour la page publique et l'étiquette : « Classe Extra · 2 % de déchets »
    public static function resume($qualite): array
    {
        if (!$qualite) {
            return [];
        }
        $morceaux = [[self::CLASSES[$qualite['classe']] ?? $qualite['classe'], []]];
        if ($qualite['calibre']) {
            $morceaux[] = ['Calibre {c}', ['c' => $qualite['calibre']]];
        }
        $morceaux[] = ['{n} % de déchets', ['n' => self::court($qualite['tauxDechets'])]];
        if ($qualite['humidite'] !== null) {
            $morceaux[] = ['Humidité {n} %', ['n' => self::court($qualite['humidite'])]];
        }
        if ($qualite['brix'] !== null) {
            $morceaux[] = ['{n} °Brix', ['n' => self::court($qualite['brix'])]];
        }
        return $morceaux;
    }

    // 2.50 → "2,5" ; 12.00 → "12"
    public static function court($n): string
    {
        return str_replace('.', ',', RecolteController::nombreCourt((float) $n));
    }

    /* ---------------- Écriture ---------------- */

    // ⚠️ Vérifier avant que la récolte appartient à l'utilisateur
    public function enregistrer(ControleQualite $q): void
    {
        $params = ['r' => $q->getIdRecolte(), 'c' => $q->getClasse(), 'cal' => $q->getCalibre(),
                   'd' => $q->getTauxDechets(), 'h' => $q->getHumidite(), 'b' => $q->getBrix(),
                   'date' => $q->getDateControle(), 'rem' => $q->getRemarque()];
        if ($this->getParRecolte($q->getIdRecolte())) {
            $sql = "UPDATE controle_qualite SET classe = :c, calibre = :cal, tauxDechets = :d, humidite = :h, brix = :b,
                           dateControle = :date, remarque = :rem WHERE idRecolte = :r";
        } else {
            $sql = "INSERT INTO controle_qualite (idRecolte, classe, calibre, tauxDechets, humidite, brix, dateControle, remarque)
                    VALUES (:r, :c, :cal, :d, :h, :b, :date, :rem)";
        }
        config::getConnexion()->prepare($sql)->execute($params);
    }

    public function supprimer(int $idRecolte): bool
    {
        $req = config::getConnexion()->prepare("DELETE FROM controle_qualite WHERE idRecolte = :r");
        $req->execute(['r' => $idRecolte]);
        return $req->rowCount() > 0;
    }

    /* ---------------- Contrôle de saisie ---------------- */

    // $recolte : la ligne de la récolte contrôlée (pour les dates) ; $mesure : 'humidite', 'brix' ou null
    public function valider(array $d, array $recolte, ?string $mesure): array
    {
        $erreurs = [];
        if (!isset(self::CLASSES[$d['classe'] ?? ''])) {
            $erreurs['classe'] = 'Veuillez choisir une classe.';
        }

        $calibre = trim($d['calibre'] ?? '');
        if ($calibre !== '' && !preg_match('/^[\p{L}0-9\s+\-\/.,]{1,30}$/u', $calibre)) {
            $erreurs['calibre'] = 'Le calibre contient au plus 30 lettres ou chiffres (ex : 57-67 mm, A, 3).';
        }

        $dechets = str_replace(',', '.', trim((string) ($d['tauxDechets'] ?? '')));
        if ($dechets === '') {
            $erreurs['tauxDechets'] = 'Le taux de déchets est obligatoire (0 si aucun).';
        } elseif (!is_numeric($dechets) || (float) $dechets < 0 || (float) $dechets > 100) {
            $erreurs['tauxDechets'] = 'Le taux de déchets doit être un pourcentage entre 0 et 100.';
        }

        if ($mesure === 'humidite') {
            $h = str_replace(',', '.', trim((string) ($d['humidite'] ?? '')));
            if ($h === '') {
                $erreurs['humidite'] = 'L’humidité du grain est obligatoire pour une céréale.';
            } elseif (!is_numeric($h) || (float) $h < 0 || (float) $h > 40) {
                $erreurs['humidite'] = 'L’humidité doit être un pourcentage entre 0 et 40.';
            }
        }
        if ($mesure === 'brix') {
            $b = str_replace(',', '.', trim((string) ($d['brix'] ?? '')));
            if ($b !== '' && (!is_numeric($b) || (float) $b < 0 || (float) $b > 40)) {
                $erreurs['brix'] = 'Le taux de sucre doit être entre 0 et 40 °Brix.';
            }
        }

        $date = RecolteController::dateValide($d['dateControle'] ?? '');
        if (!$date) {
            $erreurs['dateControle'] = 'Veuillez saisir la date du contrôle.';
        } elseif ($date > new DateTime('today')) {
            $erreurs['dateControle'] = 'La date du contrôle ne peut pas être dans le futur.';
        } elseif ($date < new DateTime($recolte['datePlantation'])) {
            $erreurs['dateControle'] = 'Le contrôle ne peut pas être avant la plantation.';
        }

        if (mb_strlen(trim($d['remarque'] ?? '')) > 255) {
            $erreurs['remarque'] = '255 caractères maximum.';
        }
        return $erreurs;
    }

    public function versObjet(array $d, int $idRecolte, ?string $mesure): ControleQualite
    {
        $nombre = fn($v) => trim((string) $v) === '' ? null : (float) str_replace(',', '.', (string) $v);
        return new ControleQualite(
            $idRecolte,
            $d['classe'],
            trim($d['calibre'] ?? '') ?: null,
            (float) $nombre($d['tauxDechets']),
            $mesure === 'humidite' ? $nombre($d['humidite'] ?? '') : null,
            $mesure === 'brix' ? $nombre($d['brix'] ?? '') : null,
            $d['dateControle'],
            trim($d['remarque'] ?? '') ?: null
        );
    }
}
