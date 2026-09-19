<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

class SettingsController extends Controller
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

        return $this->render('timetable_officer/settings', [
            'title' => 'Settings',
            'active' => 'settings',
            'pageTitle' => 'Settings',
            'notificationCount' => (new NotificationModel())->unreadCount(),
        ]);
    }
}
