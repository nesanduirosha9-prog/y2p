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
            'css_file'  => '/css/timetable.css',
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
            'sessions' => (new TimetableSessionModel())->forDeptSemYear($dept, $sem, $year),
        ];

        // Only the officer's grid assigns rooms to slots, so only the officer
        // pays for that query — as before the merge.
        if ($role === 'timetable_officer') {
            $params['rooms'] = (new RoomModel())->all();
        }

        return $this->render($copy['view'], $params);
    }
}
