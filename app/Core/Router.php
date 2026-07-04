<?php

class Router
{
    private array $routes = [];

    public function load(array $routes)
    {
        $this->routes = $routes;
    }

    public function direct($uri)
    {
        $uri = '/' . trim($uri, '/');

        if (!isset($this->routes[$uri])) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        return $this->callAction($this->routes[$uri]);
    }

    private function callAction($action)
    {
        [$controllerName, $method] = explode('@', $action);

        $controllerClass = $controllerName;

        require_once APP_PATH . "/Controllers/{$controllerClass}.php";

        $controller = new $controllerClass();

        return $controller->$method();
    }
}
