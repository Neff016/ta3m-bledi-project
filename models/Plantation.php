<?php
declare(strict_types=1);

class Plantation
{
    public int $id;
    public string $nom;
    public string $variete;
    public string $region;
    public float $surface;
    public string $date;
    public string $etat;

    public function __construct(array $row)
    {
        $this->id = (int) $row['id'];
        $this->nom = (string) $row['nom'];
        $this->variete = (string) $row['variete'];
        $this->region = (string) $row['region'];
        $this->surface = (float) $row['surface'];
        $this->date = (string) $row['date'];
        $this->etat = (string) $row['etat'];
    }

    /** @return Plantation[] */
    public static function all(?int $idUtilisateur = null): array
    {
        $db = Database::getConnection();
        if ($idUtilisateur !== null) {
            $statement = $db->prepare(
                'SELECT id, nom, variete, region, surface, date, etat
                 FROM plantation WHERE idUtilisateur = :idUtilisateur ORDER BY id DESC'
            );
            $statement->execute(['idUtilisateur' => $idUtilisateur]);
        } else {
            $statement = $db->query(
                'SELECT id, nom, variete, region, surface, date, etat
                 FROM plantation ORDER BY id DESC'
            );
        }

        return array_map(
            static fn(array $row): Plantation => new Plantation($row),
            $statement->fetchAll()
        );
    }

    public static function find(int $id, ?int $idUtilisateur = null): ?Plantation
    {
        $db = Database::getConnection();
        $sql = 'SELECT id, nom, variete, region, surface, date, etat
                FROM plantation WHERE id = :id';
        $parameters = ['id' => $id];
        if ($idUtilisateur !== null) {
            $sql .= ' AND idUtilisateur = :idUtilisateur';
            $parameters['idUtilisateur'] = $idUtilisateur;
        }
        $sql .= ' LIMIT 1';

        $statement = $db->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch();

        return $row ? new Plantation($row) : null;
    }

    public static function add(array $data, int $idUtilisateur): int
    {
        $db = Database::getConnection();
        $statement = $db->prepare(
            'INSERT INTO plantation (idUtilisateur, nom, variete, region, surface, date, etat)
             VALUES (:idUtilisateur, :nom, :variete, :region, :surface, :date, :etat)'
        );
        $statement->execute([
            'idUtilisateur' => $idUtilisateur,
            'nom' => $data['nom'],
            'variete' => $data['variete'],
            'region' => $data['region'],
            'surface' => (float) $data['surface'],
            'date' => $data['date'],
            'etat' => 'Semis',
        ]);

        return (int) $db->lastInsertId();
    }

    public static function varietes(): array
    {
        return [
            'Blé dur « Mahmoudi »',
            'Grenadier « Arbi »',
            'Abricotier local',
            'Blé Biskri',
            'Blé Hamira',
            'Boutebsi',
            'Courge traditionnelle',
            'Autre',
        ];
    }

    public static function regions(): array
    {
        return [
            'Tunis', 'Ariana', 'Ben Arous', 'Manouba', 'Nabeul', 'Zaghouan',
            'Bizerte', 'Béja', 'Jendouba', 'Le Kef', 'Siliana', 'Sousse',
            'Monastir', 'Mahdia', 'Sfax', 'Kairouan', 'Kasserine', 'Sidi Bouzid',
            'Gabès', 'Médenine', 'Tataouine', 'Gafsa', 'Tozeur', 'Kébili',
        ];
    }
}
