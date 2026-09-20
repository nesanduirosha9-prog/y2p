<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

// MessagesController (instructor): "Messages" page.
// NOTE: read-only render — the `chat_rooms`/`chat_participants`/`messages`
// tables (migrations 011-013) exist but nothing here queries them yet
// (see gaps).
class MessagesController extends Controller
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

        return $this->render('instructor/messages', [
            'title' => 'Messages',
            'css_file' => ['/css/instructor/messages.css'],
            'active' => 'messages',
            'pageTitle' => 'Messages',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }
}
