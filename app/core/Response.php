<?php

namespace app\core;

class Response
{

    // Set the HTTP status code for the current response
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    // Redirect the browser to another URL and stop execution
    public function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    // Send a JSON response with correct headers and stop execution
    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // Set an arbitrary header
    public function setHeader(string $name, string $value): void
    {
        header("{$name}: {$value}");
    }
}
