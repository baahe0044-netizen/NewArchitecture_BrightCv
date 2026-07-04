<?php

require_once __DIR__ . '/../../config/Database.php';

class ResumeRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /* =========================
       DASHBOARD DATA
    ========================= */

    public function getDashboardData($userId)
    {
        // CV COUNT
        $st = $this->db->prepare("
            SELECT COUNT(*) total
            FROM resumes
            WHERE user_id=?
        ");
        $st->execute([$userId]);
        $cvCount = $st->fetch()['total'];

        // DOWNLOADS
        $st = $this->db->prepare("
            SELECT COUNT(*) total
            FROM resume_generations rg
            JOIN resumes r ON r.id = rg.resume_id
            WHERE r.user_id=?
        ");
        $st->execute([$userId]);
        $downloads = $st->fetch()['total'];

        // ATS SCORE
        $st = $this->db->prepare("
            SELECT AVG(score) avg_score
            FROM resume_ats_scores rs
            JOIN resumes r ON r.id = rs.resume_id
            WHERE r.user_id=?
        ");
        $st->execute([$userId]);
        $ats = round($st->fetch()['avg_score'] ?? 0);

        // RECENT CVS
        $st = $this->db->prepare("
            SELECT id,name,updated_at
            FROM resumes
            WHERE user_id=?
            ORDER BY updated_at DESC
            LIMIT 5
        ");
        $st->execute([$userId]);
        $recent = $st->fetchAll();

        return [
            "total_cvs" => $cvCount,
            "total_downloads" => $downloads,
            "ats_score" => $ats,
            "recent_cvs" => $recent
        ];
    }

    public function getResumes($userId)
    {
        $st = $this->db->prepare("
            SELECT id,name,updated_at
            FROM resumes
            WHERE user_id=?
            ORDER BY updated_at DESC
        ");

        $st->execute([$userId]);

        return $st->fetchAll();
    }

    public function createResume($userId, $title)
    {
        $st = $this->db->prepare("
            INSERT INTO resumes(user_id,name)
            VALUES(?,?)
        ");

        $st->execute([$userId, $title]);

        return $this->db->lastInsertId();
    }
}
