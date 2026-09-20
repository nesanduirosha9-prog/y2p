<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\LecturerModel;
use app\models\InstructorModel;
use app\models\NotificationModel;

class CoursesController extends Controller
{
    public function __construct()
    {
        $this->setLayout('timetable_officer_dashboard');
    }

    public function index(Request $request)
    {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'timetable_officer') {
            $this->redirect('/login');
            return;
        }

        return $this->render('timetable_officer/courses', [
            'title' => 'Course Management',
            'css_file' => ['/css/directory.css', '/css/courses.css'],
            'active' => 'courses',
            'pageTitle' => 'Course Management',
            'notificationCount' => (new NotificationModel())->unreadCount(),
            'courses' => (new CourseModel())->listing(),
            'lecturers' => (new LecturerModel())->all(),
            'instructors' => (new InstructorModel())->all(),
        ]);
    }
}
