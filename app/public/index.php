<?php
// Public front-controller: boots the framework and registers routes.
require_once __DIR__ . '/../../bootstrap.php';

use app\core\Application;
use app\core\Router;
use app\controllers\HomeController;
use app\controllers\AuthController;
use app\controllers\TimetableController;
use app\controllers\CoursesController;
use app\controllers\LecturersController;
use app\controllers\NotificationsController;
use app\core\Request;
use app\core\Response;

// Create the application and router
$app = new Application(dirname(__DIR__));
$router = new Router();

// Define routes. Callbacks receive (Request, Response) and may return string
$router->get('/', function (Request $request, Response $response) {
    $controller = new HomeController();
    return $controller->index();
});

$router->get('/about', function (Request $request, Response $response) {
    $controller = new HomeController();
    return $controller->about();
});

// Authentication Routes
$router->get('/login', function (Request $request, Response $response) {
    return (new AuthController())->loginView();
});
$router->post('/login', function (Request $request, Response $response) {
    return (new AuthController())->login($request, $response);
});

$router->get('/signup', function (Request $request, Response $response) {
    return (new AuthController())->signupView();
});
$router->post('/signup', function (Request $request, Response $response) {
    return (new AuthController())->signup($request, $response);
});

$router->get('/forgot-password', function (Request $request, Response $response) {
    return (new AuthController())->forgotPasswordView();
});
$router->post('/forgot-password', function (Request $request, Response $response) {
    return (new AuthController())->resetPassword($request, $response);
});

$router->get('/logout', function (Request $request, Response $response) {
    (new AuthController())->logout();
});

// Dashboard: the officer's home is the Timetable view
$router->get('/dashboard', function (Request $request, Response $response) {
    $response->redirect('/timetable');
});

// Timetable Officer Routes
$router->get('/timetable', function (Request $request, Response $response) {
    return (new TimetableController())->index($request);
});
$router->get('/courses', function (Request $request, Response $response) {
    return (new CoursesController())->index($request);
});
$router->get('/lecturers', function (Request $request, Response $response) {
    return (new LecturersController())->index($request);
});
$router->get('/notifications', function (Request $request, Response $response) {
    return (new NotificationsController())->index($request);
});

// API route returning JSON
$router->get('/api/users', function (Request $request, Response $response) {
    (new HomeController())->usersJson($response);
});

// Example legacy redirect handler
$router->get('/oldabout', function (Request $request, Response $response) {
    (new HomeController())->oldAbout($response);
});

$app->useRouter($router);
$app->run();
