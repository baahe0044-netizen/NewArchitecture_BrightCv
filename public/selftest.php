<?php

/**
 * Standalone deployment diagnostic.
 *
 * Deliberately loads nothing from the application: no config, no autoloader,
 * no classes. health.php cannot report a problem that stops the app booting,
 * because it boots the app to run. This file answers the question "why does
 * the site return 500" when nothing else can, so it is written for PHP 7.0
 * and will run on any version the host might be set to.
 *
 * Token is hard-coded rather than read from .env, because an unreadable .env
 * is one of the faults this is here to find.
 *
 * DELETE THIS FILE once the site works.
 */

$TOKEN = '29bd3f1f404934ca5df7855fc6966601';

if (!isset($_GET['token']) || !hash_equals($TOKEN, (string) $_GET['token'])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

// Show what is going wrong instead of a blank 500.
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$rule = str_repeat('-', 56);
echo "BrightCV self test\n" . $rule . "\n";

// --- PHP -------------------------------------------------------------------
echo "PHP version        : " . PHP_VERSION . "\n";
$versionOk = version_compare(PHP_VERSION, '8.1.0', '>=');
echo "PHP 8.1 or newer   : " . ($versionOk ? 'yes' : 'NO  <-- the app cannot run on this') . "\n";
echo "SAPI               : " . PHP_SAPI . "\n";
echo $rule . "\n";

// --- Extensions ------------------------------------------------------------
foreach (array('json', 'mbstring', 'pdo', 'pdo_mysql', 'session', 'zlib') as $ext) {
    echo str_pad('ext ' . $ext, 19) . ': ' . (extension_loaded($ext) ? 'yes' : 'NO  <-- required') . "\n";
}
echo str_pad('ext zip', 19) . ': ' . (extension_loaded('zip') ? 'yes' : 'no  (optional, .docx import only)') . "\n";
echo $rule . "\n";

// --- Functions a shared host may remove ------------------------------------
$disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
echo "disabled functions : " . ($disabled ? implode(', ', $disabled) : 'none') . "\n";
foreach (array('putenv', 'getenv', 'curl_init', 'file_get_contents') as $fn) {
    echo str_pad('fn ' . $fn, 19) . ': ' . (function_exists($fn) ? 'available' : 'DISABLED') . "\n";
}
echo str_pad('allow_url_fopen', 19) . ': ' . (ini_get('allow_url_fopen') ? 'on' : 'off') . "\n";
echo $rule . "\n";

// --- Files -----------------------------------------------------------------
$root = dirname(__DIR__);
echo "app root           : " . $root . "\n";
foreach (array('.env', '.htaccess', 'config/app.php', 'app/Core/App.php', 'public/index.php', 'storage') as $rel) {
    $path = $root . '/' . $rel;
    echo str_pad($rel, 19) . ': ' . (file_exists($path) ? 'found' : 'MISSING') . "\n";
}
foreach (array('cache', 'logs', 'pdfs', 'uploads') as $dir) {
    $path = $root . '/storage/' . $dir;
    $state = !is_dir($path) ? 'MISSING' : (is_writable($path) ? 'writable' : 'NOT WRITABLE');
    echo str_pad('storage/' . $dir, 19) . ': ' . $state . "\n";
}
echo $rule . "\n";

// --- .env ------------------------------------------------------------------
$envPath = $root . '/.env';
if (!is_file($envPath)) {
    echo ".env               : MISSING at " . $envPath . "\n";
    echo "It belongs beside app/, not inside public/.\n";
    echo $rule . "\nStop here and fix that first.\n";
    exit;
}
if (!is_readable($envPath)) {
    echo ".env               : found but NOT READABLE (check its permissions)\n";
    echo $rule . "\nStop here and fix that first.\n";
    exit;
}

$values = array();
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $row) {
    $row = trim($row);
    if ($row === '' || $row[0] === '#' || strpos($row, '=') === false) {
        continue;
    }
    list($k, $v) = array_map('trim', explode('=', $row, 2));
    $values[$k] = $v;
}
echo ".env               : parsed, " . count($values) . " settings\n";

// Report presence and shape, never the secret itself.
foreach (array('APP_ENV', 'APP_URL', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME') as $k) {
    echo str_pad($k, 19) . ': ' . (isset($values[$k]) && $values[$k] !== '' ? $values[$k] : 'EMPTY OR MISSING') . "\n";
}
foreach (array('APP_KEY', 'DB_PASSWORD', 'MAIL_API_KEY') as $k) {
    $set = isset($values[$k]) && $values[$k] !== '';
    echo str_pad($k, 19) . ': ' . ($set ? 'set (' . strlen($values[$k]) . ' chars)' : 'EMPTY OR MISSING') . "\n";
}
foreach ($values as $k => $v) {
    if (strpos($v, 'PASTE_') === 0 || strpos($v, 'REPLACE') !== false) {
        echo "!! " . $k . " still holds a placeholder\n";
    }
}
echo $rule . "\n";

// --- Database --------------------------------------------------------------
if (!extension_loaded('pdo_mysql')) {
    echo "database           : cannot test, pdo_mysql missing\n";
} else {
    $dsn = 'mysql:host=' . (isset($values['DB_HOST']) ? $values['DB_HOST'] : '')
        . ';port=' . (isset($values['DB_PORT']) ? $values['DB_PORT'] : '3306')
        . ';dbname=' . (isset($values['DB_DATABASE']) ? $values['DB_DATABASE'] : '')
        . ';charset=utf8mb4';
    try {
        $pdo = new PDO(
            $dsn,
            isset($values['DB_USERNAME']) ? $values['DB_USERNAME'] : '',
            isset($values['DB_PASSWORD']) ? $values['DB_PASSWORD'] : '',
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8)
        );
        echo "database           : connected, MySQL " . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";
        $count = $pdo->query('SELECT COUNT(*) FROM resume_templates')->fetchColumn();
        echo "templates seeded   : " . $count . "\n";
    } catch (Exception $e) {
        echo "database           : FAILED\n  " . $e->getMessage() . "\n";
    }
}
echo $rule . "\n";

// --- Booting the real application ------------------------------------------
// This is the part that produces the 500, so run it last and report whatever
// it throws instead of letting the host swallow it.
echo "Loading the application...\n";
// This report has already printed, so the session start inside the bootstrap
// will warn about headers being sent. That warning is caused by this file and
// says nothing about the deployment, so only real errors are shown from here.
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
try {
    require_once $root . '/config/app.php';
    echo "config/app.php     : loaded\n";
    echo "APP_ENV seen by app: " . (defined('APP_ENV') ? APP_ENV : 'undefined') . "\n";
    echo "BASE_URL seen      : " . (defined('BASE_URL') ? BASE_URL : 'undefined') . "\n";
    echo "env() reads .env   : " . (function_exists('env') && env('DB_DATABASE', '') !== '' ? 'yes' : 'NO  <-- putenv may be disabled') . "\n";
    echo "\nNo fatal error. The application bootstraps correctly.\n";
} catch (Throwable $e) {
    echo "\nFATAL while booting:\n";
    echo "  " . get_class($e) . ': ' . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ':' . $e->getLine() . "\n";
}

echo $rule . "\nDelete public/selftest.php when the site works.\n";
