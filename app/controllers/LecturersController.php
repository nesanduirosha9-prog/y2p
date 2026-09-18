<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\LecturerModel;
use app\models\NotificationModel;

// LecturersController — the read-only "Lecturer Details" directory.
// Data is read from the database; search/filter run client-side (lecturers.js).
class LecturersController extends Controller
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

        // Course code -> [year, program], so a lecturer row can be filtered by
        // the year/program of the courses they teach.
        $courseMeta = [];
        foreach ((new CourseModel())->listing() as $c) {
            $courseMeta[$c['code']] = ['year' => $c['year'], 'program' => $c['program']];
        }

        return $this->render('lecturers/index', [
            'title' => 'Lecturer Details',
            'css_file' => ['/css/directory.css', '/css/lecturers.css'],
            'active' => 'lecturers',
            'pageTitle' => 'Lecturer Details',
            'notificationCount' => (new NotificationModel())->unreadCount(),
            'lecturers' => (new LecturerModel())->all(),
            'courseMeta' => $courseMeta,
        ]);
    }
}
