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
        CONFIG_PATH,
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

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
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

define('APP_NAME', (string) env('APP_NAME', 'LunettiStar'));
define('APP_ENV', (string) env('APP_ENV', 'production'));
define('APP_DEBUG', (bool) env('APP_DEBUG', false));
define('BASE_URL', $configuredUrl);
define('BASE_PATH', rtrim($configuredPath, '/'));

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return BASE_URL . ($path === '' ? '' : '/' . ltrim($path, '/'));
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return base_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

Session::start();

set_exception_handler(static function (Throwable $exception): void {
    error_log(sprintf(
        '[%s] %s in %s:%d%s%s',
        date(DATE_ATOM),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        PHP_EOL,
        $exception->getTraceAsString()
    ));

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
