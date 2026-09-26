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
//    just an email, with a random temporary password emailed to the member
//    (DEMO_AUTH: the shared demo password instead, nothing emailed).
// 6. deactivate() / activate() — POST, soft-removes a member who left the
//    university (and brings them back). An account with history is never
//    DELETEd: too many tables reference it — see migration 023.
// 7. destroy() — DELETE, the one hard delete for an approved account, and
//    only while it has no history at all (e.g. added with a mistyped email).
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

    // Password for accounts added while DEMO_AUTH is on — the same placeholder
    // every seeded account uses (database/seeds/001_staff.sql).
    private const DEMO_PASSWORD = 'Password123!';

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

        // Demo mode: the same placeholder password as every seeded account,
        // nothing emailed. Real mode: a random password, emailed to the member.
        $demo = defined('DEMO_AUTH') && DEMO_AUTH;
        $temporaryPassword = $demo ? self::DEMO_PASSWORD : $this->temporaryPassword();
        $created = $staffModel->createByAdmin($email, $rank, $temporaryPassword);
        if ($created === null) {
            $response->json(['success' => false, 'message' => 'Could not create the account. Please try again.'], 500);
            return;
        }

        $invited = !$demo && EmailService::send($email, 'Your StaffSync account', $this->inviteBody($created['code'], $email, $temporaryPassword));

        $response->json([
            'success' => true,
            'code' => $created['code'],
            'name' => $created['name'],
            'email' => $email,
            'invited' => $invited,
            // Only when a real-mode email failed: shown to the coordinator
            // once, or the account would be locked behind a password nobody knows.
            'temporaryPassword' => (!$demo && !$invited) ? $temporaryPassword : null,
        ]);
    }

    /**
     * 12 characters from an alphabet without look-alikes (0/O, 1/l/I), with
     * at least one upper-case letter, lower-case letter and digit.
     */
    private function temporaryPassword(): string
    {
        $sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789'];
        $all = implode('', $sets);
        $chars = array_map(fn($set) => $set[random_int(0, strlen($set) - 1)], $sets);
        while (count($chars) < 12) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }
        // Fisher–Yates with random_int, so the guaranteed characters aren't always first.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
        return implode('', $chars);
    }

    /** The invite email: sign-in details, and where to change the password. */
    private function inviteBody(string $code, string $email, string $temporaryPassword): string
    {
        // Host comes from the request, so only accept a plain host[:port]
        // before putting it in a link.
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $signIn = preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $host)
            ? '<a href="' . $scheme . '://' . $host . '/login">Sign in to StaffSync</a>'
            : 'Sign in to StaffSync';

        return '<p>An account has been created for you on StaffSync.</p>'
            . '<p>Staff code: <b>' . htmlspecialchars($code) . '</b><br>'
            . 'Email: <b>' . htmlspecialchars($email) . '</b><br>'
            . 'Temporary password: <b>' . htmlspecialchars($temporaryPassword) . '</b></p>'
            . '<p>' . $signIn . ', then change your password from Settings → Password.</p>'
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
        if (!$this->refuseSeatHolder($response, $target, 'deactivated')) {
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
     * DELETE /staff/{code} — removes an account for good, but only one with
     * no history: nothing anywhere references it yet (typically added with a
     * mistyped email). Anyone who has used the system is deactivated instead;
     * deleting them would cascade away their records. The Delete button is
     * only rendered for such accounts — this re-checks, inside the delete.
     */
    public function destroy(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'position', 'coordinator', 'in_charge')) {
            return;
        }

        $target = $this->manageableTarget($response, $params['code'] ?? '');
        if ($target === null) {
            return;
        }
        if (!$this->refuseSeatHolder($response, $target, 'deleted')) {
            return;
        }

        if (!(new StaffModel())->deleteIfNoHistory($target['code'])) {
            $response->json(['success' => false, 'message' => "{$target['name']} already has records in the system, so the account cannot be deleted. Deactivate it instead."], 409);
            return;
        }

        $response->json(['success' => true]);
    }

    /**
     * Each seat has its own hand-over flow, which keeps the department from
     * being left without a coordinator or a timetable officer — so a seat
     * holder is never deactivated or deleted from here. Sends the 409 and
     * returns false for one; true to carry on. $verb: 'deactivated' | 'deleted'.
     */
    private function refuseSeatHolder(Response $response, array $target, string $verb): bool
    {
        if ($target['role'] === 'timetable_officer') {
            $response->json(['success' => false, 'message' => "The Timetable Officer account is handed over to the new officer from Settings → Handover, not {$verb}."], 409);
            return false;
        }
        if (!empty($target['position'])) {
            $seat = $target['position'] === 'in_charge' ? 'In-Charge' : 'Coordinator';
            $response->json(['success' => false, 'message' => "{$target['name']} holds the {$seat} seat. Hand it over or revoke it from Settings → Handover first."], 409);
            return false;
        }
        return true;
    }

    /**
     * The staff row behind a deactivate/activate/delete, or null once an error has
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
            $response->json(['success' => false, 'message' => 'You cannot change or delete your own account.'], 403);
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
