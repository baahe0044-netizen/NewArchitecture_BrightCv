<?php

declare(strict_types=1);

final class TemplateRepository
{
    private readonly PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function allActive(): array
    {
        $statement = $this->db->query(
            'SELECT id, template_key, name, category, description, color, is_premium
             FROM resume_templates WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC'
        );
        return $statement->fetchAll();
    }

    public function findByKey(string $key): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, template_key, name, category, description, color, is_premium
             FROM resume_templates WHERE template_key = ? AND is_active = 1 LIMIT 1'
        );
        $statement->execute([$key]);
        return $statement->fetch() ?: null;
    }
}
