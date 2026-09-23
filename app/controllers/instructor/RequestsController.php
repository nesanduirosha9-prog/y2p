<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

// RequestsController (instructor): "Requests" page.
// Routed at /instructor/requests but NOT linked from the sidebar nav
// (see dashboard layout's $navItemsByRole) — currently only reachable by
// typing the URL directly (see gaps). Read-only render, no model wired up.
class RequestsController extends Controller
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

        return $this->render('instructor/requests', [
            'title' => 'Requests',
            'css_file' => ['/css/instructor/requests.css'],
            'active' => 'requests',
            'pageTitle' => 'Requests',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }
}
