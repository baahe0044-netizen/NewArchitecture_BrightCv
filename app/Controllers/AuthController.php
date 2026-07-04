<?php

class AuthController
{
    public function loginPage()
    {
        require VIEW_PATH . '/auth/login.php';
    }

    public function logout()
    {
        session_start();
        session_destroy();

        header("Location: " . BASE_URL . "/login");
        exit;
    }
}
