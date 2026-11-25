<?php
/*
 * Controller : Dépenses d'un terrain (pour calculer la rentabilité)
 */
require_once __DIR__ . '/../Config/config.php';
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

    // ⚠️ Vérifier avant que le terrain appartient à l'utilisateur
    public function ajouter(int $idTerrain, array $d): void
    {
        $sql = "INSERT INTO depense (idTerrain, categorie, montant, dateDepense, description)
                VALUES (:t, :c, :m, :d, :desc)";
        config::getConnexion()->prepare($sql)->execute([
            't' => $idTerrain, 'c' => $d['categorie'], 'm' => (float) str_replace(',', '.', $d['montant']),
            'd' => $d['dateDepense'], 'desc' => trim($d['description'] ?? '') ?: null,
        ]);
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
