<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('CONFIG_PATH', ROOT_PATH . '/config');

if (is_file(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

spl_autoload_register(static function (string $class): void {
    $class = basename(str_replace('\\', '/', $class));
    $directories = [
        APP_PATH . '/Core',
        APP_PATH . '/Controllers',
        APP_PATH . '/Services',
        APP_PATH . '/Repositories',
        APP_PATH . '/Middleware',
        APP_PATH . '/Helpers',
    ];

    foreach ($directories as $directory) {
        $file = $directory . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

$environmentFile = ROOT_PATH . '/.env';
if (is_file($environmentFile)) {
    foreach (file($environmentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (getenv($key) !== false) {
            continue;
        }

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        // Shared hosting commonly disables putenv, and calling a disabled
        // function is a fatal error, not a warning -- it would take down every
        // request. The arrays below are what env() actually reads back, so
        // putenv is a convenience for anything else on the box, not a
        // requirement.
        if (function_exists('putenv')) {
            putenv($key . '=' . $value);
        }
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        // getenv() first, so a real environment variable still outranks .env.
        // Then the arrays the loader fills alongside it: shared hosting often
        // disables putenv(), and reading getenv() alone there would leave the
        // whole configuration invisible and every value silently defaulted --
        // no APP_KEY, and a database host of 127.0.0.1 that cannot answer.
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? false;
        }
        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

$detectedScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$detectedHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!preg_match('/^[a-zA-Z0-9.-]+(?::[0-9]{1,5})?$/', $detectedHost)) {
    $detectedHost = 'localhost';
}
$detectedScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/LunettiStar/public/index.php');
$detectedBasePath = rtrim(str_replace('/index.php', '', $detectedScript), '/');
$detectedUrl = $detectedScheme . '://' . $detectedHost . $detectedBasePath;
$configuredUrl = rtrim((string) env('APP_URL', $detectedUrl), '/');
$configuredPath = (string) parse_url($configuredUrl, PHP_URL_PATH);

define('APP_NAME', (string) env('APP_NAME', 'BrightCV'));
define('APP_ENV', (string) env('APP_ENV', 'production'));
define('APP_DEBUG', (bool) env('APP_DEBUG', false));
define('BASE_URL', $configuredUrl);
define('BASE_PATH', rtrim($configuredPath, '/'));

// The one place a support address is written down, so every "ask a real
// person" link in the app -- the dashboard, the builder, an error page --
// stays a mailto: to the same inbox rather than each screen inventing its own.
define('SUPPORT_EMAIL', (string) env('SUPPORT_EMAIL', 'support@brightcv.app'));

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return BASE_URL . ($path === '' ? '' : '/' . ltrim($path, '/'));
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $relative = ltrim($path, '/');
        $url = base_url('assets/' . $relative);

        // Cache-bust with the file's modification time. Apache serves these
        // static files directly, so without this a browser can keep using a
        // stale stylesheet or script long after a deployment.
        $file = PUBLIC_PATH . '/assets/' . $relative;
        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }

        return $url;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('human_time_ago')) {
    /** "2 hours ago", "3 days ago" — for the dashboard's "Edited ..." lines. */
    function human_time_ago(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return 'recently';
        }

        $seconds = time() - strtotime($datetime);
        if ($seconds < 60) {
            return 'moments ago';
        }

        $steps = [
            [31536000, 'year'], [2592000, 'month'], [604800, 'week'],
            [86400, 'day'], [3600, 'hour'], [60, 'minute'],
        ];
        foreach ($steps as [$unitSeconds, $label]) {
            $count = intdiv($seconds, $unitSeconds);
            if ($count >= 1) {
                return $count . ' ' . $label . ($count === 1 ? '' : 's') . ' ago';
            }
        }

        return 'moments ago';
    }
}

if (!function_exists('badge_icon')) {
    /**
     * The inline SVG shape for one earned badge, keyed the same as
     * GamificationService::badgesFor() names them. Icons are the reference
     * mockups' own shapes: stroke-based, rounded caps, no icon font.
     */
    function badge_icon(string $key): string
    {
        $paths = [
            'first-draft' => '<path d="M6 3h8l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M13 3v5h5"/>',
            'numbers-person' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
            'tight-summary' => '<path d="M4 7h16M4 12h11M4 17h7"/>',
            'five-days' => '<path d="M12 3c2.5 3 4.5 5 4.5 8a4.5 4.5 0 0 1-9 0c0-1.4.5-2.5 1.4-3.7.6 1 1.3 1.5 2.1 1.7C11.4 7 11.6 5 12 3z"/>',
            'first-download' => '<path d="M12 3v12"/><path d="m7 11 5 5 5-5"/><path d="M5 21h14"/>',
            'two-cvs' => '<rect x="4" y="4" width="7" height="7" rx="1.4"/><rect x="13" y="4" width="7" height="7" rx="1.4"/><rect x="4" y="13" width="7" height="7" rx="1.4"/><rect x="13" y="13" width="7" height="7" rx="1.4"/>',
        ];

        $body = $paths[$key] ?? $paths['first-draft'];
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
    }
}

Session::start();

set_exception_handler(static function (Throwable $exception): void {
    $entry = sprintf(
        '[%s] %s: %s in %s:%d%s%s',
        date(DATE_ATOM),
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        PHP_EOL,
        $exception->getTraceAsString()
    );

    error_log($entry);

    // error_log() goes wherever the host decided, which on shared plans is
    // often nowhere the person running the site can read. Keep a copy beside
    // the application so a failure can be diagnosed without a shell, and
    // without turning on debug output for every visitor.
    $logDirectory = STORAGE_PATH . '/logs';
    if (is_dir($logDirectory) && is_writable($logDirectory)) {
        @file_put_contents(
            $logDirectory . '/error.log',
            $entry . PHP_EOL . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $isJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_starts_with($requestPath, BASE_PATH . '/api/');

    if ($isJson) {
        Response::json([
            'success' => false,
            'message' => APP_DEBUG ? $exception->getMessage() : 'Something went wrong. Please try again.',
        ], 500)->send();
        return;
    }

    http_response_code(500);
    if (APP_DEBUG) {
        echo '<pre>' . e((string) $exception) . '</pre>';
        return;
    }

    View::render('errors/500', ['title' => 'Something went wrong']);
});
