<?php

declare(strict_types=1);

final class UserRepository
{
    private readonly PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, email, role, plan, locale, job_title, avatar_path, email_verified_at,
                    auth_version, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $statement->execute([mb_strtolower(trim($email))]);
        return $statement->fetch() ?: null;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO users (name, email, password_hash, role, plan, locale, created_at, updated_at)
             VALUES (?, ?, ?, 'user', 'free', 'en', NOW(), NOW())"
        );
        $statement->execute([trim($name), mb_strtolower(trim($email)), $passwordHash]);
        return (int) $this->db->lastInsertId();
    }

    public function updateLastLogin(int $id): void
    {
        $statement = $this->db->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?');
        $statement->execute([$id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $statement = $this->db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $statement->execute([$hash, $id]);
    }

    public function updatePasswordAndInvalidateSessions(int $id, string $hash): void
    {
        $statement = $this->db->prepare(
            'UPDATE users
             SET password_hash = ?, auth_version = auth_version + 1, updated_at = NOW()
             WHERE id = ?'
        );
        $statement->execute([$hash, $id]);
    }

    public function updateProfile(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE users SET name = ?, job_title = ?, locale = ?, updated_at = NOW() WHERE id = ?'
        );
        $statement->execute([
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['job_title'] ?? '')),
            (string) ($data['locale'] ?? 'en'),
            $id,
        ]);
    }
}
