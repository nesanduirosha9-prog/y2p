<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\NotificationModel;
use app\models\TimetableSessionModel;

class TimetableController extends Controller
{
    public function __construct()
    {
        $this->setLayout('timetable_officer_dashboard');
    }

    public function index(Request $request)
    {
        if (!isset($_SESSION['staff_code']) || ($_SESSION['role'] ?? '') !== 'timetable_officer') {
            $this->redirect('/login');
            return;
        }

        $dept = $request->getQueryParams()['dept'] ?? 'cs';
        $sem = (int)($request->getQueryParams()['sem'] ?? 1);
        $year = (int)($request->getQueryParams()['year'] ?? 1);

        $dept = in_array($dept, ['cs', 'is'], true) ? $dept : 'cs';
        $sem = in_array($sem, [1, 2], true) ? $sem : 1;
        $year = in_array($year, [1, 2, 3, 4], true) ? $year : 1;

        $courses = (new CourseModel())->forDeptYear($dept, $year);
        $sessions = (new TimetableSessionModel())->forDeptSemYear($dept, $sem, $year);

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
        ]);
    }
}
