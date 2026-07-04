<?php
// connect.php
try {
    $conn = new PDO(
        'mysql:host=localhost;dbname=lunettistar_db;charset=utf8mb4', 
        'root', 
        '', 
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,  // Important for MyISAM
            PDO::ATTR_PERSISTENT => false,       // Better for shared hosting
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // Additional settings for better performance
    $conn->exec("SET SESSION sql_mode=''"); // Disable strict mode for MyISAM compatibility
    
} catch (PDOException $e) {
    // Log detailed error for debugging
    error_log("[" . date('Y-m-d H:i:s') . "] Database connection error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // User-friendly message (don't expose system details)
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'message' => 'Service temporarily unavailable. Our team has been notified.'
    ]));
}

// Optional: Set timezone if not set in php.ini
$conn->exec("SET time_zone = '+00:00'");
?>