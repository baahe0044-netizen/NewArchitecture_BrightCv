<?php

declare(strict_types=1);

final class ResumeRepository
{
    private readonly PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(int $userId, string $name, array $content, string $templateKey = 'modern', string $accentColor = '#5b4df7'): array
    {
        $statement = $this->db->prepare(
            "INSERT INTO resumes
             (user_id, name, template_key, language, accent_color, font_family, content_json, status, completion, ats_score, version, created_at, updated_at)
             VALUES (?, ?, ?, 'en', ?, 'Inter', ?, 'draft', 0, 0, 1, NOW(), NOW())"
        );
        $statement->execute([$userId, $name, $templateKey, $accentColor, $this->encode($content)]);

        return $this->findOwned((int) $this->db->lastInsertId(), $userId);
    }

    public function listByUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, template_key, language, accent_color, status, completion, ats_score,
                    version, last_exported_at, created_at, updated_at
             FROM resumes
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY updated_at DESC'
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function findOwned(int $id, int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM resumes WHERE id = ? AND user_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $statement->execute([$id, $userId]);
        $resume = $statement->fetch();

        if (!$resume) {
            return null;
        }

        $resume['content'] = json_decode((string) $resume['content_json'], true) ?: [];
        unset($resume['content_json']);
        return $resume;
    }

    public function save(int $id, int $userId, array $attributes): ?array
    {
        return Database::transaction(function (PDO $pdo) use ($id, $userId, $attributes): ?array {
            $current = $this->findOwned($id, $userId);
            if (!$current) {
                return null;
            }

            $expectedVersion = $attributes['version'] ?? null;
            if ($expectedVersion !== null && (int) $expectedVersion !== (int) $current['version']) {
                throw new ConflictException('This CV was updated in another tab or device.');
            }

            $latestSnapshot = $pdo->prepare(
                'SELECT created_at FROM resume_versions WHERE resume_id = ? ORDER BY created_at DESC LIMIT 1'
            );
            $latestSnapshot->execute([$id]);
            $latestCreatedAt = $latestSnapshot->fetchColumn();
            if (!$latestCreatedAt || strtotime((string) $latestCreatedAt) <= time() - 300) {
                $snapshot = $pdo->prepare(
                    'INSERT IGNORE INTO resume_versions (resume_id, version, content_json, created_at)
                     VALUES (?, ?, ?, NOW())'
                );
                $snapshot->execute([$id, (int) $current['version'], $this->encode($current['content'])]);
            }

            $statement = $pdo->prepare(
                'UPDATE resumes SET
                    name = ?, template_key = ?, language = ?, accent_color = ?, font_family = ?,
                    content_json = ?, job_description = ?, status = ?, completion = ?,
                    version = version + 1, updated_at = NOW()
                 WHERE id = ? AND user_id = ? AND version = ? AND deleted_at IS NULL'
            );
            $statement->execute([
                $attributes['name'],
                $attributes['template_key'],
                $attributes['language'],
                $attributes['accent_color'],
                $attributes['font_family'],
                $this->encode($attributes['content']),
                $attributes['job_description'],
                $attributes['status'],
                $attributes['completion'],
                $id,
                $userId,
                (int) $current['version'],
            ]);

            if ($statement->rowCount() !== 1) {
                throw new ConflictException('This CV was updated while your changes were being saved.');
            }

            return $this->findOwned($id, $userId);
        });
    }

    public function duplicate(int $id, int $userId): ?array
    {
        $source = $this->findOwned($id, $userId);
        if (!$source) {
            return null;
        }

        $statement = $this->db->prepare(
            "INSERT INTO resumes
             (user_id, name, template_key, language, accent_color, font_family, content_json, job_description,
              status, completion, ats_score, version, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, 1, NOW(), NOW())"
        );
        $statement->execute([
            $userId,
            mb_substr((string) $source['name'], 0, 143) . ' (Copy)',
            $source['template_key'],
            $source['language'],
            $source['accent_color'],
            $source['font_family'],
            $this->encode($source['content']),
            $source['job_description'],
            $source['completion'],
            $source['ats_score'],
        ]);

        return $this->findOwned((int) $this->db->lastInsertId(), $userId);
    }

    public function softDelete(int $id, int $userId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE resumes SET deleted_at = NOW(), updated_at = NOW()
             WHERE id = ? AND user_id = ? AND deleted_at IS NULL'
        );
        $statement->execute([$id, $userId]);
        return $statement->rowCount() > 0;
    }

    public function dashboardStats(int $userId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                COUNT(*) AS total_resumes,
                COALESCE(ROUND(AVG(NULLIF(ats_score, 0))), 0) AS average_ats,
                COALESCE(ROUND(AVG(completion)), 0) AS average_completion,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_resumes
             FROM resumes WHERE user_id = ? AND deleted_at IS NULL"
        );
        $statement->execute([$userId]);
        $stats = $statement->fetch() ?: [];

        $downloads = $this->db->prepare(
            'SELECT COUNT(*) FROM resume_generations g
             INNER JOIN resumes r ON r.id = g.resume_id
             WHERE r.user_id = ? AND r.deleted_at IS NULL'
        );
        $downloads->execute([$userId]);
        $stats['total_downloads'] = (int) $downloads->fetchColumn();

        $trend = $this->db->prepare(
            'SELECT DATE(s.created_at) AS date, ROUND(AVG(s.score)) AS score
             FROM resume_ats_scores s
             INNER JOIN resumes r ON r.id = s.resume_id
             WHERE r.user_id = ? AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(s.created_at)
             ORDER BY date ASC'
        );
        $trend->execute([$userId]);
        $stats['ats_trend'] = $trend->fetchAll();

        return $stats;
    }

    public function saveAtsScore(int $resumeId, int $score, int $keywordScore, array $report): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO resume_ats_scores (resume_id, score, keyword_score, report_json, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $statement->execute([$resumeId, $score, $keywordScore, $this->encode($report)]);

        $update = $this->db->prepare('UPDATE resumes SET ats_score = ?, updated_at = NOW() WHERE id = ?');
        $update->execute([$score, $resumeId]);
    }

    public function saveAssistantHistory(int $resumeId, string $action, string $input, string $output): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO resume_ai_history (resume_id, action, input_text, output_text, provider, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $statement->execute([$resumeId, $action, $input, $output, (string) env('AI_PROVIDER', 'local')]);
    }

    public function recordExport(int $resumeId, string $format, string $language): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO resume_generations (resume_id, format, language, created_at)
             VALUES (?, ?, ?, NOW())'
        );
        $statement->execute([$resumeId, $format, $language]);

        $update = $this->db->prepare('UPDATE resumes SET last_exported_at = NOW() WHERE id = ?');
        $update->execute([$resumeId]);
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
