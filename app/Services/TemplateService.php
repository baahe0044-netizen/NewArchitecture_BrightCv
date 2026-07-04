<?php

require_once __DIR__ . '/../Repositories/TemplateRepository.php';

class TemplateService
{
    private TemplateRepository $repository;

    public function __construct()
    {
        $this->repository = new TemplateRepository();
    }

    public function getTemplates()
    {
        return $this->repository->getTemplates();
    }

    public function getTemplate($id)
    {
        return $this->repository->getTemplate($id);
    }

    public function getSelectedTemplate($resumeId)
    {
        return $this->repository->getSelectedTemplate($resumeId);
    }

    public function saveSelectedTemplate($resumeId, $templateId)
    {
        return $this->repository->saveSelectedTemplate(
            $resumeId,
            $templateId
        );
    }
}
