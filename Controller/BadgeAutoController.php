<?php
/*
 * Badges attribués AUTOMATIQUEMENT par le module Terrains et Récoltes
 * (sans attendre l'administrateur). Appelé après chaque action importante.
 */
require_once __DIR__ . '/../Config/config.php';

class BadgeAutoController
{
    /**
     * Vérifie les conditions et attribue les badges manquants.
     * Retourne la liste des badges gagnés maintenant : [['nom' => ..., 'points' => ...], ...]
     */
    public function verifier(int $idUtilisateur, bool $etiquetteImprimee = false): array
    {
        $pdo = config::getConnexion();
        $compter = function (string $sql) use ($pdo, $idUtilisateur): float {
            $req = $pdo->prepare($sql);
            $req->execute(['u' => $idUtilisateur]);
            return (float) $req->fetchColumn();
        };

        $conditions = [
            'Premier terrain'     => $compter("SELECT COUNT(*) FROM terrain WHERE idUtilisateur = :u") >= 1,
            'Première récolte'    => $compter("SELECT COUNT(*) FROM recolte r JOIN terrain t ON t.id = r.idTerrain
                                               WHERE t.idUtilisateur = :u") >= 1,
            'Une tonne récoltée'  => $compter("SELECT COALESCE(SUM(r.quantiteKg), 0) FROM recolte r JOIN terrain t ON t.id = r.idTerrain
                                               WHERE t.idUtilisateur = :u") >= 1000,
            'Comptable rigoureux' => $compter("SELECT COUNT(*) FROM depense d JOIN terrain t ON t.id = d.idTerrain
                                               WHERE t.idUtilisateur = :u") >= 10,
            'Producteur traçable' => $etiquetteImprimee,
        ];

        $gagnes = [];
        foreach ($conditions as $nom => $rempli) {
            if (!$rempli) {
                continue;
            }
            $badge = $pdo->prepare("SELECT id, points FROM badge WHERE nom = :n");
            $badge->execute(['n' => $nom]);
            $b = $badge->fetch();
            if (!$b) {
                continue;                       // badge pas encore créé (script SQL non importé)
            }
            $deja = $pdo->prepare("SELECT COUNT(*) FROM utilisateur_badge WHERE idUtilisateur = :u AND idBadge = :b");
            $deja->execute(['u' => $idUtilisateur, 'b' => $b['id']]);
            if ($deja->fetchColumn() > 0) {
                continue;
            }
            try {
                $pdo->prepare("INSERT INTO utilisateur_badge (idUtilisateur, idBadge, motif) VALUES (:u, :b, :m)")
                    ->execute(['u' => $idUtilisateur, 'b' => $b['id'], 'm' => 'Attribué automatiquement']);
                $gagnes[] = ['nom' => $nom, 'points' => (int) $b['points']];
            } catch (PDOException $e) {
                // déjà attribué entre-temps (contrainte UNIQUE) : rien à faire
            }
        }
        return $gagnes;
    }

    // Message à afficher : « Nouveau badge : Première récolte (+10 pts) »
    public static function message(array $gagnes): string
    {
        if (empty($gagnes)) {
            return '';
        }
        return ' 🏅 Nouveau badge : ' . implode(', ', array_map(fn($b) => "{$b['nom']} (+{$b['points']} pts)", $gagnes)) . ' !';
    }
}
