<?php

session_start();

require_once __DIR__ . '/../app/Controllers/AuthController.php';

$controller = new AuthController();

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'login':

        if ($controller->login()) {
            echo 1;
        } else {
            echo 3;
        }

        break;

    default:

        echo "Invalid action";
}
