<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../app/Controllers/ResumeController.php';

$controller = new ResumeController();

$action = $_GET['action'] ?? '';

switch ($action) {

    case "dashboard_data":

        echo json_encode(
            $controller->dashboardData()
        );

        break;

    case "get_resumes":

        echo json_encode(
            $controller->getResumes()
        );

        break;

    case "create_resume":

        echo json_encode(
            $controller->createResume()
        );

        break;

    default:

        echo json_encode([
            "success" => false,
            "message" => "Invalid action"
        ]);
}
