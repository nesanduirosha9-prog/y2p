<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

class NotificationsController extends Controller
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

        $model = new NotificationModel();
        $notifications = $model->all();
        $unread = $model->unreadCount();

        return $this->render('timetable_officer/notifications', [
            'title' => 'Notifications',
            'css_file' => ['/css/directory.css', '/css/notifications.css'],
            'active' => 'notifications',
            'pageTitle' => 'Notifications',
            'notificationCount' => $unread,
            'notifications' => $notifications,
            'unread' => $unread,
        ]);
    }
}
