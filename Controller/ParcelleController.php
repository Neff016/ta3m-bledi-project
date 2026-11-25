<?php
/*
 * Controller : Parcelles d'un terrain et plan de rotation sur 4 ans
 *
 * Rotation conseillée (cycle de 4 ans) :
 *   Céréale → Légumineuse → Légume → Jachère (ou engrais vert) → Céréale…
 *  - la légumineuse (fève, pois chiche) remet de l'azote dans le sol après la céréale ;
 *  - le légume profite de cet azote ;
 *  - la jachère repose le sol et casse le cycle des maladies.
 * Les arbres et cultures pérennes (olivier, agrumes, luzerne…) ne tournent pas.
 */
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Model/Parcelle.php';
require_once __DIR__ . '/TerrainController.php';
require_once __DIR__ . '/CultureController.php';
require_once __DIR__ . '/RecolteController.php';

class ParcelleController
{
    public const FAMILLES = [
        'CEREALE'     => ['Céréale', 'bi-flower3'],
        'LEGUMINEUSE' => ['Légumineuse', 'bi-circle-square'],
        'LEGUME'      => ['Légume', 'bi-basket'],
        'JACHERE'     => ['Jachère', 'bi-moon-stars'],
        'PERENNE'     => ['Culture pérenne', 'bi-tree'],
    ];
    public const CYCLE = ['CEREALE', 'LEGUMINEUSE', 'LEGUME', 'JACHERE'];
    public const ANNEES_PLAN = 4;

    private CultureController $cultureC;

    public function __construct()
    {
        $this->cultureC = new CultureController();
    }

    /* ---------------- Lecture ---------------- */

