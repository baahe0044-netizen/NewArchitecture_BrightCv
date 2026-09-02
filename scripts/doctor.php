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
foreach (['json', 'mbstring', 'pdo', 'pdo_mysql', 'session', 'zlib'] as $extension) {
    $result(extension_loaded($extension), 'PHP extension ' . $extension);
}

// Optional: their absence narrows what a CV can be imported from, but the rest
// of the app is unaffected, so these are reported rather than failed.
if (!extension_loaded('zip')) {
    echo '[NOTE] PHP extension zip is missing — Word (.docx) import is unavailable. '
        . 'PDF, plain text, and pasted text still work.' . PHP_EOL;
}

// The importer accepts files up to 5 MB, so PHP has to as well.
$toBytes = static function (string $value): float {
    $value = trim($value);
    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    return match ($unit) {
        'g' => $number * 1024 ** 3,
        'm' => $number * 1024 ** 2,
        'k' => $number * 1024,
        default => $number,
    };
};
foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
    $configured = (string) ini_get($setting);
    $result($toBytes($configured) >= 5 * 1024 * 1024, 'PHP ' . $setting . ' is at least 5M', $configured);
}

$envFile = $root . DIRECTORY_SEPARATOR . '.env';
$result(is_file($envFile), '.env file exists', $envFile);
if (!is_file($envFile)) {
    echo PHP_EOL . 'Create it with: Copy-Item .env.example .env' . PHP_EOL;
    exit(1);
}

require_once $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

$result(
    filter_var(BASE_URL, FILTER_VALIDATE_URL) !== false && !str_ends_with(BASE_URL, '/'),
    'APP_URL is a valid URL with no trailing slash',
    BASE_URL
);
if (APP_ENV === 'production') {
    $result(str_starts_with(BASE_URL, 'https://'), 'APP_URL uses HTTPS in production', BASE_URL);
    $result(!APP_DEBUG, 'APP_DEBUG is off in production');

    // A broken mail setup is invisible until someone cannot reset a password,
    // so it is worth failing here instead.
    $mailDriver = (string) env('MAIL_DRIVER', 'api');
    $result(
        in_array($mailDriver, MailService::DRIVERS, true) && $mailDriver !== 'log',
        'MAIL_DRIVER sends mail in production',
        $mailDriver === 'log' ? 'log only writes to storage/logs/mail.log' : $mailDriver
    );

    if ($mailDriver === 'api') {
        $result(
            in_array((string) env('MAIL_API_PROVIDER', 'brevo'), HttpMailer::PROVIDERS, true),
            'MAIL_API_PROVIDER is one of: ' . implode(', ', HttpMailer::PROVIDERS)
        );
        $result(trim((string) env('MAIL_API_KEY', '')) !== '', 'MAIL_API_KEY is set');
        $result(
            function_exists('curl_init') || (bool) ini_get('allow_url_fopen'),
            'curl or allow_url_fopen is available to reach the mail provider'
        );
    }

    if ($mailDriver === 'smtp') {
        $result(trim((string) env('MAIL_HOST', '')) !== '', 'MAIL_HOST is set');
        $result(trim((string) env('MAIL_PASSWORD', '')) !== '', 'MAIL_PASSWORD is set');
    }

    $from = (string) env('MAIL_FROM_ADDRESS', '');
    $result(
        filter_var($from, FILTER_VALIDATE_EMAIL) !== false && !str_contains($from, 'example.com'),
        'MAIL_FROM_ADDRESS is a real address on a domain you verified',
        $from
    );
}

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

echo 'BrightCV is ready. Open:' . PHP_EOL;
echo BASE_URL . '/' . PHP_EOL;
