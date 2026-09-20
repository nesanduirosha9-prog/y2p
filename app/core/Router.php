<?php

namespace app\core;

use app\controllers\HomeController;

// Router: matches incoming requests to registered callbacks.
// Supports simple HTTP method buckets and parameterized routes like `/user/{id}`.
// 1. get()/post()/put()/delete() — register a callback under method+path.
// 2. resolve() — called once per request by Application::run():
//    a. exact path match first,
//    b. then `{param}` routes via regex,
//    c. falls back to HomeController::notFound() (404).
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
        $path = $request->getPath();
        $method = $request->getMethod();
        $callback = $this->routes[$method][$path] ?? false;

        // 1. Exact path match first (fast path, no regex)
        if ($callback !== false) {
            return call_user_func($callback, $request, $response);
        }

        // 2. Try route parameter matching (e.g. /user/{id})
        if (!empty($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $cb) {
                // 2a. Convert `{param}` to named regex groups `(?P<param>[^/]+)`
                $pattern = preg_replace('#\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}#', '(?P<\\1>[^/]+)', $route);
                $pattern = '#^' . $pattern . '$#';
                if (preg_match($pattern, $path, $matches)) {
                    // 2b. Extract only the named params from regex matches
                    $params = [];
                    foreach ($matches as $key => $val) {
                        if (!is_int($key)) {
                            $params[$key] = $val;
                        }
                    }
                    // 2c. Store params on the Request so controllers can read them
                    if (method_exists($request, 'setRouteParams')) {
                        $request->setRouteParams($params);
                    }
                    // 2d. Also pass params to the callback as a third arg
                    return call_user_func($cb, $request, $response, $params);
                }
            }
        }

        // 3. No route matched -> 404 page
        $response->setStatusCode(404);
        return (new HomeController())->notFound();
    }
}
