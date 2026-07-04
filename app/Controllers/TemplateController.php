<?php

require_once __DIR__ . '/../Services/TemplateService.php';

class TemplateController
{
    private TemplateService $service;

    public function __construct()
    {
        $this->service = new TemplateService();
    }

    public function index()
    {
        $templates = $this->service->getTemplates();

        require __DIR__ .
            '/../Views/templates/list.php';
    }

    public function preview($id)
    {
        $template =
            $this->service->getTemplate($id);

        require __DIR__ .
            '/../Views/templates/preview.php';
    }

    public function useTemplate($resumeId, $templateId)
    {
        return $this->service
            ->saveSelectedTemplate(
                $resumeId,
                $templateId
            );
    }
}
