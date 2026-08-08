<?php

namespace app\core;

class Application
{
    private Router $router;
    private Request $request;
    private Response $response;

    public static string $ROOT_DIR;

    public function __construct(string $rootdir)
    {
        // $this->router = new Router();
        // $this->request = new Request();
        self::$ROOT_DIR = $rootdir;
    }


    public function useRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function run(): void
    {
        $this->request = new Request();
        $this->response = new Response();
        echo $this->router->resolve($this->request, $this->response);
    }
}
