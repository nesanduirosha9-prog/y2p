<?php

namespace app\controllers\coordinator;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;
use app\models\StaffModel;

// Coordinator "Staff" screen: approve/reject pending self-registrations and
// browse the active staff directory. Available to anyone holding the
// additive `coordinator` position — and to `in_charge`, which carries every
// coordinator ability plus its own Accounts/handover screen.
class StaffController extends Controller
{
    private const ASSIGNABLE_ROLES = [
        'junior' => ['role' => 'academic_staff', 'academic_rank' => 'junior'],
        'senior' => ['role' => 'academic_staff', 'academic_rank' => 'senior'],
        'timetable_officer' => ['role' => 'timetable_officer', 'academic_rank' => null],
    ];

    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    private function guard(): bool
    {
        return isset($_SESSION['staff_code'])
            && in_array($_SESSION['position'] ?? '', ['coordinator', 'in_charge'], true);
    }

    public function index(Request $request)
    {
        if (!$this->guard()) {
            $this->redirect('/login');
            return;
        }

        $staffModel = new StaffModel();

        return $this->render('coordinator/staff', [
            'title' => 'Staff',
            'css_file' => ['/css/directory.css', '/css/coordinator/staff.css'],
            'active' => 'staff',
            'pageTitle' => 'Staff',
            'pageSubtitle' => 'Approve new registrations and manage active staff.',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'pending' => $staffModel->pendingRegistrations(),
            'activeStaff' => $staffModel->activeStaff(),
        ]);
    }

    /** POST /coordinator/staff/{code}/approve */
    public function approve(Request $request, Response $response, array $params = [])
    {
        if (!$this->guard()) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $code = $params['code'] ?? '';
        $body = $request->getBody();
        $assign = self::ASSIGNABLE_ROLES[$body['role'] ?? ''] ?? null;

        if ($code === '' || $assign === null) {
            $response->json(['success' => false, 'message' => 'Please choose a role to assign.'], 400);
            return;
        }

        $ok = (new StaffModel())->approve($code, $assign['role'], $assign['academic_rank']);
        if (!$ok) {
            $response->json(['success' => false, 'message' => 'Registration not found or already handled.'], 404);
            return;
        }

        $response->json(['success' => true]);
    }

    /** POST /coordinator/staff/{code}/reject */
    public function reject(Request $request, Response $response, array $params = [])
    {
        if (!$this->guard()) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $code = $params['code'] ?? '';
        $ok = (new StaffModel())->reject($code);
        if (!$ok) {
            $response->json(['success' => false, 'message' => 'Registration not found or already handled.'], 404);
            return;
        }

        $response->json(['success' => true]);
    }
}
