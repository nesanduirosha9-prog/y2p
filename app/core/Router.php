<?php

namespace app\core;

use app\controllers\HomeController;

// Router: matches incoming requests to registered callbacks.
// Supports simple HTTP method buckets and parameterized routes like `/user/{id}`.
class Router
{
    // routes['get']['/path'] = callable
    private array $routes = [];

    // Register a GET route
    public function get(string $path, callable $callback): void
    {
        $this->routes['get'][$path] = $callback;
    }

    public function post(string $path, callable $callback): void
    {
        $this->routes['post'][$path] = $callback;
    }

    public function put(string $path, callable $callback): void
    {
        $this->routes['put'][$path] = $callback;
    }

    public function delete(string $path, callable $callback): void
    {
        $this->routes['delete'][$path] = $callback;
    }

    public function resolve(Request $request, Response $response)
    {
        // $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        // $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $request->getPath();
        $method = $request->getMethod();
        $callback = $this->routes[$method][$path] ?? false;

        // Exact match first
        if ($callback !== false) {
            return call_user_func($callback, $request, $response);
        }

        // Try route parameter matching (e.g. /user/{id})
        if (!empty($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $cb) {
                // Convert `{param}` to named regex groups `(?P<param>[^/]+)`
                $pattern = preg_replace('#\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}#', '(?P<\\1>[^/]+)', $route);
                $pattern = '#^' . $pattern . '$#';
                if (preg_match($pattern, $path, $matches)) {
                    // Extract named params from regex matches
                    $params = [];
                    foreach ($matches as $key => $val) {
                        if (!is_int($key)) {
                            $params[$key] = $val;
                        }
                    }
                    // Store params on the Request so controllers can read them
                    if (method_exists($request, 'setRouteParams')) {
                        $request->setRouteParams($params);
                    }
                    // Pass params to callback as third arg for convenience
                    return call_user_func($cb, $request, $response, $params);
                }
            }
        }

        // Not found -> 404 page
        $response->setStatusCode(404);
        return (new HomeController())->notFound();
    }
}
