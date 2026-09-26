<?php

namespace app\controllers\coordinator;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;
use app\models\StaffModel;
use app\services\EmailService;

// Coordinator "Staff Details" screen: approve/reject pending self-registrations,
// add staff directly, and browse the active staff directory. Available to
// anyone holding the additive `coordinator` position — and to `in_charge`,
// which carries every coordinator ability plus its own Accounts/handover screen.
// 1. Every action is guarded to position coordinator OR in_charge — this is
//    the one screen the two share, so the guard is deliberately not strict.
// 2. index()   — GET, lists pending registrations + the active directory.
// 3. approve() — POST, assigns a role/rank and flips status to 'active'.
// 4. reject()  — POST, deletes a pending row outright (no soft-delete).
// 5. create()  — POST, adds an active Lecturer / Junior Staff account from
//    just an email, and emails the member how to set their password.
// 6. deactivate() / activate() — POST, soft-removes a member who left the
//    university (and brings them back). An active account is never DELETEd:
//    too many tables reference it — see migration 023.
class StaffController extends Controller
{
    private const ASSIGNABLE_ROLES = [
        'junior' => ['role' => 'academic_staff', 'academic_rank' => 'junior'],
        'senior' => ['role' => 'academic_staff', 'academic_rank' => 'senior'],
        'timetable_officer' => ['role' => 'timetable_officer', 'academic_rank' => null],
    ];

    // What "Add staff" may create, request value => academic_rank. Deliberately
    // narrower than ASSIGNABLE_ROLES: the Timetable Officer seat is handed over
    // from the In-Charge's Accounts screen, not created here. The UI says
    // "Lecturer"; the stored rank is still 'senior'.
    private const CREATABLE_RANKS = [
        'lecturer' => 'senior',
        'junior' => 'junior',
    ];

    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requirePosition('coordinator', 'in_charge');
        if ($denied !== null) {
            return $denied;
        }

        $staffModel = new StaffModel();

