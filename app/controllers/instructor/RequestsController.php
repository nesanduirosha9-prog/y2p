<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

class RequestsController extends Controller
{
    public function __construct()
    {
        $this->setLayout('instructor_dashboard');
    }

    public function index(Request $request)
    {
        if (!isset($_SESSION['staff_code']) || ($_SESSION['role'] ?? '') !== 'academic_staff') {
            $this->redirect('/login');
            return;
        }

        return $this->render('instructor/requests', [
            'title' => 'Requests',
            'css_file' => ['/css/instructor/requests.css'],
            'active' => 'requests',
            'pageTitle' => 'Requests',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }
}
