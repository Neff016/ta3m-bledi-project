<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/config.php';

final class PlantationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function varieties(): array
    {
        return $this->db->query('SELECT idVariete, nom FROM varietes ORDER BY nom')->fetchAll();
    }

    public function countVarieties(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM varietes')->fetchColumn();
    }

    private function selectSql(bool $includeOwner = false): string
    {
        $ownerColumn = $includeOwner
            ? ", CASE WHEN p.user_id IS NULL THEN 'Non attribuee' ELSE COALESCE(NULLIF(CONCAT_WS(' ', NULLIF(u.prenom, ''), NULLIF(u.nom, '')), ''), CONCAT('Utilisateur #', p.user_id)) END AS username"
            : '';

        return 'SELECT p.id AS idPlantation, p.name AS nom, COALESCE(p.address, p.region) AS adresse, '
            . 'p.latitude, p.longitude, p.surface, p.type_support AS typeSupport, '
            . 'p.planted_date AS dateCreation, p.user_id AS idUtilisateur, p.variety_id AS idVariete, '
            . 'COALESCE(v.nom, p.variety) AS varieteNom, p.status' . $ownerColumn
            . ' FROM plantations p LEFT JOIN varietes v ON v.idVariete = p.variety_id '
            . 'LEFT JOIN utilisateur u ON u.id = p.user_id';
    }

    public function all(): array
    {
        return $this->db->query($this->selectSql(true) . ' ORDER BY p.planted_date DESC, p.id DESC')->fetchAll();
    }

    public function allForUser(int $userId): array
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE p.user_id = :user AND p.owner_verified = 1 ORDER BY p.planted_date DESC, p.id DESC');
        $stmt->execute(['user' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare($this->selectSql(true) . ' WHERE p.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE p.id = :id AND p.user_id = :user AND p.owner_verified = 1 LIMIT 1');
        $stmt->execute(['id' => $id, 'user' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data, ?int $userId = null): bool
    {
        $parameters = $this->parameters($data, $userId);
        $parameters['varietyName'] = $this->varietyName($data['idVariete']);
        $stmt = $this->db->prepare(
            'INSERT INTO plantations (user_id, owner_verified, name, variety, region, surface, planted_date, address, latitude, longitude, type_support, variety_id) '
            . 'VALUES (:user, :verified, :nom, :varietyName, :region, :surface, :dateCreation, :adresse, :latitude, :longitude, :typeSupport, :variete)'
        );
        return $stmt->execute($parameters);
    }

    public function updateById(int $id, array $data, ?int $userId = null): bool
    {
        $parameters = $this->parameters($data, null);
        unset($parameters['user'], $parameters['verified']);
        $parameters['id'] = $id;
        $parameters['varietyName'] = $this->varietyName($data['idVariete']);
        $sql = 'UPDATE plantations SET name = :nom, variety = :varietyName, region = :region, surface = :surface, '
            . 'planted_date = :dateCreation, address = :adresse, latitude = :latitude, longitude = :longitude, '
            . 'type_support = :typeSupport, variety_id = :variete WHERE id = :id';
        if ($userId !== null) {
            $sql .= ' AND user_id = :owner AND owner_verified = 1';
            $parameters['owner'] = $userId;
        }
        return $this->db->prepare($sql)->execute($parameters);
    }

    public function deleteById(int $id, ?int $userId = null): bool
    {
        $sql = 'DELETE FROM plantations WHERE id = :id';
        $parameters = ['id' => $id];
        if ($userId !== null) {
            $sql .= ' AND user_id = :owner AND owner_verified = 1';
            $parameters['owner'] = $userId;
        }
        return $this->db->prepare($sql)->execute($parameters);
    }

    private function parameters(array $data, ?int $userId): array
    {
        return [
            'nom' => $data['nom'],
            'adresse' => $data['adresse'],
            'region' => $data['adresse'],
            'latitude' => $data['latitude'] === '' ? null : $data['latitude'],
            'longitude' => $data['longitude'] === '' ? null : $data['longitude'],
            'surface' => $data['surface'],
            'typeSupport' => $data['typeSupport'],
            'dateCreation' => $data['dateCreation'],
            'user' => $userId,
            'verified' => $userId === null ? 0 : 1,
            'variete' => $data['idVariete'] === '' ? null : (int) $data['idVariete'],
        ];
    }

    private function varietyName(string $varietyId): string
    {
        if ($varietyId === '') {
            return 'Non precisee';
        }
        $stmt = $this->db->prepare('SELECT nom FROM varietes WHERE idVariete = :id LIMIT 1');
        $stmt->execute(['id' => (int) $varietyId]);
        return (string) ($stmt->fetchColumn() ?: 'Non precisee');
    }
}