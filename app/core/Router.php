<?php

namespace app\core;

use app\controllers\HomeController;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $callback): void
    {
        $this->routes['get'][$path] = $callback;
    }

    public function resolve(Request $request, Response $response)
    {
        // $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        // $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $request->getPath();
        $method = $request->getMethod();
        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            $response->setStatusCode(404);
            return (new HomeController())->notFound();
        }
        return call_user_func($callback, $request, $response);
    }
}
