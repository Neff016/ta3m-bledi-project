<?php
declare(strict_types=1);

class Suivi
{
    public int $id;
    public int $plantationId;
    public string $date;
    public string $etat;
    public string $arrosage;
    public string $observation;

    public function __construct(array $row)
    {
        $this->id = (int) $row['id'];
        $this->plantationId = (int) $row['plantationId'];
        $this->date = (string) $row['date'];
        $this->etat = (string) $row['etat'];
        $this->arrosage = (string) $row['arrosage'];
        $this->observation = (string) $row['observation'];
    }

    /** @return Suivi[] */
    public static function all(?int $idUtilisateur = null): array
    {
        $db = Database::getConnection();
        $sql = 'SELECT s.id, s.plantationId, s.date, s.etat, s.arrosage, s.observation
                FROM suivi s';
        $parameters = [];
        if ($idUtilisateur !== null) {
            $sql .= ' INNER JOIN plantation p ON p.id = s.plantationId
                      WHERE p.idUtilisateur = :idUtilisateur';
            $parameters['idUtilisateur'] = $idUtilisateur;
        }
        $sql .= ' ORDER BY s.id DESC';

        $statement = $db->prepare($sql);
        $statement->execute($parameters);

        return array_map(
            static fn(array $row): Suivi => new Suivi($row),
            $statement->fetchAll()
        );
    }

    /** @return Suivi[] */
    public static function forPlantation(int $id, ?int $idUtilisateur = null): array
    {
        $db = Database::getConnection();
        $sql = 'SELECT s.id, s.plantationId, s.date, s.etat, s.arrosage, s.observation
                FROM suivi s INNER JOIN plantation p ON p.id = s.plantationId
                WHERE s.plantationId = :plantationId';
        $parameters = ['plantationId' => $id];
        if ($idUtilisateur !== null) {
            $sql .= ' AND p.idUtilisateur = :idUtilisateur';
            $parameters['idUtilisateur'] = $idUtilisateur;
        }
        $sql .= ' ORDER BY s.id DESC';
        $statement = $db->prepare($sql);
        $statement->execute($parameters);

        return array_map(
            static fn(array $row): Suivi => new Suivi($row),
            $statement->fetchAll()
        );
    }

    public static function add(array $data, int $idUtilisateur): void
    {
        $plantation = Plantation::find((int) $data['plantation'], $idUtilisateur);
        if (!$plantation) {
            throw new InvalidArgumentException('Plantation introuvable ou non autorisée.');
        }

        $db = Database::getConnection();
        $statement = $db->prepare(
            'INSERT INTO suivi (plantationId, date, etat, arrosage, observation)
             VALUES (:plantationId, :date, :etat, :arrosage, :observation)'
        );
        $statement->execute([
            'plantationId' => $plantation->id,
            'date' => $data['date'],
            'etat' => $data['etat'],
            'arrosage' => $data['arrosage'],
            'observation' => $data['observation'],
        ]);
    }

    public static function etats(): array
    {
        return ['Bonne santé', 'Stress hydrique', 'Maladie suspectée'];
    }

    public static function arrosages(): array
    {
        return ['Aucun', 'Léger', 'Abondant'];
    }
}
