<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\NotificationModel;
use app\models\RoomModel;
use app\models\TimetableSessionModel;

// TimetableController: the weekly schedule grid at /timetable.
//
// Replaces instructor/TimetableController and
// timetable_officer/TimetableController, which parsed the same three filters
// with the same defaults and whitelists and called the same two models — they
// differed only in the role they guarded, four strings of page copy, and one
// extra query on the officer's side.
//
// The two views stay separate and genuinely differ: the officer's grid is
// editable (it assigns rooms and sessions to slots), the academic staff one is
// read-only. Only the controller merged.
//
// NOTE (pre-existing, unchanged): the staff view shows the whole department's
// grid, not just the sessions this person teaches.
class TimetableController extends Controller
{
    // View and page copy, keyed by $_SESSION['role']. Kept verbatim from the
    // two controllers this replaces — including css_file being a bare string
    // for the officer and an array for academic staff, which the dashboard
    // layout normalises with (array) either way.
    private const COPY = [
        'timetable_officer' => [
            'view'      => 'timetable_officer/timetable',
            'title'     => 'Timetable Management',
            'css_file'  => '/css/timetable_officer/timetable.css',
            'pageTitle' => 'Timetable Management',
        ],
        'academic_staff' => [
            'view'      => 'instructor/timetable',
            'title'     => 'My Timetable',
            'css_file'  => ['/css/timetable.css', '/css/instructor/timetable.css'],
            'pageTitle' => 'My Timetable',
        ],
    ];

    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    // public function index(Request $request)
    // {
    //     // Both roles may see a timetable; which one they see is decided below.
    //     // Anyone else (there is no third role today) gets the 403 page.
    //     $denied = $this->requireRole('academic_staff', 'timetable_officer');
    //     if ($denied !== null) {
    //         return $denied;
    //     }

    //     // The guard above already limited this to the two keys in self::COPY.
    //     $role = $_SESSION['role'];
    //     $copy = self::COPY[$role];

    //     // Read + validate the dept/semester/year filters from ?query.
    //     // $dept = $request->getQueryParams()['dept'] ?? 'cs';
    //     // $sem = (int)($request->getQueryParams()['sem'] ?? 1);
    //     // $year = (int)($request->getQueryParams()['year'] ?? 1);

    //     // $dept = in_array($dept, ['cs', 'is'], true) ? $dept : 'cs';
    //     // $sem = in_array($sem, [1, 2], true) ? $sem : 1;
    //     // $year = in_array($year, [1, 2, 3, 4], true) ? $year : 1;

    //     $params = [
    //         'title' => $copy['title'],
    //         'css_file' => $copy['css_file'],
    //         'active' => 'timetable',
    //         'pageTitle' => $copy['pageTitle'],
    //         'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
    //         'dept' => $dept,
    //         'sem' => $sem,
    //         'year' => $year,
    //         'courses' => (new CourseModel())->forDeptYear($dept, $year),
    //         'sessions' => (new TimetableSessionModel())->forDeptSemYear($dept, $sem, $year),
    //     ];

    //     // Only the officer's grid assigns rooms to slots, so only the officer
    //     // pays for that query — as before the merge.
    //     if ($role === 'timetable_officer') {
    //         $params['rooms'] = (new RoomModel())->all();
    //     }

    //     return $this->render($copy['view'], $params);
    // }
    public function index(Request $request)
    {
        // Both roles may see a timetable; which one they see is decided below.
        // Anyone else (there is no third role today) gets the 403 page.
        $denied = $this->requireRole('academic_staff', 'timetable_officer');
        if ($denied !== null) {
            return $denied;
        }

        // The guard above already limited this to the two keys in self::COPY.
        $role = $_SESSION['role'];
        $copy = self::COPY[$role];

        // Read + validate the dept/semester/year filters from ?query.
        $dept = $request->getQueryParams()['dept'] ?? 'cs';
        $sem = (int)($request->getQueryParams()['sem'] ?? 1);
        $year = (int)($request->getQueryParams()['year'] ?? 1);

        $dept = in_array($dept, ['cs', 'is'], true) ? $dept : 'cs';
        $sem = in_array($sem, [1, 2], true) ? $sem : 1;
        $year = in_array($year, [1, 2, 3, 4], true) ? $year : 1;

        // Fetch sessions based on the user's role
        $sessionModel = new TimetableSessionModel();
        if ($role === 'academic_staff') {
            // Staff only see sessions they manage
            $sessions = $sessionModel->forStaffManager($_SESSION['staff_code']);
        } else {
            // Timetable officers see everything filtered by dept/sem/year
            $sessions = $sessionModel->forDeptSemYear($dept, $sem, $year);
        }

        $params = [
            'title' => $copy['title'],
            'css_file' => $copy['css_file'],
            'active' => 'timetable',
            'pageTitle' => $copy['pageTitle'],
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'dept' => $dept,
            'sem' => $sem,
            'year' => $year,
            'courses' => (new CourseModel())->forDeptYear($dept, $year),
            'sessions' => $sessions,
        ];

        // Only the officer's grid assigns rooms to slots, so only the officer
        // pays for that query — as before the merge.
        if ($role === 'timetable_officer') {
            $params['rooms'] = (new RoomModel())->all();
        }

        return $this->render($copy['view'], $params);
    }





    public function schedule(Request $request)
    {
        // Use PHP associative array syntax instead of JavaScript object syntax
        $available = [
            [
                ['day_of_week' => 'wed', 'start_hour' => '8', 'duration_hours' => '1','available_weeks'=>'sem'],
                ['day_of_week' => 'mon', 'start_hour' => '14', 'duration_hours' => '1','available_weeks'=>'sem']
            ], 
            [
                ['day_of_week' => 'fri', 'start_hour' => '8', 'duration_hours' => '2','available_weeks'=>'3']
            ]
        ];

        // Return the available data along with the success status
        return json_encode([
            'status' => 'success',
            'available' => $available
        ]);
    }

    public function scheduleRequest(Request $request)
    {
    // Check authorization
    $denied = $this->requireRole('academic_staff', 'timetable_officer');

    if ($denied !== null) {
        http_response_code(403);

        return json_encode([
            'status' => 'error',
            'message' => 'Forbidden'
        ]);
    }

    $body = $request->getBody();
    $staffCode = $_SESSION['staff_code'] ?? null;

    if (empty($body['slots']) || !is_array($body['slots'])) {
        http_response_code(400);

        return json_encode([
            'status' => 'error',
            'message' => 'No valid time slots provided'
        ]);
    }

    // Package the parsed request body with the requester's staff code
    $requestData = [
        'requester_code'     => $staffCode,
        'slots'              => $body['slots'],
        'for_how_many_weeks' => (int) ($body['for_how_many_weeks'] ?? 1),
        'description'        => $body['description'] ?? '',
    ];

    // Instantiate the model where you placed session_request_create()
    $sessionModel = new TimetableSessionModel();

    $success = $sessionModel->session_request_create($requestData);

    if ($success) {
        return json_encode([
            'status' => 'success'
        ]);
    }

    http_response_code(500);

    return json_encode([
        'status' => 'error',
        'message' => 'Failed to save schedule request'
    ]);
    }
}
