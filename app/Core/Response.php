<?php

declare(strict_types=1);

final class Response
{
    private array $headers = [];

    public function __construct(
        private readonly string $content = '',
        private readonly int $status = 200
    ) {
    }

    public static function json(array $data, int $status = 200): self
    {
        $response = new self(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $status
        );
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return (new self('', $status))->withHeader('Location', $url);
    }

    public static function view(string $view, array $data = [], int $status = 200): self
    {
        ob_start();
        View::render($view, $data);
        return new self((string) ob_get_clean(), $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        echo $this->content;
    }
}
