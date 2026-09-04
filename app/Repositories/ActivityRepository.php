<?php

declare(strict_types=1);

final class ActivityRepository
{
    private readonly PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function record(int $userId, string $action, string $description, ?int $resumeId = null): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO user_activity (user_id, resume_id, action, description, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $statement->execute([$userId, $resumeId, $action, $description]);
    }

    public function reassignOwner(int $fromUserId, int $toUserId): void
    {
        $statement = $this->db->prepare('UPDATE user_activity SET user_id = ? WHERE user_id = ?');
        $statement->execute([$toUserId, $fromUserId]);
    }

    public function recent(int $userId, int $limit = 8): array
    {
        $limit = max(1, min($limit, 25));
        $statement = $this->db->prepare(
            'SELECT action, description, resume_id, created_at
             FROM user_activity WHERE user_id = ?
             ORDER BY created_at DESC LIMIT ' . $limit
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }
}
