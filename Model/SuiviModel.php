<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/config.php';

final class SuiviModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    private function selectSql(): string
    {
        return 'SELECT s.id AS idSuivi, s.plantation_id AS idPlantation, s.followup_date AS dateSuivi, '
            . 's.photo, s.etat, s.arrosage, s.observation, s.diagnosticIA, p.name AS plantationNom, '
            . "CASE WHEN p.user_id IS NULL THEN 'Non attribuee' ELSE COALESCE(NULLIF(CONCAT_WS(' ', NULLIF(u.prenom, ''), NULLIF(u.nom, '')), ''), CONCAT('Utilisateur #', p.user_id)) END AS username "
            . 'FROM suivis s JOIN plantations p ON p.id = s.plantation_id LEFT JOIN utilisateur u ON u.id = p.user_id';
    }

    public function forPlantation(int $plantationId, ?int $userId = null): array
    {
        $sql = $this->selectSql() . ' WHERE s.plantation_id = :plantation';
        $parameters = ['plantation' => $plantationId];
        if ($userId !== null) {
            $sql .= ' AND p.user_id = :user AND p.owner_verified = 1';
            $parameters['user'] = $userId;
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY s.followup_date DESC, s.id DESC');
        $stmt->execute($parameters);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query(
            $this->selectSql() . " ORDER BY s.followup_date DESC, s.id DESC"
        )->fetchAll();
    }

    public function allForUser(int $userId): array
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE p.user_id = :user AND p.owner_verified = 1 ORDER BY s.followup_date DESC, s.id DESC');
        $stmt->execute(['user' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE s.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE s.id = :id AND p.user_id = :user AND p.owner_verified = 1 LIMIT 1');
        $stmt->execute(['id' => $id, 'user' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data, int $plantationId, ?int $userId = null): bool
    {
        $sql = 'INSERT INTO suivis (plantation_id, user_id, followup_date, stage, condition_state, observation, photo, etat, arrosage, diagnosticIA) '
            . 'SELECT p.id, p.user_id, :dateSuivi, p.status, :conditionState, :observation, :photo, :etat, :arrosage, :diagnosticIA '
            . 'FROM plantations p WHERE p.id = :plantation';
        $parameters = $this->parameters($data, $plantationId);
        if ($userId !== null) {
            $sql .= ' AND p.user_id = :owner AND p.owner_verified = 1';
            $parameters['owner'] = $userId;
        }
        $parameters['conditionState'] = $this->conditionState($data['etat']);
        return $this->db->prepare($sql)->execute($parameters);
    }

    public function updateById(int $id, array $data, int $plantationId, ?int $userId = null): bool
    {
        $sql = 'UPDATE suivis SET followup_date = :dateSuivi, photo = :photo, etat = :etat, '
            . 'condition_state = :conditionState, arrosage = :arrosage, observation = :observation, '
            . 'diagnosticIA = :diagnosticIA WHERE id = :id AND plantation_id = :plantation';
        $parameters = $this->parameters($data, $plantationId);
        $parameters['id'] = $id;
        $parameters['conditionState'] = $this->conditionState($data['etat']);
        if ($userId !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM plantations p WHERE p.id = suivis.plantation_id AND p.user_id = :owner AND p.owner_verified = 1)';
            $parameters['owner'] = $userId;
        }
        return $this->db->prepare($sql)->execute($parameters);
    }

    public function deleteById(int $id, ?int $userId = null): bool
    {
        if ($userId === null) {
            return $this->db->prepare('DELETE FROM suivis WHERE id = :id')->execute(['id' => $id]);
        }
        $stmt = $this->db->prepare(
            'DELETE s FROM suivis s JOIN plantations p ON p.id = s.plantation_id WHERE s.id = :id AND p.user_id = :user AND p.owner_verified = 1'
        );
        return $stmt->execute(['id' => $id, 'user' => $userId]);
    }

    private function parameters(array $data, int $plantationId): array
    {
        return [
            'dateSuivi' => $data['dateSuivi'],
            'photo' => $data['photo'],
            'etat' => $data['etat'],
            'arrosage' => $data['arrosage'],
            'observation' => $data['observation'],
            'diagnosticIA' => $data['diagnosticIA'],
            'plantation' => $plantationId,
        ];
    }

    private function conditionState(string $etat): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower($etat, 'UTF-8') : strtolower($etat);
        if (str_contains($value, 'surveill') || str_contains($value, 'maladie') || str_contains($value, 'attention')) {
            return 'À surveiller';
        }
        if (str_contains($value, 'moyen') || str_contains($value, 'stress') || str_contains($value, 'eau')) {
            return 'Moyen';
        }
        return 'Bon';
    }
}