<?php
declare(strict_types=1);

class User
{
    public function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $password): int
    {
        $stmt = db()->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, "user")'
        );
        $stmt->execute([
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        return (int) db()->lastInsertId();
    }

    public function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
