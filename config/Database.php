<?php
class Database
{
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection === null) {

            try {

                self::$connection = new PDO(
                    "mysql:host=localhost;dbname=lunettistar_db;charset=utf8mb4",
                    "root",
                    ""
                );

                self::$connection->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                self::$connection->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );
            } catch (PDOException $e) {

                error_log($e->getMessage());

                die("We're experiencing technical difficulties. Please try again later.");
            }
        }

        return self::$connection;
    }
}


$dbname   = 'lunettistar_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die("Database connection failed: " . $e->getMessage());
}
