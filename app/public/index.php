<?php
require_once __DIR__ . '/../../bootstrap.php';

use app\core\Application;
use app\core\Router;
use app\controllers\HomeController;
use app\core\Request;
use app\core\Response;

$app = new Application(dirname(__DIR__));
$router = new Router();

$router->get('/', function (Request $request, Response $response) {
    $controller = new HomeController();
    return $controller->index();
});

$router->get('/about', function (Request $request, Response $response) {
    $controller = new HomeController();
    return $controller->about();
});

$router->get('/api/users', function (Request $request, Response $response) {
    (new HomeController())->usersJson($response);
});

$router->get('/oldabout', function (Request $request, Response $response) {
    (new HomeController())->oldAbout($response);
});
$app->useRouter($router);
$app->run();
