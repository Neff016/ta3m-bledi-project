<?php
declare(strict_types=1);

class Registration
{
    public function forUser(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT r.id AS registration_id, r.created_at, w.*
             FROM registrations r JOIN workshops w ON w.id = r.workshop_id
             WHERE r.user_id = ? ORDER BY w.workshop_date ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function register(int $userId, int $workshopId): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id, capacity, status FROM workshops WHERE id = ? FOR UPDATE');
            $stmt->execute([$workshopId]);
            $workshop = $stmt->fetch();
            if (!$workshop || $workshop['status'] !== 'active') {
                throw new RuntimeException('Cet atelier n’est pas disponible.');
            }

            $dup = $pdo->prepare('SELECT id FROM registrations WHERE user_id = ? AND workshop_id = ?');
            $dup->execute([$userId, $workshopId]);
            if ($dup->fetch()) {
                throw new RuntimeException('Tu es déjà inscrit à cet atelier.');
            }

            $count = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE workshop_id = ?');
            $count->execute([$workshopId]);
            if ((int) $count->fetchColumn() >= (int) $workshop['capacity']) {
                throw new RuntimeException('Désolé, cet atelier est complet.');
            }

            $insert = $pdo->prepare('INSERT INTO registrations (user_id, workshop_id) VALUES (?, ?)');
            $insert->execute([$userId, $workshopId]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function cancelForUser(int $registrationId, int $userId): void
    {
        $stmt = db()->prepare('DELETE FROM registrations WHERE id = ? AND user_id = ?');
        $stmt->execute([$registrationId, $userId]);
    }

    public function all(): array
    {
        return db()->query(
            'SELECT r.id AS registration_id, r.created_at, u.name, u.email,
                    w.title AS workshop_title, w.workshop_date, w.location
             FROM registrations r
             JOIN users u ON u.id = r.user_id
             JOIN workshops w ON w.id = r.workshop_id
             ORDER BY r.created_at DESC'
        )->fetchAll();
    }

    public function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM registrations WHERE id = ?');
        $stmt->execute([$id]);
    }
}
