<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/app.php';

$database = (string) env('DB_DATABASE', 'brightcv_db');
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

$requiredColumns = [
    'users' => [
        'id', 'name', 'email', 'password_hash', 'auth_version', 'role', 'plan', 'locale',
        'job_title', 'avatar_path', 'is_guest', 'email_verified_at', 'last_login_at', 'created_at', 'updated_at',
    ],
    'resume_templates' => [
        'id', 'template_key', 'name', 'category', 'description', 'color', 'is_active',
        'is_premium', 'sort_order', 'created_at', 'updated_at',
    ],
    'resumes' => [
        'id', 'user_id', 'name', 'template_key', 'language', 'accent_color', 'font_family',
        'content_json', 'job_description', 'status', 'completion', 'ats_score', 'version',
        'last_exported_at', 'deleted_at', 'created_at', 'updated_at',
    ],
];
$columnsStatement = $pdo->prepare(
    'SELECT COLUMN_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
);

// Known, additive upgrades: safe to apply to an existing BrightCV database
// automatically rather than making every deployment stop and hand-run an
// ALTER TABLE. Each entry only adds a column an older install of BrightCV
// itself would be missing -- never touches or drops existing data -- so by
// the time the strict check below runs, a legitimate older install passes
// it instead of being told to back up and reset over one new column.
$knownUpgrades = [
    ['users', 'is_guest', 'ALTER TABLE users '
        . 'ADD COLUMN is_guest TINYINT(1) NOT NULL DEFAULT 0 AFTER avatar_path, '
        . 'ADD INDEX idx_users_is_guest (is_guest)'],
];
foreach ($knownUpgrades as [$table, $column, $alterSql]) {
    $columnsStatement->execute([$database, $table]);
    $existingColumns = $columnsStatement->fetchAll(PDO::FETCH_COLUMN);
    if ($existingColumns !== [] && !in_array($column, $existingColumns, true)) {
        $pdo->exec($alterSql);
    }
}

foreach ($requiredColumns as $table => $columns) {
    $columnsStatement->execute([$database, $table]);
    $existingColumns = $columnsStatement->fetchAll(PDO::FETCH_COLUMN);
    if ($existingColumns === []) {
        continue;
    }

    $missingColumns = array_values(array_diff($columns, $existingColumns));
    if ($missingColumns !== []) {
        throw new RuntimeException(sprintf(
            'Database "%s" contains a legacy or incomplete "%s" table (missing: %s). '
            . 'No schema changes were applied. Back up the legacy database, set '
            . 'DB_DATABASE=brightcv_db in .env, and run this command again.',
            $database,
            $table,
            implode(', ', $missingColumns)
        ));
    }
}

$sql = (string) file_get_contents(__DIR__ . '/schema.sql');
$statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: []));

foreach ($statements as $statement) {
    if (preg_match('/^(CREATE DATABASE|USE)\b/i', $statement)) {
        continue;
    }
    $pdo->exec($statement);
}

echo sprintf("Database schema \"%s\" is ready.\n", $database);
