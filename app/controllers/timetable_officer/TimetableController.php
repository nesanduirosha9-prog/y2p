<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\NotificationModel;
use app\models\TimetableSessionModel;

// TimetableController (timetable officer): the weekly schedule grid.
// 1. Guards the route to logged-in `timetable_officer` sessions only.
// 2. Reads dept/sem/year filters from the query string, falling back to
//    (and validating against) sane defaults.
// 3. Loads the course list + scheduled sessions for that combination.
// 4. Renders the grid inside the shared dashboard layout.
class TimetableController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // 1. Route guard — only a signed-in timetable officer may view this.
        if (!isset($_SESSION['staff_code']) || ($_SESSION['role'] ?? '') !== 'timetable_officer') {
            $this->redirect('/login');
            return;
        }

        // 2. Read + validate the dept/semester/year filters from ?query.
        $dept = $request->getQueryParams()['dept'] ?? 'cs';
        $sem = (int)($request->getQueryParams()['sem'] ?? 1);
        $year = (int)($request->getQueryParams()['year'] ?? 1);

        $dept = in_array($dept, ['cs', 'is'], true) ? $dept : 'cs';
        $sem = in_array($sem, [1, 2], true) ? $sem : 1;
        $year = in_array($year, [1, 2, 3, 4], true) ? $year : 1;

        // 3. Load the course catalogue + scheduled sessions + rooms for the grid.
        $courses = (new CourseModel())->forDeptYear($dept, $year);
        $sessions = (new TimetableSessionModel())->forDeptSemYear($dept, $sem, $year);
        $rooms = (new \app\models\RoomModel())->all();

        // 4. Render inside the shared dashboard shell.
        return $this->render('timetable_officer/timetable', [
            'title' => 'Timetable Management',
            'css_file' => '/css/timetable.css',
            'active' => 'timetable',
            'pageTitle' => 'Timetable Management',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'dept' => $dept,
            'sem' => $sem,
            'year' => $year,
            'courses' => $courses,
            'sessions' => $sessions,
            'rooms' => $rooms,
        ]);
    }
}
