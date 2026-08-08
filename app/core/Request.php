<?php

namespace app\core;

class Request
{

    // Return the URI path only, without query string
    public function getPath(): string
    {
        // $path = $_SERVER['REQUEST_URI'] ?? '/';
        // $position = strpos($path, '?');
        // if ($position === false) {
        //     return $path;
        // }
        // return substr($path, 0, $position);
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

    // Get all POST data, or a single field by key
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
        return $body;
    }

    // Get query string parameters ($_GET)
    public function getQueryParams(): array
    {
        return $_GET;
    }
}
