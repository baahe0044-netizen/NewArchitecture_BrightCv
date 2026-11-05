<?php

declare(strict_types=1);

final class Auth
{
    private static ?array $user = null;

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return is_numeric($id) ? (int) $id : null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }

        if (self::$user === null) {
            $user = (new UserRepository())->findById($id);
            $sessionVersion = (int) Session::get('auth_version', 0);
            if (!$user || $sessionVersion !== (int) $user['auth_version']) {
                Session::forget('user_id');
                Session::forget('auth_version');
                return null;
            }
            self::$user = $user;
        }
        return self::$user;
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::forget('_csrf_token');
        Session::put('user_id', (int) $user['id']);
        Session::put('auth_version', (int) ($user['auth_version'] ?? 1));
        Session::put('auth_time', time());
        Session::put('remember_requested', $remember);
        unset($user['password_hash']);
        self::$user = $user;
    }

    public static function logout(): void
    {
        self::$user = null;
        Session::destroy();
        Session::start();
        Session::regenerate();
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? 'user') === 'admin';
    }
}
