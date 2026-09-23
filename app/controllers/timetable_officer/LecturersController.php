<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\InstructorModel;
use app\models\LecturerModel;
use app\models\NotificationModel;

// LecturersController (timetable officer): the "Staff Details" directory
// (senior lecturers + junior instructors, tabbed in the view).
// 1. Guards the route to a logged-in timetable officer.
// 2. Builds a course_code -> {year, program} lookup so the view can show
//    each staff member's course badges without re-querying per row.
// 3. Renders lecturers, junior-staff directory, and that lookup together.
class LecturersController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requireRole('timetable_officer');
        if ($denied !== null) {
            return $denied;
        }

        $courseMeta = [];
        foreach ((new CourseModel())->listing() as $c) {
            $courseMeta[$c['code']] = ['year' => $c['year'], 'program' => $c['program']];
        }

        return $this->render('timetable_officer/lecturers', [
            'title' => 'Staff Details',
            'css_file' => ['/css/directory.css', '/css/lecturers.css'],
            'active' => 'lecturers',
            'pageTitle' => 'Staff Details',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'lecturers' => (new LecturerModel())->all(),
            'juniorStaff' => (new InstructorModel())->directory(),
            'courseMeta' => $courseMeta,
        ]);
    }
}
