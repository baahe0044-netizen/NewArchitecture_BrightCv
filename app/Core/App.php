<?php

declare(strict_types=1);

final class App
{
    private readonly Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $routes = require CONFIG_PATH . '/routes.php';
        $routes($this->router);
    }

    public function run(): void
    {
        $this->sendSecurityHeaders();
        $this->router->dispatch(Request::capture())->send();
    }

    private function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), geolocation=(), payment=()');
        // style-src and font-src carry fonts.googleapis.com/fonts.gstatic.com
        // because head_meta.php (loaded on every page) actually requests the
        // Tinos stylesheet and its two woff2 files from there. Without these,
        // the CSP silently blocks that request on every single page load --
        // the Times New Roman fallback the request is designed to have would
        // fire on every visit, not just when a network genuinely blocks it.
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data:; font-src 'self' data: https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self'; connect-src 'self'; worker-src 'self'; manifest-src 'self'");
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Cache-Control: no-store, private');

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
