<?php
/*
 * Controller : Dépenses d'un terrain (pour calculer la rentabilité)
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Model/Depense.php';
require_once __DIR__ . '/RecolteController.php';

class DepenseController
{
    public const CATEGORIES = ['Semences', 'Engrais', 'Eau', "Main-d'oeuvre", 'Traitement', 'Matériel', 'Autre'];

    public function listParTerrain(int $idTerrain): array
    {
        $req = config::getConnexion()->prepare("SELECT * FROM depense WHERE idTerrain = :t ORDER BY dateDepense DESC, id DESC");
        $req->execute(['t' => $idTerrain]);
        return $req->fetchAll();
    }

    // Une dépense SEULEMENT si son terrain appartient à l'utilisateur (sinon false)
    public function getDepenseOfUser(int $id, int $idUtilisateur)
    {
        $req = config::getConnexion()->prepare(
            "SELECT d.* FROM depense d JOIN terrain t ON t.id = d.idTerrain WHERE d.id = :id AND t.idUtilisateur = :u");
        $req->execute(['id' => $id, 'u' => $idUtilisateur]);
        return $req->fetch();
    }

    // ⚠️ Vérifier avant que le terrain appartient à l'utilisateur
    public function ajouter(Depense $d): int
    {
        $sql = "INSERT INTO depense (idTerrain, categorie, montant, dateDepense, description)
                VALUES (:t, :c, :m, :d, :desc)";
        config::getConnexion()->prepare($sql)->execute($this->parametres($d));
        return (int) config::getConnexion()->lastInsertId();
    }

    // ⚠️ Vérifier avant (getDepenseOfUser) que la dépense appartient à l'utilisateur
    public function modifier(Depense $d): void
    {
        $sql = "UPDATE depense SET categorie = :c, montant = :m, dateDepense = :d, description = :desc
                WHERE id = :id AND idTerrain = :t";
        config::getConnexion()->prepare($sql)->execute($this->parametres($d) + ['id' => $d->getId()]);
    }

    private function parametres(Depense $d): array
    {
        return ['t' => $d->getIdTerrain(), 'c' => $d->getCategorie(), 'm' => $d->getMontant(),
                'd' => $d->getDateDepense(), 'desc' => $d->getDescription()];
    }

    // Construit l'objet Depense à partir du formulaire (déjà validé)
    public static function versObjet(array $d, int $idTerrain, ?int $id = null): Depense
    {
        return new Depense($idTerrain, $d['categorie'], (float) str_replace(',', '.', trim($d['montant'])),
                           $d['dateDepense'], trim($d['description'] ?? '') ?: null, $id);
    }

    // Supprime seulement si la dépense est sur un terrain de l'utilisateur
    public function supprimer(int $id, int $idUtilisateur): bool
    {
        $req = config::getConnexion()->prepare(
            "DELETE FROM depense WHERE id = :id AND idTerrain IN (SELECT id FROM terrain WHERE idUtilisateur = :u)");
        $req->execute(['id' => $id, 'u' => $idUtilisateur]);
        return $req->rowCount() > 0;
    }

    public function valider(array $d): array
    {
        $e = [];
        if (!in_array($d['categorie'] ?? '', self::CATEGORIES, true)) {
            $e['categorie'] = 'Choisissez une catégorie.';
        }
        $m = str_replace(',', '.', trim((string) ($d['montant'] ?? '')));
        if ($m === '') {
            $e['montant'] = 'Le montant est obligatoire.';
        } elseif (!is_numeric($m) || (float) $m <= 0 || (float) $m > 10000000) {
            $e['montant'] = 'Le montant doit être un nombre entre 0 et 10 000 000 DT.';
        }
        $date = RecolteController::dateValide($d['dateDepense'] ?? '');
        if (!$date) {
            $e['dateDepense'] = 'Date invalide.';
        } elseif ($date > new DateTime('today')) {
            $e['dateDepense'] = 'La date ne peut pas être dans le futur.';
        }
        if (mb_strlen(trim($d['description'] ?? '')) > 150) {
            $e['description'] = '150 caractères maximum.';
        }
        return $e;
    }
}
