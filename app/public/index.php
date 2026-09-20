<?php
// Public front-controller — the single entry point every request hits.
// 1. Turns on error display/reporting (development mode).
// 2. Loads bootstrap.php (autoloader + config.php).
// 3. Creates the Application + Router.
// 4. Registers every route, grouped by area below:
//    Home -> Auth -> Dashboard -> Timetable Officer -> Instructor ->
//    Coordinator -> In-Charge.
// 5. Hands control to $app->run(), which resolves the current request.
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../bootstrap.php';

use app\core\Application;
use app\core\Router;
use app\controllers\HomeController;
use app\controllers\AuthController;
use app\controllers\timetable_officer\TimetableController;
use app\controllers\timetable_officer\CoursesController;
use app\controllers\timetable_officer\LecturersController;
use app\controllers\timetable_officer\LectureHallsController;

use app\controllers\timetable_officer\SettingsController;
use app\controllers\coordinator\StaffController;
use app\controllers\in_charge\AccountsController;
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
$router->get('/lecture-halls', function (Request $request, Response $response) {
    return (new LectureHallsController())->index($request);
});
$router->put('/lecture-halls/{code}', function (Request $request, Response $response, array $params) {
    return (new LectureHallsController())->update($request, $response, $params);
});

$router->get('/settings', function (Request $request, Response $response) {
    return (new SettingsController())->index($request);
});

// API route returning JSON
$router->get('/api/users', function (Request $request, Response $response) {
    (new HomeController())->usersJson($response);
});

// Example legacy redirect handler
$router->get('/oldabout', function (Request $request, Response $response) {
    (new HomeController())->oldAbout($response);
});

// Instructor Routes
$router->get('/instructor/timetable', function (Request $request, Response $response) {
    return (new \app\controllers\instructor\TimetableController())->index($request);
});
$router->get('/instructor/workload', function (Request $request, Response $response) {
    return (new \app\controllers\instructor\WorkloadController())->index($request);
});
$router->get('/instructor/requests', function (Request $request, Response $response) {
    return (new \app\controllers\instructor\RequestsController())->index($request);
});
$router->get('/instructor/leave', function (Request $request, Response $response) {
    return (new \app\controllers\instructor\LeaveController())->index($request);
});
$router->get('/instructor/messages', function (Request $request, Response $response) {
    return (new \app\controllers\instructor\MessagesController())->index($request);
});
$router->get('/instructor/settings', function (Request $request, Response $response) {
    return (new \app\controllers\instructor\SettingsController())->index($request);
});

// Coordinator Routes (also reachable by In-Charge, which carries every
// coordinator ability plus its own Accounts screen below)
$router->get('/coordinator/staff', function (Request $request, Response $response) {
    return (new StaffController())->index($request);
});
$router->post('/coordinator/staff/{code}/approve', function (Request $request, Response $response, array $params) {
    return (new StaffController())->approve($request, $response, $params);
});
$router->post('/coordinator/staff/{code}/reject', function (Request $request, Response $response, array $params) {
    return (new StaffController())->reject($request, $response, $params);
});

// In-Charge Routes
$router->get('/in-charge/accounts', function (Request $request, Response $response) {
    return (new AccountsController())->index($request);
});
$router->get('/in-charge/accounts/change/{position}/{code}', function (Request $request, Response $response, array $params) {
    return (new AccountsController())->change($request, $response, $params);
});
$router->get('/in-charge/accounts/select/{position}/{code}', function (Request $request, Response $response, array $params) {
    return (new AccountsController())->selectView($request, $response, $params);
});
$router->post('/in-charge/accounts/select', function (Request $request, Response $response) {
    return (new AccountsController())->selectSubmit($request, $response);
});
$router->get('/in-charge/accounts/verify', function (Request $request, Response $response) {
    return (new AccountsController())->verifyView($request);
});
$router->post('/in-charge/accounts/verify', function (Request $request, Response $response) {
    return (new AccountsController())->verifySubmit($request, $response);
});
$router->get('/in-charge/accounts/updated', function (Request $request, Response $response) {
    return (new AccountsController())->updatedView($request);
});

$app->useRouter($router);
$app->run();
