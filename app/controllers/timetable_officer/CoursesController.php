<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\LecturerModel;
use app\models\InstructorModel;
use app\models\NotificationModel;

// CoursesController (timetable officer): the Course Management table.
// 1. Guards the route to a logged-in timetable officer.
// 2. Loads the full course listing plus lecturer/instructor name lookups
//    (used to render the assigned-staff badges and the Add/Edit dropdowns).
class CoursesController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        if (!isset($_SESSION['staff_code']) || ($_SESSION['role'] ?? '') !== 'timetable_officer') {
            $this->redirect('/login');
            return;
        }

        return $this->render('timetable_officer/course_details', [
            'title' => 'Course Details',
            'css_file' => ['/css/directory.css', '/css/courses.css'],
            'active' => 'courses',
            'pageTitle' => 'Course Details',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'courses' => (new CourseModel())->listing(),
            'lecturers' => (new LecturerModel())->all(),
            'instructors' => (new InstructorModel())->all(),
        ]);
    }
}
