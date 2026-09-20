<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->setLayout('instructor_dashboard');
    }

    public function index(Request $request)
    {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'instructor') {
            $this->redirect('/login');
            return;
        }

        return $this->render('instructor/leave', [
            'title' => 'Leave Management',
            'css_file' => ['/css/instructor/leave.css'],
            'active' => 'leave',
            'pageTitle' => 'Leave',
            'notificationCount' => (new NotificationModel())->unreadCount(),
        ]);
    }
}
