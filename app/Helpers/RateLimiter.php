<?php

declare(strict_types=1);

final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maximum): bool
    {
        $attempt = self::attempt($key);
        if ($attempt === null) {
            return false;
        }

        if ((int) $attempt['expired'] === 1) {
            self::clear($key);
            return false;
        }

        return (int) $attempt['attempts'] >= $maximum;
    }

    public static function hit(string $key, int $decaySeconds): void
    {
        $statement = Database::getConnection()->prepare(
            'INSERT INTO rate_limits (key_hash, attempts, expires_at, updated_at)
             VALUES (?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())
             ON DUPLICATE KEY UPDATE
                attempts = IF(expires_at <= NOW(), 1, attempts + 1),
                expires_at = IF(expires_at <= NOW(), VALUES(expires_at), expires_at),
                updated_at = NOW()'
        );
        $statement->execute([self::hash($key), max(1, $decaySeconds)]);
    }

    public static function clear(string $key): void
    {
        $statement = Database::getConnection()->prepare('DELETE FROM rate_limits WHERE key_hash = ?');
        $statement->execute([self::hash($key)]);
    }

    public static function availableIn(string $key): int
    {
        $attempt = self::attempt($key);
        return $attempt === null
            ? 0
            : max(0, (int) $attempt['remaining_seconds']);
    }

    private static function attempt(string $key): ?array
    {
        $statement = Database::getConnection()->prepare(
            'SELECT attempts,
                    GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), expires_at)) AS remaining_seconds,
                    expires_at <= NOW() AS expired
             FROM rate_limits WHERE key_hash = ? LIMIT 1'
        );
        $statement->execute([self::hash($key)]);
        return $statement->fetch() ?: null;
    }

    private static function hash(string $key): string
    {
        return hash_hmac('sha256', $key, (string) env('APP_KEY', APP_NAME));
    }
}
