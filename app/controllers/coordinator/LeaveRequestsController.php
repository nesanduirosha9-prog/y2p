<?php

namespace app\controllers\coordinator;

use app\core\Controller;
use app\core\Request;
use app\models\LeaveRequestModel;
use app\models\NotificationModel;

// LeaveRequestsController: the "Leave Requests" page in the Administration
// nav, just under Workload. Read-only — leave is approved on paper outside
// the system, so this page is the department's record of who is (and was)
// away, used to plan workload around it.
//   index() — GET /leave/requests[?tab=upcoming|history]
//     upcoming  leave whose last day is today or later
//     history   leave that has fully passed
// Coordinator and In-Charge see the same page.
class LeaveRequestsController extends Controller
{
    private const TABS = ['upcoming', 'history'];

    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        $denied = $this->requirePosition('coordinator', 'in_charge');
        if ($denied !== null) {
            return $denied;
        }

        $tab = $request->getQueryParams()['tab'] ?? 'upcoming';
        if (!in_array($tab, self::TABS, true)) {
            $tab = 'upcoming';
        }

        return $this->render('coordinator/leave_requests', [
            'title' => 'Leave Requests — StaffSync',
            'css_file' => ['/css/directory.css', '/css/coordinator/staff.css', '/css/leave_tables.css'],
            'active' => 'leave-requests',
            'pageTitle' => 'Leave Requests',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'tab' => $tab,
            // Rendered client-side by js/leave_requests.js.
            'leaveData' => [
                'today' => date('Y-m-d'),
                'records' => (new LeaveRequestModel())->all(),
            ],
        ]);
    }
}
