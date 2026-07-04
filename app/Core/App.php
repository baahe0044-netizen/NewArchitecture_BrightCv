<?php

require_once __DIR__ . '/Router.php';

class App
{
    protected Router $router;

    public function __construct()
    {
        $this->router = new Router();

        $routes = require CONFIG_PATH . '/routes.php';

        $this->router->load($routes);
    }

    public function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // remove base folder (/LunettiStar/public)
        $uri = str_replace(BASE_URL, '', $uri);

        $this->router->direct($uri);
    }
}
