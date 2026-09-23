<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

// LeaveController (instructor): "Leave" page.
// NOTE: read-only render — no model backs the `leave_requests` table
// (migration 009) here yet, so submitting a leave request has no
// server-side handler (see gaps).
class LeaveController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requireRole('academic_staff');
        if ($denied !== null) {
            return $denied;
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
