<?php

require_once __DIR__ . '/../Services/ResumeService.php';
require_once __DIR__ . '/../Services/PdfService.php';

class ResumeController
{
    private ResumeService $resumeService;
    private $pdfService;

    public function __construct()
    {
        $this->resumeService = new ResumeService();
        $this->pdfService = new PdfService();
    }

    public function dashboardData()
    {
        $userId = 1;

        return [
            "success" => true,
            "data" => $this->resumeService->dashboardData($userId)
        ];
    }

    public function generatePdf()
    {
        session_start();

        $resumeId = isset($_GET['resume_id'])
            ? (int)$_GET['resume_id']
            : 0;

        $userId = $_SESSION['user_id'];

        // Support multiple possible service method names to avoid undefined method errors
        if (method_exists($this->resumeService, 'getResume')) {
            $resume = $this->resumeService->getResume($resumeId, $userId);
        } elseif (method_exists($this->resumeService, 'getResumeById')) {
            $resume = $this->resumeService->getResumeById($resumeId, $userId);
        } elseif (method_exists($this->resumeService, 'findResume')) {
            $resume = $this->resumeService->findResume($resumeId, $userId);
        } else {
            http_response_code(500);
            exit("Resume service method not found.");
        }

        if (!$resume) {
            http_response_code(403);
            exit("Resume not found or access denied.");
        }

        $this->pdfService->generateResumePdf(
            $resumeId,
            $resume
        );
    }

    public function getResumes()
    {
        $userId = 1;

        return [
            "success" => true,
            "data" => $this->resumeService->getResumes($userId)
        ];
    }

    public function createResume()
    {
        $userId = 1;

        $data = json_decode(file_get_contents("php://input"), true);

        $title = $data['title'] ?? "My Resume";

        $resumeId = $this->resumeService->createResume($userId, $title);

        return [
            "success" => true,
            "data" => [
                "resume_id" => $resumeId
            ]
        ];
    }

    public function builder()
    {
        require VIEW_PATH . '/resume/builder.php';
    }
}