    public function listParTerrain(int $idTerrain): array
    {
        $req = config::getConnexion()->prepare(
            "SELECT p.*, (SELECT COUNT(*) FROM recolte r WHERE r.idParcelle = p.id) AS nbRecoltes
             FROM parcelle p WHERE p.idTerrain = :t ORDER BY p.nom");
        $req->execute(['t' => $idTerrain]);
        return $req->fetchAll();
    }

    // Toutes les parcelles d'un utilisateur (formulaire récolte : liste filtrée par terrain en JS)
    public function listParUtilisateur(int $idUtilisateur): array
    {
        $req = config::getConnexion()->prepare(
            "SELECT p.id, p.nom, p.surface, p.idTerrain FROM parcelle p JOIN terrain t ON t.id = p.idTerrain
             WHERE t.idUtilisateur = :u ORDER BY p.nom");
        $req->execute(['u' => $idUtilisateur]);
        return $req->fetchAll();
    }

    // Une parcelle seulement si son terrain appartient à l'utilisateur
    public function getParcelleOfUser(int $id, int $idUtilisateur)
    {
        $req = config::getConnexion()->prepare(
            "SELECT p.* FROM parcelle p JOIN terrain t ON t.id = p.idTerrain WHERE p.id = :id AND t.idUtilisateur = :u");
        $req->execute(['id' => $id, 'u' => $idUtilisateur]);
        return $req->fetch();
    }

    public function surfaceUtilisee(int $idTerrain, ?int $exceptId = null): float
    {
        $req = config::getConnexion()->prepare("SELECT COALESCE(SUM(surface), 0) FROM parcelle WHERE idTerrain = :t AND id <> :ex");
        $req->execute(['t' => $idTerrain, 'ex' => $exceptId ?? 0]);
        return (float) $req->fetchColumn();
    }

    /* ---------------- Écriture ---------------- */

    public function ajouter(Parcelle $p): int
    {
        $req = config::getConnexion()->prepare("INSERT INTO parcelle (idTerrain, nom, surface, polygone) VALUES (:t, :n, :s, :p)");
        $req->execute(['t' => $p->getIdTerrain(), 'n' => $p->getNom(), 's' => $p->getSurface(), 'p' => $p->getPolygone()]);
        return (int) config::getConnexion()->lastInsertId();
    }

    // ⚠️ Vérifier avant (getParcelleOfUser) que la parcelle appartient à l'utilisateur
    public function modifier(Parcelle $p): void
    {
        $req = config::getConnexion()->prepare("UPDATE parcelle SET nom = :n, surface = :s, polygone = :p WHERE id = :id");
        $req->execute(['n' => $p->getNom(), 's' => $p->getSurface(), 'p' => $p->getPolygone(), 'id' => $p->getId()]);
    }

    // Les récoltes de la parcelle restent sur le terrain (idParcelle devient vide : ON DELETE SET NULL)
    public function supprimer(int $id, int $idUtilisateur): bool
    {
        $req = config::getConnexion()->prepare(
            "DELETE FROM parcelle WHERE id = :id AND idTerrain IN (SELECT id FROM terrain WHERE idUtilisateur = :u)");
        $req->execute(['id' => $id, 'u' => $idUtilisateur]);
        return $req->rowCount() > 0;
    }

    /* ---------------- Contrôle de saisie ---------------- */

    public function valider(array $d, array $terrain, ?int $exceptId = null): array
    {
        $erreurs = [];
        $nom = trim($d['nom'] ?? '');
        if ($nom === '') {
            $erreurs['nom'] = 'Le nom de la parcelle est obligatoire.';
        } elseif (mb_strlen($nom) < 2 || mb_strlen($nom) > 50) {
            $erreurs['nom'] = 'Le nom doit contenir entre 2 et 50 caractères.';
        } elseif (!preg_match("/^[\p{L}0-9\s'’\-]+$/u", $nom)) {
            $erreurs['nom'] = 'Le nom ne doit contenir que des lettres, chiffres, espaces, apostrophes ou tirets.';
        } else {
            $req = config::getConnexion()->prepare("SELECT COUNT(*) FROM parcelle WHERE idTerrain = :t AND LOWER(nom) = LOWER(:n) AND id <> :ex");
            $req->execute(['t' => $terrain['id'], 'n' => $nom, 'ex' => $exceptId ?? 0]);
            if ($req->fetchColumn() > 0) {
                $erreurs['nom'] = 'Une parcelle porte déjà ce nom sur ce terrain.';
            }
        }

        // Contour dessiné (facultatif) : la surface est alors recalculée par le serveur
        $polygone = trim((string) ($d['polygone'] ?? ''));
        $points = $polygone !== '' ? TerrainController::lirePolygone($polygone) : null;
        if ($polygone !== '' && $points === null) {
            $erreurs['polygone'] = 'Le contour dessiné est invalide : placez au moins 3 coins en Tunisie.';
        }

        $surface = $points ? (string) TerrainController::surfaceHectares($points) : str_replace(',', '.', trim($d['surface'] ?? ''));
        if ($surface === '') {
            $erreurs['surface'] = 'La surface est obligatoire.';
        } elseif (!is_numeric($surface) || (float) $surface <= 0) {
            $erreurs['surface'] = 'La surface doit être un nombre supérieur à 0.';
        } else {
            $total = $this->surfaceUtilisee((int) $terrain['id'], $exceptId) + (float) $surface;
            if ($total > (float) $terrain['superficie'] * 1.01) {      // 1 % de tolérance (arrondis de la carte)
                $erreurs['surface'] = 'Les parcelles feraient {total} ha, plus que le terrain ({max} ha).';
                $erreurs['surfaceValeurs'] = ['total' => CultureController::nombre($total, 2), 'max' => CultureController::nombre((float) $terrain['superficie'], 2)];
            }
        }
        return $erreurs;
    }

    public function versObjet(array $d, int $idTerrain, ?int $id = null): Parcelle
    {
        $points = trim((string) ($d['polygone'] ?? '')) !== '' ? TerrainController::lirePolygone($d['polygone']) : null;
        $surface = $points ? TerrainController::surfaceHectares($points) : (float) str_replace(',', '.', trim($d['surface']));
        return new Parcelle($idTerrain, trim($d['nom']), $surface, $points ? json_encode($points) : null, $id);
    }

    /* ---------------- Rotation des cultures ---------------- */

    // Famille d'une culture (null si elle n'est pas dans le catalogue)
    public function famille(string $nomCulture): ?string
    {
        $c = $this->cultureC->trouver($nomCulture);
        if (!$c) {
            return null;
        }
        if (in_array($c['nom'], ['Fève', 'Pois chiche'], true)) {
            return 'LEGUMINEUSE';
        }
        if ($c['categorie'] === 'Céréales') {
            return 'CEREALE';
        }
        if ((int) $c['dureeJours'] >= 200) {         // arbres, luzerne, romarin : restent en place plusieurs années
            return 'PERENNE';
        }
        return 'LEGUME';
    }

    // Famille qui suit dans le cycle
    public static function suivante(?string $famille): string
    {
        $i = array_search($famille, self::CYCLE, true);
        return $i === false ? 'CEREALE' : self::CYCLE[($i + 1) % count(self::CYCLE)];
    }

    /**
     * Culture conseillée d'une famille pour ce terrain : sol adapté (+2), région adaptée (+2),
     * peu d'eau en région sèche (+1). On évite de reprendre la même culture que la dernière fois.
     */
    public function cultureConseillee(string $famille, array $terrain, ?string $eviter = null): ?string
    {
        if ($famille === 'JACHERE' || $famille === 'PERENNE') {
            return null;
        }
        $meilleure = null;
        $meilleurScore = -1;
        foreach ($this->cultureC->listCultures() as $c) {
            if ($this->famille($c['nom']) !== $famille) {
                continue;
            }
            $score = 0;
            $score += in_array($terrain['typeSol'], explode(',', $c['sols']), true) ? 2 : 0;
            $score += $c['regions'] === '*' ? 1 : (in_array($terrain['localisation'], explode(',', $c['regions']), true) ? 2 : 0);
            $score += in_array($terrain['localisation'], CultureController::REGIONS_ARIDES, true) && $c['besoinEau'] === 'FAIBLE' ? 1 : 0;
            $score -= ($eviter !== null && $c['nom'] === $eviter) ? 3 : 0;
            if ($score > $meilleurScore) {
                $meilleurScore = $score;
                $meilleure = $c['nom'];
            }
        }
        return $meilleure;
    }

    /**
     * Plan de rotation : pour chaque parcelle, 3 années d'historique (N-2, N-1, N) puis 4 années de plan (N+1 … N+4).
     * $recoltes : récoltes du terrain (avec idParcelle). Sans parcelle, une ligne « Tout le terrain ».
     * Retour : ['annees' => [...], 'lignes' => [['nom' => …, 'cases' => [...], 'conseil' => […]]]]
     */
    public function planRotation(array $terrain, array $parcelles, array $recoltes, ?int $anneeCourante = null): array
    {
        $n = $anneeCourante ?? (int) date('Y');
        $annees = range($n - 2, $n + self::ANNEES_PLAN);
        $groupes = [];
        if (empty($parcelles)) {
            $groupes[] = ['nom' => 'Tout le terrain', 'id' => null, 'recoltes' => $recoltes];
        } else {
            foreach ($parcelles as $p) {
                $groupes[] = ['nom' => $p['nom'], 'id' => (int) $p['id'],
                              'recoltes' => array_filter($recoltes, fn($r) => (int) ($r['idParcelle'] ?? 0) === (int) $p['id'])];
            }
        }

        $lignes = [];
        foreach ($groupes as $g) {
            // Dernière récolte de chaque année (ordre chronologique)
            usort($g['recoltes'], fn($a, $b) => strcmp($a['dateRecolte'], $b['dateRecolte']));
            $parAnnee = [];
            $derniereFamille = null;
            $derniereCulture = null;
            $dernierParFamille = [];                                     // pour ne pas reproposer la même culture
            foreach ($g['recoltes'] as $r) {
                $parAnnee[(int) substr($r['dateRecolte'], 0, 4)] = $r;
                $f = $this->famille($r['culture']);
                if ($f !== null) {
                    $derniereFamille = $f;
                    $dernierParFamille[$f] = $this->cultureC->trouver($r['culture'])['nom'];
                    $derniereCulture = $r['culture'];
                }
            }

            $cases = [];
            $precedente = null;
            foreach (array_slice($annees, 0, 3) as $a) {                 // historique
                if (isset($parAnnee[$a])) {
                    $f = $this->famille($parAnnee[$a]['culture']);
                    $cases[] = ['annee' => $a, 'type' => 'historique', 'famille' => $f, 'culture' => $parAnnee[$a]['culture'],
                                'alerte' => $f !== null && $f !== 'PERENNE' && $f === $precedente];
                    $precedente = $f;
                } else {
                    $cases[] = ['annee' => $a, 'type' => 'vide', 'famille' => null, 'culture' => null, 'alerte' => false];
                    $precedente = null;
                }
            }

            $famille = $derniereFamille;
            foreach (array_slice($annees, 3) as $a) {                    // plan
                if ($famille === 'PERENNE') {
                    $cases[] = ['annee' => $a, 'type' => 'plan', 'famille' => 'PERENNE', 'culture' => $derniereCulture, 'alerte' => false];
                    continue;
                }
                $famille = self::suivante($famille);
                $culture = $this->cultureConseillee($famille, $terrain, $dernierParFamille[$famille] ?? null);
                $dernierParFamille[$famille] = $culture;
                $cases[] = ['annee' => $a, 'type' => 'plan', 'famille' => $famille, 'culture' => $culture, 'alerte' => false];
            }

            $conseil = $derniereFamille === null
                ? ['Pas encore de récolte : on commence le cycle par une céréale.', []]
                : ($derniereFamille === 'PERENNE'
                    ? ['{c} est une culture pérenne : elle reste en place, pas de rotation.', ['c' => $derniereCulture]]
                    : ['Dernière culture : {c} ({f}). Le plan continue le cycle.', ['c' => $derniereCulture, 'f' => self::FAMILLES[$derniereFamille][0]]]);
            $lignes[] = ['nom' => $g['nom'], 'id' => $g['id'], 'cases' => $cases, 'conseil' => $conseil];
        }
        return ['annees' => $annees, 'courante' => $n, 'lignes' => $lignes];
    }
}
