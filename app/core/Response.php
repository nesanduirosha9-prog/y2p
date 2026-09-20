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

    // Send arbitrary content with a status code and content type
    // Useful for building API or page responses programmatically.
    public function send(string $content, int $statusCode = 200, string $contentType = 'text/html'): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', $contentType);
        echo $content;
        // Caller may choose whether to exit; framework keeps control
    }

    // Convenience: set the Content-Type header
    public function setContentType(string $type): void
    {
        $this->setHeader('Content-Type', $type);
    }

    // Set a cookie on the response
    public function setCookie(string $name, string $value, int $expire = 0, string $path = '/', string $domain = ''): void
    {
        setcookie($name, $value, $expire, $path, $domain ?: '');
    }

    // Set an arbitrary header
    public function setHeader(string $name, string $value): void
    {
        header("{$name}: {$value}");
    }
}
