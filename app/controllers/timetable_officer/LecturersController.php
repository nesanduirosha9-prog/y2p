<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\models\CourseModel;
use app\models\LecturerModel;
use app\models\NotificationModel;

class LecturersController extends Controller
{
    public function __construct()
    {
        $this->setLayout('timetable_officer_dashboard');
    }

    public function index(Request $request)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }

        $courseMeta = [];
        foreach ((new CourseModel())->listing() as $c) {
            $courseMeta[$c['code']] = ['year' => $c['year'], 'program' => $c['program']];
        }

        return $this->render('timetable_officer/lecturers', [
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
