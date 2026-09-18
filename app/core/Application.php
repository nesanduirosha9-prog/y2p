<?php

namespace app\core;

// Application: central app container and lifecycle manager.
// Holds the global `app` instance, root dir, simple DI container,
// and coordinates request/response and router dispatch.
class Application
{
    // Router instance used to dispatch requests
    private Router $router;
    // Current Request and Response objects for the lifecycle
    private Request $request;
    private Response $response;
    // Minimal container for storing shared services/values
    private array $container = [];

    // Application root directory, used by views and other paths
    public static string $ROOT_DIR;
    // Global application instance (convenience for controllers/helpers)
    public static Application $app;

    public function __construct(string $rootdir)
    {
        self::$ROOT_DIR = $rootdir;
        // make the application globally available for quick access
        self::$app = $this;
    }

    public function useRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function set(string $key, $value): void
    {
        $this->container[$key] = $value;
    }

    public function get(string $key)
    {
        return $this->container[$key] ?? null;
    }

    public function run(): void
    {
        $this->request = new Request();
        $this->response = new Response();

        if (!isset($this->router)) {
            // No router configured — fatal for this framework
            http_response_code(500);
            // Inform developer that router must be registered before run()
            echo "Router not configured";
            return;
        }

        try {
            $result = $this->router->resolve($this->request, $this->response);
            // router may already echo or send response; if it returns content, echo it
            if (is_string($result)) {
                echo $result;
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            if (ini_get('display_errors')) {
                // In development, print the exception message for debugging
                echo "<h1>Application error</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            } else {
                echo "Internal Server Error";
            }
        }
    }
}
