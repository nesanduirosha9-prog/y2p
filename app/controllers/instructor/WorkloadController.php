<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

// WorkloadController (instructor): "My Workload" page.
// NOTE: renders the view with no workload data passed in — the
// `workload_tasks` table (migration 010) exists but nothing here queries
// it yet, so the view must be using static/placeholder content (see gaps).
class WorkloadController extends Controller
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

        return $this->render('instructor/workload', [
            'title' => 'My Workload',
            'css_file' => ['/css/instructor/workload.css'],
            'active' => 'workload',
            'pageTitle' => 'My Workload',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }
}
