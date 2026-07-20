<?php

declare(strict_types=1);

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        session_name((string) env('SESSION_NAME', 'lunettistar_session'));
        session_set_cookie_params([
            'lifetime' => (int) env('SESSION_LIFETIME', 7200),
            'path' => BASE_PATH !== '' ? BASE_PATH : '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;
        self::ageFlashData();
        self::enforceIdleTimeout();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash']['new'][$key] = $value;
    }

    public static function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash']['old'][$key] ?? $default;
        unset($_SESSION['_flash']['old'][$key]);
        return $value;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$started = false;
    }

    private static function ageFlashData(): void
    {
        unset($_SESSION['_flash']['old']);
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'] ?? [];
        $_SESSION['_flash']['new'] = [];
    }

    private static function enforceIdleTimeout(): void
    {
        $now = time();
        $lastActivity = (int) ($_SESSION['_last_activity'] ?? $now);
        $lifetime = max(300, (int) env('SESSION_LIFETIME', 7200));

        if (isset($_SESSION['user_id']) && ($now - $lastActivity) > $lifetime) {
            $_SESSION = [];
            self::regenerate();
        }

        $_SESSION['_last_activity'] = $now;
    }
}
