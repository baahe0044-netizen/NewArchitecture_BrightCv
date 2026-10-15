<?php

declare(strict_types=1);

/**
 * Read-only queries the gamification layer needs, against tables that
 * already exist.
 *
 * Phase 1 of the Quest redesign derives XP, streaks, and badges from data the
 * app already records rather than adding a schema for them. Nothing here
 * writes anything: every number is recomputed on each request from the same
 * rows dashboardStats() and the builder already read, so it cannot drift from
 * what actually happened and cannot be edited independently of the CV itself.
 */
final class GamificationRepository
{
    private readonly PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Enough of each CV to score it: completion and ATS as already stored,
     * plus the content itself so real achievement lines, summary length, and
     * role count can be read rather than guessed.
     */
    public function resumeContentSummaries(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, template_key, status, completion, ats_score, content_json,
                    last_exported_at, created_at, updated_at
             FROM resumes
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY updated_at DESC'
        );
        $statement->execute([$userId]);
        $rows = $statement->fetchAll();

        foreach ($rows as &$row) {
            $row['content'] = json_decode((string) $row['content_json'], true) ?: [];
            unset($row['content_json']);
        }

        return $rows;
    }

    public function totalDownloads(int $userId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM resume_generations g
             INNER JOIN resumes r ON r.id = g.resume_id
             WHERE r.user_id = ? AND r.deleted_at IS NULL'
        );
        $statement->execute([$userId]);
        return (int) $statement->fetchColumn();
    }

    /**
     * Calendar dates, most recent first, on which the account did anything —
     * a save, an export, an import. The streak is read off the front of this
     * list rather than stored, so it cannot go stale.
     *
     * @return list<string> Y-m-d dates, descending.
     */
    public function activeDates(int $userId, int $days = 60): array
    {
        $statement = $this->db->prepare(
            'SELECT DISTINCT DATE(created_at) AS activity_date
             FROM user_activity
             WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             ORDER BY activity_date DESC'
        );
        $statement->execute([$userId, $days]);
        return array_column($statement->fetchAll(), 'activity_date');
    }

    /** XP earned "today" is read from activity rows already timestamped. */
    public function activityCountToday(int $userId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM user_activity WHERE user_id = ? AND DATE(created_at) = CURDATE()'
        );
        $statement->execute([$userId]);
        return (int) $statement->fetchColumn();
    }
}
