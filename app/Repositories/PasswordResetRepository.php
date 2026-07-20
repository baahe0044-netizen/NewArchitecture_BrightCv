<?php

declare(strict_types=1);

final class PasswordResetRepository
{
    private readonly PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(string $email, string $token, int $minutes = 30): void
    {
        $minutes = max(1, min(1440, $minutes));
        $this->db->prepare('DELETE FROM password_reset_tokens WHERE email = ? OR expires_at < NOW()')
            ->execute([$email]);

        $statement = $this->db->prepare(
            'INSERT INTO password_reset_tokens (email, token_hash, expires_at, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ' . $minutes . ' MINUTE), NOW())'
        );
        $statement->execute([
            $email,
            hash('sha256', $token),
        ]);
    }

    public function findValid(string $email, string $token): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, email, expires_at FROM password_reset_tokens
             WHERE email = ? AND token_hash = ? AND used_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1'
        );
        $statement->execute([$email, hash('sha256', $token)]);
        return $statement->fetch() ?: null;
    }

    public function markUsed(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE password_reset_tokens
             SET used_at = NOW()
             WHERE id = ? AND used_at IS NULL AND expires_at > NOW()'
        );
        $statement->execute([$id]);
        return $statement->rowCount() === 1;
    }
}