        return $this->render('coordinator/staff', [
            'title' => 'Staff Details',
            'css_file' => ['/css/directory.css', '/css/coordinator/staff.css'],
            'active' => 'staff',
            'pageTitle' => 'Staff Details',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'pending' => $staffModel->pendingRegistrations(),
            'activeStaff' => $staffModel->activeStaff(),
        ]);
    }

    /** POST /staff/{code}/approve */
    public function approve(Request $request, Response $response, array $params = [])
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        if (!$this->guardJson($response, 'position', 'coordinator', 'in_charge')) {
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

    /** POST /staff/create — body: { email, role: 'lecturer' | 'junior' } */
    public function create(Request $request, Response $response)
    {
        if (!$this->guardJson($response, 'position', 'coordinator', 'in_charge')) {
            return;
        }

        $body = $request->getBody();
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $rank = self::CREATABLE_RANKS[$body['role'] ?? ''] ?? null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->json(['success' => false, 'field' => 'email', 'message' => 'Enter a valid email address.'], 400);
            return;
        }
        if ($rank === null) {
            $response->json(['success' => false, 'field' => 'role', 'message' => 'Choose Lecturer or Junior Staff.'], 400);
            return;
        }

        $staffModel = new StaffModel();
        if ($staffModel->findByEmail($email)) {
            $response->json(['success' => false, 'field' => 'email', 'message' => 'An account with this email already exists.'], 409);
            return;
        }

        $created = $staffModel->createByAdmin($email, $rank);
        if ($created === null) {
            $response->json(['success' => false, 'message' => 'Could not create the account. Please try again.'], 500);
            return;
        }

        // The account exists either way; the email only tells the member how
        // to get in. In demo mode nothing is sent — Forgot Password accepts
        // any code there, so the member can still set a password.
        $demo = defined('DEMO_AUTH') && DEMO_AUTH;
        $invited = !$demo && EmailService::send($email, 'Your StaffSync account', $this->inviteBody($created['code']));

        $response->json([
            'success' => true,
            'code' => $created['code'],
            'name' => $created['name'],
            'invited' => $invited,
            'demo' => $demo,
        ]);
    }

    /** The invite email: no password in it — the member sets their own. */
    private function inviteBody(string $code): string
    {
        // Host comes from the request, so only accept a plain host[:port]
        // before putting it in a link.
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link = preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $host)
            ? '<a href="' . $scheme . '://' . $host . '/forgot-password">set your password</a>'
            : 'set your password using "Forgot password" on the sign-in page';

        return '<p>An account has been created for you on StaffSync.</p>'
            . '<p>Your staff code is <b>' . htmlspecialchars($code) . '</b>. To sign in, first '
            . $link . ' with this email address.</p>'
            . '<p>If you were not expecting this, you can ignore this email.</p>';
    }

    /** POST /staff/{code}/reject */
    public function reject(Request $request, Response $response, array $params = [])
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        if (!$this->guardJson($response, 'position', 'coordinator', 'in_charge')) {
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

    /**
     * POST /staff/{code}/deactivate — for a member who has left the
     * university. Soft delete: the row and its history stay, login is refused.
     * Answers with the courses they were removed from, for the officer to
     * reassign.
     */
    public function deactivate(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'position', 'coordinator', 'in_charge')) {
            return;
        }

        $target = $this->manageableTarget($response, $params['code'] ?? '');
        if ($target === null) {
            return;
        }
        if ($target['status'] !== 'active') {
            $response->json(['success' => false, 'message' => 'This account is already inactive.'], 409);
            return;
        }
        // Each seat has its own hand-over flow, which keeps the department
        // from being left without a coordinator or a timetable officer.
        if ($target['role'] === 'timetable_officer') {
            $response->json(['success' => false, 'message' => 'The Timetable Officer account is handed over to the new officer from Settings → Handover, not deactivated.'], 409);
            return;
        }
        if (!empty($target['position'])) {
            $seat = $target['position'] === 'in_charge' ? 'In-Charge' : 'Coordinator';
            $response->json(['success' => false, 'message' => "{$target['name']} holds the {$seat} seat. Hand it over or revoke it from Settings → Handover first."], 409);
            return;
        }

        $staffModel = new StaffModel();
        $courses = $staffModel->courseCodes($target['code']);
        if (!$staffModel->deactivate($target['code'], $_SESSION['staff_code'])) {
            $response->json(['success' => false, 'message' => 'Could not deactivate the account. Please try again.'], 500);
            return;
        }

        $response->json(['success' => true, 'removedFromCourses' => $courses]);
    }

    /** POST /staff/{code}/activate — reverses deactivate(). */
    public function activate(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'position', 'coordinator', 'in_charge')) {
            return;
        }

        $target = $this->manageableTarget($response, $params['code'] ?? '');
        if ($target === null) {
            return;
        }
        if (!(new StaffModel())->reactivate($target['code'])) {
            $response->json(['success' => false, 'message' => 'This account is not inactive.'], 409);
            return;
        }

        $response->json(['success' => true]);
    }

    /**
     * The staff row behind a deactivate/activate, or null once an error has
     * been sent. Same rules as the Actions column of components/staff_directory.php:
     * never yourself, never the In-Charge, and a Coordinator only by the In-Charge.
     */
    private function manageableTarget(Response $response, string $code): ?array
    {
        $target = (new StaffModel())->findByCode($code);
        if (!$target || $target['status'] === 'pending') {
            $response->json(['success' => false, 'message' => 'Staff member not found.'], 404);
            return null;
        }
        if ($target['code'] === $_SESSION['staff_code']) {
            $response->json(['success' => false, 'message' => 'You cannot change the status of your own account.'], 403);
            return null;
        }
        if ($target['position'] === 'in_charge'
            || ($target['position'] === 'coordinator' && ($_SESSION['position'] ?? '') !== 'in_charge')) {
            $response->json(['success' => false, 'message' => 'You do not have permission to manage this account.'], 403);
            return null;
        }
        return $target;
    }
}
