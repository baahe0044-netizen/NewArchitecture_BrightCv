<?php

declare(strict_types=1);

final class Router
{
    private array $routes = [];

    public function get(string $path, string|callable $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    public function post(string $path, string|callable $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    public function put(string $path, string|callable $action, array $middleware = []): void
    {
        $this->add('PUT', $path, $action, $middleware);
    }

    public function delete(string $path, string|callable $action, array $middleware = []): void
    {
        $this->add('DELETE', $path, $action, $middleware);
    }

    public function add(string $method, string $path, string|callable $action, array $middleware = []): void
    {
        $normalized = '/' . trim($path, '/');
        $this->routes[] = compact('method', 'normalized', 'action', 'middleware');
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $parameterNames = [];
            $pattern = preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                static function (array $matches) use (&$parameterNames): string {
                    $parameterNames[] = $matches[1];
                    return '([^/]+)';
                },
                $route['normalized']
            );

            if (!preg_match('#^' . $pattern . '$#', $request->path(), $matches)) {
                continue;
            }

            array_shift($matches);
            $parameters = [];
            foreach ($parameterNames as $index => $name) {
                $parameters[$name] = rawurldecode($matches[$index] ?? '');
            }

            $guard = $this->runMiddleware($route['middleware'], $request);
            if ($guard instanceof Response) {
                return $guard;
            }

            return $this->invoke($route['action'], $request, $parameters);
        }

        return $request->expectsJson()
            ? Response::json(['success' => false, 'message' => 'Endpoint not found.'], 404)
            : Response::view('errors/404', [], 404);
    }

    private function runMiddleware(array $middleware, Request $request): ?Response
    {
        foreach ($middleware as $name) {
            $class = match ($name) {
                'auth' => AuthMiddleware::class,
                'guest' => GuestMiddleware::class,
                'admin' => AdminMiddleware::class,
                'csrf' => CsrfMiddleware::class,
                default => null,
            };

            if ($class === null) {
                throw new RuntimeException('Unknown middleware: ' . $name);
            }

            $response = (new $class())->handle($request);
            if ($response instanceof Response) {
                return $response;
            }
        }

        return null;
    }

    private function invoke(string|callable $action, Request $request, array $parameters): Response
    {
        if (is_callable($action)) {
            $response = $action($request, ...array_values($parameters));
        } else {
            [$controllerName, $method] = explode('@', $action, 2);
            $controller = new $controllerName();
            $response = $controller->{$method}($request, ...array_values($parameters));
        }

        if ($response instanceof Response) {
            return $response;
        }

        if (is_array($response)) {
            return Response::json($response);
        }

        return new Response((string) $response);
    }
}
