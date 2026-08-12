<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// The application bootstrap configures and starts the session. Buffer the
// diagnostic lines written before it is loaded so CLI output does not count as
// sent headers and trigger misleading session warnings.
ob_start();

$root = dirname(__DIR__);
$failures = [];

$result = static function (bool $ok, string $label, string $detail = '') use (&$failures): void {
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
    if (!$ok) {
        $failures[] = $label . ($detail !== '' ? ': ' . $detail : '');
    }
};

$result(version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP 8.1 or newer', PHP_VERSION);
foreach (['json', 'mbstring', 'pdo', 'pdo_mysql', 'session'] as $extension) {
    $result(extension_loaded($extension), 'PHP extension ' . $extension);
}

$envFile = $root . DIRECTORY_SEPARATOR . '.env';
$result(is_file($envFile), '.env file exists', $envFile);
if (!is_file($envFile)) {
    echo PHP_EOL . 'Create it with: Copy-Item .env.example .env' . PHP_EOL;
    exit(1);
}

require_once $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

$expectedUrl = 'http://localhost/NewArchitecture_BrightCv/public';
$result(BASE_URL === $expectedUrl, 'APP_URL matches the WAMP folder', 'current: ' . BASE_URL);

$key = trim((string) env('APP_KEY', ''));
$placeholder = $key === '' || str_contains($key, 'replace-') || str_contains($key, 'PASTE_');
$result(!$placeholder && strlen($key) >= 32, 'APP_KEY is configured');

foreach (['cache', 'logs', 'pdfs', 'uploads'] as $directory) {
    $path = STORAGE_PATH . DIRECTORY_SEPARATOR . $directory;
    $result(is_dir($path) && is_writable($path), 'Writable storage/' . $directory, $path);
}

$result(is_file($root . DIRECTORY_SEPARATOR . '.htaccess'), 'Root .htaccess exists');
$result(is_file(PUBLIC_PATH . DIRECTORY_SEPARATOR . '.htaccess'), 'Public .htaccess exists');
$result(is_file(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'index.php'), 'Public index.php exists');

try {
    $pdo = Database::getConnection();
    $result(true, 'MySQL connection');
    foreach (['users', 'resume_templates', 'resumes', 'resume_generations', 'password_reset_tokens'] as $table) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
        );
        $statement->execute([(string) env('DB_DATABASE', 'brightcv_db'), $table]);
        $result((int) $statement->fetchColumn() === 1, 'Database table ' . $table);
    }
} catch (Throwable $exception) {
    $result(false, 'MySQL connection/schema', $exception->getMessage());
}

echo PHP_EOL;
if ($failures !== []) {
    echo count($failures) . ' problem(s) found. Correct the FAIL lines, then run this command again.' . PHP_EOL;
    exit(1);
}

echo 'BrightCV is ready. Restart WAMP and open:' . PHP_EOL;
echo $expectedUrl . '/' . PHP_EOL;
