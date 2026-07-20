<?php

declare(strict_types=1);

final class Request
{
    private array $json = [];
    private bool $invalidJson = false;

    public function __construct(
        private readonly array $server,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $cookies
    ) {
        $contentType = strtolower($server['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $raw = (string) file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $this->json = is_array($decoded) ? $decoded : [];
            $this->invalidJson = trim($raw) !== ''
                && (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded));
        }
    }

    public static function capture(): self
    {
        return new self($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE);
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        $override = $this->header('X-HTTP-Method-Override') ?? $this->body['_method'] ?? null;
        return $override ? strtoupper((string) $override) : $method;
    }

    public function path(): string
    {
        $path = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (
            BASE_PATH !== ''
            && ($path === BASE_PATH || str_starts_with($path, BASE_PATH . '/'))
        ) {
            $path = substr($path, strlen(BASE_PATH)) ?: '/';
        }
        return '/' . trim($path, '/');
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $input = array_replace($this->query, $this->body, $this->json);
        return $key === null ? $input : ($input[$key] ?? $default);
    }

    public function only(array $keys): array
    {
        $input = $this->input();
        return array_intersect_key($input, array_flip($keys));
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_string($value) || is_numeric($value) ? (string) $value : $default;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->input($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value) || is_int($value)) {
            return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
        }
        return $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            ? $file
            : null;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function expectsJson(): bool
    {
        return str_contains($this->header('Accept') ?? '', 'application/json')
            || str_starts_with($this->path(), '/api/');
    }

    public function hasInvalidJson(): bool
    {
        return $this->invalidJson;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
