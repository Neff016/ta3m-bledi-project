<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/** Requêtes PDO du domaine Suivi. */
final class SuiviModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnexion();
    }

    public function forPlantation(int $plantationId): array
    {
        $stmt = $this->db->prepare('SELECT s.id AS idSuivi, s.plantation_id AS idPlantation, s.followup_date AS dateSuivi, s.photo, s.etat, s.arrosage, s.observation, s.diagnosticIA FROM suivis s WHERE s.plantation_id = :plantation ORDER BY s.followup_date DESC, s.id DESC');
        $stmt->execute(['plantation' => $plantationId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query(
            "SELECT s.id AS idSuivi, s.plantation_id AS idPlantation, s.followup_date AS dateSuivi, s.photo, s.etat, s.arrosage, s.observation, s.diagnosticIA, p.name AS plantationNom, CASE WHEN p.user_id IS NULL THEN 'Public' ELSE CONCAT('Utilisateur #', p.user_id) END AS username FROM suivis s JOIN plantations p ON p.id = s.plantation_id ORDER BY s.followup_date DESC, s.id DESC"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT s.id AS idSuivi, s.plantation_id AS idPlantation, s.followup_date AS dateSuivi, s.photo, s.etat, s.arrosage, s.observation, s.diagnosticIA, p.name AS plantationNom FROM suivis s JOIN plantations p ON p.id = s.plantation_id WHERE s.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data, int $plantationId): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO suivis (plantation_id, user_id, followup_date, stage, condition_state, observation, photo, etat, arrosage, diagnosticIA) SELECT p.id, p.user_id, :dateSuivi, p.status, :conditionState, :observation, :photo, :etat, :arrosage, :diagnosticIA FROM plantations p WHERE p.id = :plantation'
        );
        $parameters = $this->parameters($data, $plantationId);
        $parameters['conditionState'] = $this->conditionState($data['etat']);
        return $stmt->execute($parameters);
    }

    public function updateById(int $id, array $data, int $plantationId): bool
    {
        $parameters = $this->parameters($data, $plantationId);
        $parameters['id'] = $id;
        $parameters['conditionState'] = $this->conditionState($data['etat']);
        $stmt = $this->db->prepare(
            'UPDATE suivis SET followup_date = :dateSuivi, photo = :photo, etat = :etat, condition_state = :conditionState, arrosage = :arrosage, observation = :observation, diagnosticIA = :diagnosticIA WHERE id = :id AND plantation_id = :plantation'
        );
        return $stmt->execute($parameters);
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM suivis WHERE id = :id');
        return $stmt->execute(['id' => $id]);
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
