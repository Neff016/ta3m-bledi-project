<?php
declare(strict_types=1);

class Workshop
{
    public function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT w.*, (w.capacity - COUNT(r.id)) AS places_left
                FROM workshops w
                LEFT JOIN registrations r ON r.workshop_id = w.id
                ' . ($activeOnly ? 'WHERE w.status = "active" ' : '') . '
                GROUP BY w.id
                ORDER BY w.workshop_date ASC';
        return db()->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM workshops WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO workshops (title, category, description, workshop_date, location, capacity, icon, status)
             VALUES (:title, :category, :description, :workshop_date, :location, :capacity, :icon, :status)'
        );
        $stmt->execute($data);
        return (int) db()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = db()->prepare(
            'UPDATE workshops SET title=:title, category=:category, description=:description,
             workshop_date=:workshop_date, location=:location, capacity=:capacity, icon=:icon, status=:status
             WHERE id=:id'
        );
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM workshops WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function stats(): array
    {
        return [
            'workshops' => (int) db()->query('SELECT COUNT(*) FROM workshops')->fetchColumn(),
            'registrations' => (int) db()->query('SELECT COUNT(*) FROM registrations')->fetchColumn(),
            'users' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn(),
            'upcoming' => (int) db()->query('SELECT COUNT(*) FROM workshops WHERE workshop_date >= CURDATE() AND status="active"')->fetchColumn(),
        ];
    }
}
