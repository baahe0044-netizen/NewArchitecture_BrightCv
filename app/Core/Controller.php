<?php

declare(strict_types=1);

abstract class Controller
{
    protected function view(string $view, array $data = [], int $status = 200): Response
    {
        return Response::view($view, $data, $status);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function success(array $data = [], string $message = 'Success', int $status = 200): Response
    {
        return $this->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    protected function error(string $message, int $status = 422, array $errors = []): Response
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status);
    }
}
