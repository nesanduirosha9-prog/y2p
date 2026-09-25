<?php
// Public front-controller — the single entry point every request hits.
// 1. Turns on error display/reporting (development mode).
// 2. Loads bootstrap.php (autoloader + config.php).
// 3. Creates the Application + Router.
// 4. Registers every route, grouped by area below:
//    Home -> Auth -> Dashboard -> Canonical resource routes -> Legacy shims.
// 5. Hands control to $app->run(), which resolves the current request.
//
// Routes are canonical and role-free: no path contains the words `instructor`,
// `coordinator` or `in-charge`. The role lives in $_SESSION, so it decides
// authorization and which view renders — never which URL you visit. The old
// role-prefixed paths are kept alive as shims at the bottom of this file.
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../bootstrap.php';

use app\core\Application;
use app\core\Router;
use app\core\Request;
use app\core\Response;
use app\controllers\HomeController;
use app\controllers\AuthController;
use app\controllers\SettingsController;
use app\controllers\WorkloadController;
use app\controllers\EvaluationsController;
use app\controllers\TimetableController;
use app\controllers\MessagesController;
use app\controllers\NotificationsController;
use app\controllers\AuditController;

// Several resources have one controller per role, so the class names collide
// (there are two TimetableControllers, three EvaluationsControllers, ...).
// Alias every import by role: it keeps the dispatch closures below readable and
// puts every fully-qualified class name in this one block, which matters because
// the autoloader maps namespace straight to file path — moving a controller in
// Phase 3 means editing its `namespace` line and these lines together.
use app\controllers\timetable_officer\CoursesController as OfficerCoursesController;
use app\controllers\timetable_officer\LecturersController;
use app\controllers\timetable_officer\LectureHallsController;
use app\controllers\timetable_officer\TimetableSessionsController;

use app\controllers\instructor\CoursesController as StaffCoursesController;
use app\controllers\instructor\WorkloadController as StaffWorkloadController;
use app\controllers\instructor\LeaveController;
use app\controllers\instructor\RequestsController;

use app\controllers\coordinator\StaffController;

use app\controllers\in_charge\AccountsController;

// Create the application and router
$app = new Application(dirname(__DIR__));
$router = new Router();

