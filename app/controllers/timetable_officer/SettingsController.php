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
        if (!isset($_SESSION['staff_code']) || ($_SESSION['role'] ?? '') !== 'timetable_officer') {
            $this->redirect('/login');
            return;
        }

        return $this->render('timetable_officer/settings', [
            'title' => 'Settings',
            'active' => 'settings',
            'pageTitle' => 'Settings',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }
}
