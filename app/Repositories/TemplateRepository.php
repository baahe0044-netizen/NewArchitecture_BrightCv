<?php

require_once __DIR__ . '/../../config/Database.php';

class TemplateRepository
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getTemplates()
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                name,
                description
            FROM resume_templates
            WHERE is_active = 1
            AND LENGTH(css_styles) > 0
            AND LENGTH(html_structure) > 0
            ORDER BY id
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTemplate($id)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM resume_templates
            WHERE id=?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSelectedTemplate($resumeId)
    {
        $stmt = $this->db->prepare("
            SELECT template_id
            FROM resume_selected_template
            WHERE resume_id=?
            LIMIT 1
        ");

        $stmt->execute([$resumeId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveSelectedTemplate($resumeId, $templateId)
    {
        $stmt = $this->db->prepare("
            REPLACE INTO resume_selected_template
            (resume_id,template_id)
            VALUES(?,?)
        ");

        return $stmt->execute([
            $resumeId,
            $templateId
        ]);
    }
}