// Define routes. Callbacks receive (Request, Response) and may return string
$router->get('/', function (Request $request, Response $response) {
    if (isset($_SESSION['staff_code'])) {
        $response->redirect((new AuthController())->dashboardUrlForRole($_SESSION['role']));
        return;
    }
    $response->redirect('/login');
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
$router->post('/forgot-password/send-otp', function (Request $request, Response $response) {
    return (new AuthController())->sendOtp($request, $response);
});
$router->post('/forgot-password/verify-otp', function (Request $request, Response $response) {
    return (new AuthController())->verifyOtp($request, $response);
});

$router->get('/logout', function (Request $request, Response $response) {
    (new AuthController())->logout();
});

// Dashboard: redirects based on role in one hop
$router->get('/dashboard', function (Request $request, Response $response) {
    if (isset($_SESSION['staff_code'])) {
        $response->redirect((new AuthController())->dashboardUrlForRole($_SESSION['role'] ?? ''));
        return;
    }
    $response->redirect('/login');
});

// Example legacy redirect handler
$router->get('/oldabout', function (Request $request, Response $response) {
    (new HomeController())->oldAbout($response);
});

// ===========================================================================
// CANONICAL RESOURCE ROUTES
//
// One URL per resource, no role name in any of them. Where a resource has a
// different screen per role, the closure picks the controller out of $_SESSION
// and the controller's own guard (Controller::requireRole / requirePosition)
// decides whether to allow it.
//
// Read the `else` branch of each dispatch as the *stricter* of the two: a user
// who fits neither branch lands on the controller whose guard rejects them, and
// gets the normal 403 page from the normal guard path. That is deliberate —
// Controller::forbidden() is protected, so a bare closure here could not render
// a 403 itself even if it wanted to.
// ===========================================================================

// --- Timetable -------------------------------------------------------------
// GET /timetable — one weekly grid. The officer's view can edit it; academic
// staff get a read-only version. One controller picks the view by role.
$router->get('/timetable', function (Request $request, Response $response) {
    return (new TimetableController())->index($request);
});
$router->post('/timetable/schedule', function (Request $request, Response $response) {
    return (new TimetableController())->schedule($request);
});
$router->post('/timetable/scheduleRequest', function (Request $request, Response $response) {
    return (new TimetableController())->scheduleRequest($request);
});
$router->post('/timetable/updateScheduleRequest', function (Request $request, Response $response) {
    return (new TimetableController())->updateScheduleRequest($request);
});
$router->post('/timetable/deleteScheduleRequest', function (Request $request, Response $response) {
    return (new TimetableController())->deleteScheduleRequest($request);
});
// Officer-only writes behind the grid (JSON, js/timetable.js). The URL carries
// a session's ORIGINAL key (room/day/hour), since an edit may move it. Five
// segments, so nothing else can shadow these in the {param} loop.
$router->post('/timetable/sessions', function (Request $request, Response $response) {
    return (new TimetableSessionsController())->store($request, $response);
});
$router->put('/timetable/sessions/{room}/{day}/{hour}', function (Request $request, Response $response, array $params) {
    return (new TimetableSessionsController())->update($request, $response, $params);
});
$router->delete('/timetable/sessions/{room}/{day}/{hour}', function (Request $request, Response $response, array $params) {
    return (new TimetableSessionsController())->destroy($request, $response, $params);
});

// --- Courses ---------------------------------------------------------------
// GET /courses — "Course Details" (the whole catalogue) for an officer,
// "My Courses" (just yours) for academic staff.
$router->get('/courses', function (Request $request, Response $response) {
    if (($_SESSION['role'] ?? '') === 'academic_staff') {
        return (new StaffCoursesController())->index($request);
    }
    return (new OfficerCoursesController())->index($request);
});
// Officer-only writes behind the Course Details drawer (JSON, js/courses.js).
// Different verbs from the GET above, so no dispatch is needed — the
// controller's guardJson() rejects every other role.
$router->post('/courses', function (Request $request, Response $response) {
    return (new OfficerCoursesController())->store($request, $response);
});
$router->put('/courses/{code}', function (Request $request, Response $response, array $params) {
    return (new OfficerCoursesController())->update($request, $response, $params);
});
$router->delete('/courses/{code}', function (Request $request, Response $response, array $params) {
    return (new OfficerCoursesController())->destroy($request, $response, $params);
});

// --- Staff -----------------------------------------------------------------
// GET /staff — two genuinely different screens behind one URL: the officer's
// read-only staff directory, and the Coordinator/In-Charge registration
// approval queue. A junior academic staff member has neither, so they fall
// through to StaffController and its position guard answers 403.
$router->get('/staff', function (Request $request, Response $response) {
    if (($_SESSION['role'] ?? '') === 'timetable_officer') {
        return (new LecturersController())->index($request);
    }
    return (new StaffController())->index($request);
});
// These are POST-only and /staff is GET-only, so they cannot collide with it
// however the router orders its {param} loop. /staff/create is two segments
// and the {code} routes are three, so those cannot collide either.
$router->post('/staff/create', function (Request $request, Response $response) {
    return (new StaffController())->create($request, $response);
});
$router->post('/staff/{code}/approve', function (Request $request, Response $response, array $params) {
    return (new StaffController())->approve($request, $response, $params);
});
$router->post('/staff/{code}/reject', function (Request $request, Response $response, array $params) {
    return (new StaffController())->reject($request, $response, $params);
});

// --- Lecture halls ---------------------------------------------------------
// Already role-free before this refactor; timetable officer only. Full CRUD:
// the writes answer JSON and are called by js/lecture_halls.js.
$router->get('/lecture-halls', function (Request $request, Response $response) {
    return (new LectureHallsController())->index($request);
});
$router->post('/lecture-halls', function (Request $request, Response $response) {
    return (new LectureHallsController())->store($request, $response);
});
$router->put('/lecture-halls/{code}', function (Request $request, Response $response, array $params) {
    return (new LectureHallsController())->update($request, $response, $params);
});
$router->delete('/lecture-halls/{code}', function (Request $request, Response $response, array $params) {
    return (new LectureHallsController())->destroy($request, $response, $params);
});

// --- Workload --------------------------------------------------------------
// ORDERING: Router::resolve() tries an exact path match before it loops the
// {param} routes, so these three literals can never be shadowed. If a
// /workload/{something} route is ever added, register it BELOW these anyway —
// the {param} loop runs in registration order, and once two {param} routes
// compete that order is the only thing keeping them apart.
$router->get('/workload', function (Request $request, Response $response) {
    return (new StaffWorkloadController())->index($request);
});
// The Coordinator and the In-Charge both see the same matrix with different
// wording; one controller now serves both and picks the copy by position.
$router->get('/workload/distribution', function (Request $request, Response $response) {
    return (new WorkloadController())->distribution($request);
});
// The Duty Scheduler is now a set of tabs on the Workload page.
$router->get('/workload/scheduler', function (Request $request, Response $response) {
    $response->redirect('/workload/distribution?tab=week');
});

// --- Evaluations -----------------------------------------------------------
// Three readings of the same idea, chosen inside the controller by position:
// the Coordinator reviews, the In-Charge appraises, and everyone else (senior
// or junior academic staff) evaluates per course and is sent to /courses.
$router->get('/evaluations', function (Request $request, Response $response) {
    return (new EvaluationsController())->index($request);
});

// --- Activity log ----------------------------------------------------------
// GET /audit    the department-wide log — Coordinator and In-Charge only, and
//               the two of them do NOT see the same rows (the Coordinator is
//               not shown the In-Charge's entries or those of another
//               coordinator). That filtering is done server-side inside
//               AuditPrototypeData::feed, not in the browser.
// GET /audit/me your own record — every signed-in role, reached from the
//               profile menu rather than the sidebar.
//
// ORDERING: /audit is one segment and /audit/me is two, so neither can shadow
// the other however the router loops. Both are GET-only; there is deliberately
// no POST, PUT or DELETE route here, because nothing may write to the log
// except the application itself, as a side effect of the action being recorded.
$router->get('/audit', function (Request $request, Response $response) {
    return (new AuditController())->index($request);
});
$router->get('/audit/me', function (Request $request, Response $response) {
    return (new AuditController())->mine($request);
});

// --- Messages --------------------------------------------------------------
// One screen for both roles, differing only in the seeded conversations and in
// whether group chats exist — the officer's are all direct messages, with the
// Coordinator and the In-Charge only. Branching lives in the controller.
$router->get('/messages', function (Request $request, Response $response) {
    return (new MessagesController())->index($request);
});

// --- Academic staff screens with no officer equivalent ---------------------
$router->get('/leave', function (Request $request, Response $response) {
    return (new LeaveController())->index($request);
});
$router->get('/requests', function (Request $request, Response $response) {
    return (new RequestsController())->index($request);
});

// --- Notifications ---------------------------------------------------------
// JSON only — the feed itself is rendered inline on every dashboard page by
// views/components/notifications.php. These two just persist "seen", so the
// bell badge survives a reload. Open to every signed-in role; both are scoped
// to the caller's own staff_code inside the controller.
//
// ORDERING: /notifications/read-all is two segments and /notifications/{id}/read
// is three, so the {param} loop cannot shadow the literal whatever the order.
$router->post('/notifications/read-all', function (Request $request, Response $response) {
    return (new NotificationsController())->markAllRead($request, $response);
});
$router->post('/notifications/{id}/read', function (Request $request, Response $response, array $params) {
    return (new NotificationsController())->markRead($request, $response, $params);
});

// --- Settings --------------------------------------------------------------
// The only screen open to every signed-in role, and now served by a single
// controller — there is nothing to dispatch on. The update handler is held in
// a variable because the legacy POST /instructor/settings shim reuses it
// verbatim; see the shim block for why it cannot simply redirect.
$settingsUpdate = function (Request $request, Response $response) {
    return (new SettingsController())->update($request, $response);
};
$router->get('/settings', function (Request $request, Response $response) {
    return (new SettingsController())->index($request);
});
$router->post('/settings', $settingsUpdate);

// --- Settings > Handover ---------------------------------------------------
// Reassigning a key role (Coordinator, In-Charge, Timetable Officer) to another
// staff member. It lives under /settings because it is rendered as a tab of the
// Settings screen, not as a page of its own.
//
// /settings/handover therefore has no page to show: it bounces to that tab,
// exactly as the old /in-charge/accounts route did.
$router->get('/settings/handover', function (Request $request, Response $response) {
    $response->redirect('/settings#handover');
});
$router->get('/settings/handover/change/{position}/{code}', function (Request $request, Response $response, array $params) {
    return (new AccountsController())->change($request, $response, $params);
});
// "Add coordinator" — the same select/verify steps with nobody replaced.
$router->get('/settings/handover/add/{position}', function (Request $request, Response $response, array $params) {
    return (new AccountsController())->add($request, $response, $params);
});
// The Change / Add panel on the Settings tab loads its candidate list from here.
$router->get('/settings/handover/candidates/{position}', function (Request $request, Response $response, array $params) {
    return (new AccountsController())->candidates($request, $response, $params);
});
$router->post('/settings/handover/revoke', function (Request $request, Response $response) {
    return (new AccountsController())->revoke($request, $response);
});
$router->get('/settings/handover/select/{position}/{code}', function (Request $request, Response $response, array $params) {
    return (new AccountsController())->selectView($request, $response, $params);
});
$router->post('/settings/handover/select', function (Request $request, Response $response) {
    return (new AccountsController())->selectSubmit($request, $response);
});
$router->get('/settings/handover/verify', function (Request $request, Response $response) {
    return (new AccountsController())->verifyView($request);
});
$router->post('/settings/handover/verify', function (Request $request, Response $response) {
    return (new AccountsController())->verifySubmit($request, $response);
});
$router->get('/settings/handover/updated', function (Request $request, Response $response) {
    return (new AccountsController())->updatedView($request);
});

// ===========================================================================
// LEGACY ROUTE SHIMS — role-prefixed URLs kept alive so in-flight branches and
// bookmarks keep working. Canonical routes are above. Delete this block once
// every branch has merged (tracked in docs/ROUTING_REFACTOR.md).
// ===========================================================================

// Plain GET redirects: old path => canonical path.
$legacyRedirects = [
    '/instructor/timetable'              => '/timetable',
    '/instructor/my-courses'             => '/courses',
    '/course-details'                    => '/courses',
    '/lecturers'                         => '/staff',
    '/coordinator/staff'                 => '/staff',
    '/instructor/workload'               => '/workload',
    '/coordinator/workload/distribution' => '/workload/distribution',
    '/in-charge/workload/distribution'   => '/workload/distribution',
    '/coordinator/workload/scheduler'    => '/workload/distribution?tab=week',
    '/instructor/evaluations'            => '/evaluations',
    '/coordinator/evaluations'           => '/evaluations',
    '/in-charge/evaluations'             => '/evaluations',
    '/instructor/leave'                  => '/leave',
    '/instructor/messages'               => '/messages',
    '/instructor/requests'               => '/requests',
    '/instructor/settings'               => '/settings',
    '/in-charge/accounts'                => '/settings/handover',
    '/in-charge/accounts/verify'         => '/settings/handover/verify',
    '/in-charge/accounts/updated'        => '/settings/handover/updated',
];
foreach ($legacyRedirects as $old => $new) {
    $router->get($old, function (Request $request, Response $response) use ($new) {
        $response->redirect($new);
    });
}

// Parameterised GET shims need their own closures, to rebuild the target path
// from the captured params. urlencode() because a staff code or position comes
// straight off the URL and goes straight back into one.
$router->get('/in-charge/accounts/change/{position}/{code}', function (Request $request, Response $response, array $params) {
    $response->redirect('/settings/handover/change/' . urlencode($params['position'] ?? '') . '/' . urlencode($params['code'] ?? ''));
});
$router->get('/in-charge/accounts/select/{position}/{code}', function (Request $request, Response $response, array $params) {
    $response->redirect('/settings/handover/select/' . urlencode($params['position'] ?? '') . '/' . urlencode($params['code'] ?? ''));
});

// POST shims must NOT redirect. A 302 answer to a POST makes the browser (and
// fetch()) re-issue the request as a GET with no body, so the form data would
// be silently dropped and the endpoint would 404 or misbehave. Each of these
// calls the same controller method as its canonical route instead — the old URL
// keeps working as a genuine alias, not as a redirect.
$router->post('/coordinator/staff/{code}/approve', function (Request $request, Response $response, array $params) {
    return (new StaffController())->approve($request, $response, $params);
});
$router->post('/coordinator/staff/{code}/reject', function (Request $request, Response $response, array $params) {
    return (new StaffController())->reject($request, $response, $params);
});
$router->post('/instructor/settings', $settingsUpdate);
$router->post('/in-charge/accounts/select', function (Request $request, Response $response) {
    return (new AccountsController())->selectSubmit($request, $response);
});
$router->post('/in-charge/accounts/verify', function (Request $request, Response $response) {
    return (new AccountsController())->verifySubmit($request, $response);
});

$app->useRouter($router);
$app->run();
