<?php

require_once __DIR__ . '/../Repositories/UserRepository.php';

class AuthService
{
    private $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function login($email, $password)
    {
        $user = $this->userRepository->findByEmailAndPassword(
            $email,
            $password
        );

        if (!$user) {
            return false;
        }

        foreach ($user as $key => $value) {
            $_SESSION['login_' . $key] = $value;
        }

        return true;
    }
}
