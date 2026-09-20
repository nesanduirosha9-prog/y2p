<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

class SettingsController extends Controller
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

        return $this->render('instructor/settings', [
            'title' => 'Settings',
            'css_file' => ['/css/instructor/settings.css'],
            'active' => 'settings',
            'pageTitle' => 'Settings',
            'notificationCount' => (new NotificationModel())->unreadCount(),
        ]);
    }
}
