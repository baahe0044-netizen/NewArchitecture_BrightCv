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

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

// Show what is going wrong instead of a blank 500.
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// The report is written as plain text and wrapped at the end, so a section
// that stops early with exit() still produces a readable page.
ob_start();
register_shutdown_function(function () use ($TOKEN) {
    $report = ob_get_clean();
    $safe = htmlspecialchars($report, ENT_QUOTES, 'UTF-8');
    $action = htmlspecialchars(
        strtok((string) $_SERVER['REQUEST_URI'], '?') . '?token=' . $TOKEN,
        ENT_QUOTES,
        'UTF-8'
    );

    echo '<!doctype html><meta charset="utf-8"><title>BrightCV self test</title>';
    echo '<style>body{margin:0;padding:20px;background:#0d1117;color:#d6dde6;'
        . 'font:13px/1.55 ui-monospace,Consolas,monospace}pre{white-space:pre-wrap;margin:0 0 22px}'
        . 'form{padding:14px;background:#161d26;border:1px solid #2a3542;border-radius:8px;max-width:520px}'
        . 'h2{margin:0 0 6px;font-size:14px}p{margin:0 0 10px;color:#93a1b2}'
        . 'input{width:100%;padding:8px;margin-bottom:10px;font:inherit;color:#e6edf5;'
        . 'background:#0d1117;border:1px solid #2a3542;border-radius:5px;box-sizing:border-box}'
        . 'button{padding:8px 14px;font:inherit;color:#0d1117;background:#4fb3c2;border:0;'
        . 'border-radius:5px;cursor:pointer}</style>';
    echo '<pre>' . $safe . '</pre>';

    // Trying a password here beats editing .env, uploading it and reloading
    // for every guess. Nothing is written or logged; it is used for one
    // connection attempt and discarded with the request.
    echo '<form method="post" action="' . $action . '">'
        . '<h2>Try a database password</h2>'
        . '<p>Tests the connection with this password and the host, database and '
        . 'username already in your .env. Nothing is saved. When one works, put it '
        . 'in .env as DB_PASSWORD.</p>'
        . '<input type="password" name="try_password" placeholder="Paste a password to test" autocomplete="off">'
        . '<button type="submit">Test connection</button>'
        . '</form>';
});

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

// --- Inventory -------------------------------------------------------------
// A part-finished upload is the common failure here, and it looks identical
// to a broken app from the outside. Count what actually arrived.
$countFiles = function ($dir) use (&$countFiles) {
    if (!is_dir($dir)) {
        return -1;
    }
    $total = 0;
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        $total += is_dir($path) ? max(0, $countFiles($path)) : 1;
    }
    return $total;
};

echo "In htdocs          : " . implode(' ', array_values(array_diff(scandir($root), array('.', '..')))) . "\n\n";

// Counted from the upload package itself, so a short count means files are
// genuinely missing rather than my arithmetic being wrong.
$expected = array(
    'app' => 74, 'app/Controllers' => 9, 'app/Core' => 10, 'app/Helpers' => 3,
    'app/Middleware' => 4, 'app/Repositories' => 7, 'app/Services' => 16,
    'app/Views' => 24, 'config' => 3, 'database' => 2, 'public' => 30,
    'public/assets' => 25, 'storage' => 5,
);
foreach ($expected as $rel => $want) {
    $have = $countFiles($root . '/' . $rel);
    if ($have === -1) {
        echo str_pad($rel, 19) . ': DIRECTORY MISSING  (expected about ' . $want . " files)\n";
    } else {
        $flag = $have === 0 ? '  <-- empty' : ($have < $want * 0.7 ? '  <-- looks incomplete' : '');
        echo str_pad($rel, 19) . ': ' . $have . ' files (expected about ' . $want . ')' . $flag . "\n";
    }
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
    // A password submitted through the form is tried instead of the stored
    // one, so credentials can be checked without editing and re-uploading.
    $tried = isset($_POST['try_password']) && $_POST['try_password'] !== '';
    $password = $tried
        ? (string) $_POST['try_password']
        : (isset($values['DB_PASSWORD']) ? $values['DB_PASSWORD'] : '');

    if ($tried) {
        echo "testing            : the password you typed (" . strlen($password) . " chars), not the one in .env\n";
    }

    $dsn = 'mysql:host=' . (isset($values['DB_HOST']) ? $values['DB_HOST'] : '')
        . ';port=' . (isset($values['DB_PORT']) ? $values['DB_PORT'] : '3306')
        . ';dbname=' . (isset($values['DB_DATABASE']) ? $values['DB_DATABASE'] : '')
        . ';charset=utf8mb4';
    try {
        $pdo = new PDO(
            $dsn,
            isset($values['DB_USERNAME']) ? $values['DB_USERNAME'] : '',
            $password,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8)
        );
        echo "database           : connected, MySQL " . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";
        $count = $pdo->query('SELECT COUNT(*) FROM resume_templates')->fetchColumn();
        echo "templates seeded   : " . $count . "\n";
        if ($tried) {
            echo "\n  That password works. Put it in .env as DB_PASSWORD.\n";
        }
    } catch (Exception $e) {
        echo "database           : FAILED\n  " . $e->getMessage() . "\n";

        // The characters matter more than the value when a password has been
        // retyped or pasted through an editor that rewrites quotes.
        if ($password !== '') {
            $odd = preg_replace('/[A-Za-z0-9]/', '', $password);
            echo "  password length  : " . strlen($password) . "\n";
            echo "  non-alphanumeric : " . ($odd === '' ? 'none' : $odd) . "\n";
            if (trim($password) !== $password) {
                echo "  !! it has leading or trailing whitespace\n";
            }
        }
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

// --- Recent failures -------------------------------------------------------
// The whole point of this section: a 500 on one page says nothing, and the
// host's own error log is usually out of reach. Reproduce the failure, then
// reload this page and read what it recorded.
echo $rule . "\nRecent errors (storage/logs/error.log)\n" . $rule . "\n";
$errorLog = $root . '/storage/logs/error.log';
if (!is_file($errorLog)) {
    echo "No error log yet. Trigger the failure, then reload this page.\n";
} else {
    $lines = file($errorLog, FILE_IGNORE_NEW_LINES);
    $tail = array_slice($lines, -40);
    echo "(last " . count($tail) . " of " . count($lines) . " lines)\n\n";
    echo implode("\n", $tail) . "\n";
}

echo $rule . "\nDelete public/selftest.php when the site works.\n";
