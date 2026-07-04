<?php

require_once __DIR__ . '/../../config/Database.php';

class UserRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmailAndPassword($email, $password)
    {
        $query = $this->db->prepare("
            SELECT *
            FROM users
            WHERE email = :email
            AND password_hash = :password_hash
        ");

        $query->execute([
            ':email' => $email,
            ':password_hash' => $password
        ]);

        return $query->fetch();
    }
}
