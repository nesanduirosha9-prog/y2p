<?php

namespace app\core;

// Request: encapsulates HTTP request data and provides helpers.
// - path, method, query and body parsing
// - stores route parameters extracted by Router
class Request
{
    // Parameters extracted from a parameterized route, e.g. ['id' => '42']
    private array $routeParams = [];

    // Return the URI path only, without query string
    public function getPath(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    // Return the HTTP method, e.g. GET, POST
    public function getMethod(): string
    {
        return strtolower($_SERVER['REQUEST_METHOD'] ?? 'get');
    }

    // Convenience checks
    public function isGet(): bool
    {
        return $this->getMethod() === 'get';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'post';
    }

    // Get request body data. For form posts it reads $_POST; for JSON
    // requests it will parse php://input and merge results.
    public function getBody(): array
    {
        $body = [];
        if ($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        if ($this->isPost()) {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        // If content-type is JSON, parse raw body and merge into $body
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $body = array_merge($body, $json);
            }
        }

        return $body;
    }

    // Get query string parameters ($_GET)
    public function getQueryParams(): array
    {
        return $_GET;
    }

    // Called by Router when a parameterized route matches
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    // Retrieve a single route parameter by name
    public function getRouteParam(string $name, $default = null)
    {
        return $this->routeParams[$name] ?? $default;
    }

    // Return HTTP request headers derived from the $_SERVER array
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}
