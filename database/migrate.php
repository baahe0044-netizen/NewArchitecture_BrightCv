<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/app.php';

$database = (string) env('DB_DATABASE', 'lunettistar_db');
if (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
    throw new RuntimeException('DB_DATABASE may contain only letters, numbers, and underscores.');
}
$quotedDatabase = chr(96) . $database . chr(96);

$dsn = sprintf(
    'mysql:host=%s;port=%s;charset=%s',
    (string) env('DB_HOST', '127.0.0.1'),
    (string) env('DB_PORT', '3306'),
    (string) env('DB_CHARSET', 'utf8mb4')
);
$pdo = new PDO($dsn, (string) env('DB_USERNAME', 'root'), (string) env('DB_PASSWORD', ''), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
try {
    $pdo->exec('USE ' . $quotedDatabase);
} catch (PDOException $exception) {
    $driverCode = (int) ($exception->errorInfo[1] ?? 0);
    if ($driverCode !== 1049) {
        throw $exception;
    }
    $pdo->exec(
        'CREATE DATABASE ' . $quotedDatabase . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $pdo->exec('USE ' . $quotedDatabase);
}

$sql = (string) file_get_contents(__DIR__ . '/schema.sql');
$statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: []));

foreach ($statements as $statement) {
    if (preg_match('/^(CREATE DATABASE|USE)\b/i', $statement)) {
        continue;
    }
    $pdo->exec($statement);
}

echo "Database schema is ready.\n";
