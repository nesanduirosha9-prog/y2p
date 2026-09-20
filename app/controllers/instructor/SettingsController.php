<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

// SettingsController (instructor): "Settings" page.
// NOTE: read-only render — there is no POST/PUT handler here to persist
// profile edits (name/phone/etc). This is the page a self-registered
// account is meant to fill in its details on post-signup (see gaps).
class SettingsController extends Controller
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

        return $this->render('instructor/settings', [
            'title' => 'Settings',
            'css_file' => ['/css/instructor/settings.css'],
            'active' => 'settings',
            'pageTitle' => 'Settings',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }
}
