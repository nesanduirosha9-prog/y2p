<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

// NotificationsController — the notification feed.
// Data is read from the database; "mark as read" is client-side (notifications.js).
class NotificationsController extends Controller
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

        $model = new NotificationModel();
        $notifications = $model->all();
        $unread = $model->unreadCount();

        return $this->render('notifications/index', [
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
