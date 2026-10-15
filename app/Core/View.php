<?php

declare(strict_types=1);

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $path = VIEW_PATH . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);
        require $path;
    }

    public static function partial(string $view, array $data = []): void
    {
        self::render($view, $data);
    }
}
