<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        if (!isset($_SESSION['staff_code']) || ($_SESSION['role'] ?? '') !== 'academic_staff') {
            $this->redirect('/login');
            return;
        }

        return $this->render('instructor/leave', [
            'title' => 'Leave Management',
            'css_file' => ['/css/instructor/leave.css'],
            'active' => 'leave',
            'pageTitle' => 'Leave',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }
}
