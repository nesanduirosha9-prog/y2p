<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\LecturerModel;
use app\models\InstructorModel;
use app\models\NotificationModel;

// CoursesController — the "Course Management" screen (list + Add/Edit modals).
// Data is read from the database (courses + course_lecturers / course_instructors
// join tables). The add/edit/delete interactions are still client-side only
// (see courses.js) — an empty database renders an empty table.
class CoursesController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }

        return $this->render('courses/index', [
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
