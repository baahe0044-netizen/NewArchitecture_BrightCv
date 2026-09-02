<?php

declare(strict_types=1);

/**
 * The deployment check, for hosts with no shell.
 *
 * scripts/doctor.php answers the same questions from the command line, which
 * free and shared plans do not offer. Without something like this a failed
 * upload is diagnosed by guesswork: the app returns a blank page and nothing
 * says whether the cause is a missing extension, an unwritable directory, or
 * a database that was never imported.
 *
 * Reaching it requires HEALTH_TOKEN from .env, so it cannot be browsed by
 * anyone who finds the URL. Delete this file once the site is up if you would
 * rather it not exist at all.
 */

require_once dirname(__DIR__) . '/config/app.php';

$expected = trim((string) env('HEALTH_TOKEN', ''));
$supplied = (string) ($_GET['token'] ?? '');

// An unset token closes the page rather than opening it.
if ($expected === '' || !hash_equals($expected, $supplied)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$failures = 0;
$warnings = 0;

/** Report one check. A warning is something the app survives without. */
$check = static function (bool $passed, string $label, string $detail = '', bool $fatal = true) use (&$failures, &$warnings): void {
    if ($passed) {
        echo '[PASS] ' . $label . ($detail === '' ? '' : ' — ' . $detail) . "\n";
        return;
    }
    if ($fatal) {
        $failures++;
        echo '[FAIL] ' . $label . ($detail === '' ? '' : ' — ' . $detail) . "\n";
        return;
    }
    $warnings++;
    echo '[WARN] ' . $label . ($detail === '' ? '' : ' — ' . $detail) . "\n";
};

echo "BrightCV deployment check\n";
echo str_repeat('-', 52) . "\n";

// --- Runtime ---------------------------------------------------------------
$check(PHP_VERSION_ID >= 80100, 'PHP 8.1 or newer', PHP_VERSION);

foreach (['json', 'mbstring', 'pdo', 'pdo_mysql', 'session', 'zlib'] as $extension) {
    $check(extension_loaded($extension), 'Extension ' . $extension);
}
$check(
    extension_loaded('zip'),
    'Extension zip',
    'Optional. Without it .docx import is off; PDF and text import still work.',
    false
);

/** Turn "8M" into bytes so the limits can be compared. */
$bytes = static function (string $value): int {
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $unit = strtolower($value[strlen($value) - 1]);
    $number = (int) $value;

    return match ($unit) {
        'g' => $number * 1024 * 1024 * 1024,
        'm' => $number * 1024 * 1024,
        'k' => $number * 1024,
        default => $number,
    };
};

$minimum = 5 * 1024 * 1024;
$check($bytes((string) ini_get('upload_max_filesize')) >= $minimum, 'upload_max_filesize is at least 5M', (string) ini_get('upload_max_filesize'));
$check($bytes((string) ini_get('post_max_size')) >= $minimum, 'post_max_size is at least 5M', (string) ini_get('post_max_size'));

// --- Configuration ---------------------------------------------------------
echo str_repeat('-', 52) . "\n";
$check(APP_ENV === 'production', 'APP_ENV is production', APP_ENV);
$check(!APP_DEBUG, 'APP_DEBUG is off', APP_DEBUG ? 'on — this leaks stack traces' : 'off');
$check(str_starts_with(BASE_URL, 'https://'), 'APP_URL uses HTTPS', BASE_URL);

$key = trim((string) env('APP_KEY', ''));
$check(
    strlen($key) >= 32 && !str_contains($key, 'replace-'),
    'APP_KEY is set to a real secret',
    // Never print the key; its length is enough to tell it apart from a stub.
    strlen($key) . ' characters'
);

// The service worker only registers over HTTPS, so the PWA depends on it.
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
$check($secure, 'This page was served over HTTPS', 'the PWA will not install without it');

// --- Filesystem ------------------------------------------------------------
echo str_repeat('-', 52) . "\n";
foreach (['cache', 'logs', 'pdfs', 'uploads'] as $directory) {
    $path = STORAGE_PATH . '/' . $directory;
    $check(is_dir($path) && is_writable($path), 'Writable storage/' . $directory);
}
$check(is_file(ROOT_PATH . '/.htaccess'), 'Root .htaccess uploaded');
$check(is_file(STORAGE_PATH . '/.htaccess'), 'storage/.htaccess uploaded', 'keeps the mail log unreadable');

// --- Database --------------------------------------------------------------
echo str_repeat('-', 52) . "\n";
try {
    $pdo = Database::getConnection();
    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    $check(true, 'Database connection', $version);

    $tables = ['users', 'resume_templates', 'resumes', 'resume_versions', 'resume_generations', 'password_reset_tokens'];
    $missing = [];
    foreach ($tables as $table) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
        );
        $statement->execute([(string) env('DB_DATABASE', ''), $table]);
        $present = (int) $statement->fetchColumn() === 1;
        if (!$present) {
            $missing[] = $table;
        }
        $check($present, 'Table ' . $table);
    }
    if ($missing !== []) {
        echo "        Import database/schema.sql through phpMyAdmin.\n";
    }

    $templates = (int) $pdo->query('SELECT COUNT(*) FROM resume_templates')->fetchColumn();
    $check($templates >= 18, 'CV templates seeded', $templates . ' found, 18 expected');
} catch (Throwable $exception) {
    // The message can name the host and user, which is what makes it useful
    // here, and this page already required a secret to reach.
    $check(false, 'Database connection', $exception->getMessage());
}

// --- Mail ------------------------------------------------------------------
echo str_repeat('-', 52) . "\n";
$driver = (string) env('MAIL_DRIVER', 'api');
$check($driver !== 'log', 'MAIL_DRIVER sends mail', $driver === 'log' ? 'log only writes to storage/logs/mail.log' : $driver);

if ($driver === 'api') {
    $check(in_array((string) env('MAIL_API_PROVIDER', ''), HttpMailer::PROVIDERS, true), 'MAIL_API_PROVIDER is supported', implode(' or ', HttpMailer::PROVIDERS));
    $check(trim((string) env('MAIL_API_KEY', '')) !== '', 'MAIL_API_KEY is set');
    $check(function_exists('curl_init') || (bool) ini_get('allow_url_fopen'), 'curl or allow_url_fopen available');
}

$from = (string) env('MAIL_FROM_ADDRESS', '');
$check(
    filter_var($from, FILTER_VALIDATE_EMAIL) !== false && !str_contains($from, 'example.com'),
    'MAIL_FROM_ADDRESS is a verified address',
    $from
);

// --- Verdict ---------------------------------------------------------------
echo str_repeat('-', 52) . "\n";
if ($failures > 0) {
    echo $failures . " problem(s) to fix. Correct the FAIL lines and reload.\n";
    if ($warnings > 0) {
        echo $warnings . " warning(s) you can live with.\n";
    }
    exit;
}

echo $warnings > 0
    ? "Ready, with " . $warnings . " warning(s) above.\n"
    : "Everything checks out.\n";
echo "Delete public/health.php when you are done.\n";
