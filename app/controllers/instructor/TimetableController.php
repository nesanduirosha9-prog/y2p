<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\NotificationModel;
use app\models\TimetableSessionModel;

// TimetableController (instructor): "My Timetable" read-only weekly grid.
// 1. Guards the route to a logged-in academic_staff session.
// 2. Reads + validates dept/sem/year filters (same defaults/whitelist as
//    the timetable officer's controller — NOTE: shows the whole
//    department's grid, not sessions filtered to just this instructor).
// 3. Renders the grid inside the shared dashboard layout.
class TimetableController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requireRole('academic_staff');
        if ($denied !== null) {
            return $denied;
        }

        $dept = $request->getQueryParams()['dept'] ?? 'cs';
        $sem = (int)($request->getQueryParams()['sem'] ?? 1);
        $year = (int)($request->getQueryParams()['year'] ?? 1);

        $dept = in_array($dept, ['cs', 'is'], true) ? $dept : 'cs';
        $sem = in_array($sem, [1, 2], true) ? $sem : 1;
        $year = in_array($year, [1, 2, 3, 4], true) ? $year : 1;

        $courses = (new CourseModel())->forDeptYear($dept, $year);
        $sessions = (new TimetableSessionModel())->forDeptSemYear($dept, $sem, $year);

        return $this->render('instructor/timetable', [
            'title' => 'My Timetable',
            'css_file' => ['/css/timetable.css', '/css/instructor/timetable.css'],
            'active' => 'timetable',
            'pageTitle' => 'My Timetable',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'dept' => $dept,
            'sem' => $sem,
            'year' => $year,
            'courses' => $courses,
            'sessions' => $sessions,
        ]);
    }
}
