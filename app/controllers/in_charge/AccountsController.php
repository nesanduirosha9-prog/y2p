<?php

namespace app\controllers\in_charge;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;
use app\models\StaffModel;

// In-Charge "Accounts" / Role Assignment screen — pulled from Figma node
// 34:5044 (canvas "In_Charge"): a 4-step handover flow (pick seat -> search
// replacement -> OTP verify -> success) to reassign the Coordinator(s),
// In-Charge and Timetable Officer seats. Only `position = 'in_charge'` may
// open this screen (confirmed with the user — not shared with Timetable
// Officer, even though the Figma mockup's sidebar profile card said "TO").
//
// The in-progress handover (which seat, chosen replacement, OTP) lives in
// $_SESSION['handover'] — there's no other multi-step wizard state anywhere
// in this codebase, so this mirrors the existing session-based auth guard
// pattern rather than introducing a new persistence mechanism for a
// short-lived, single-user flow.
class AccountsController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    private function guard(): bool
    {
        return isset($_SESSION['staff_code']) && ($_SESSION['position'] ?? '') === 'in_charge';
    }

    private function commonViewData(): array
    {
        return [
            'css_file' => ['/css/directory.css', '/css/in_charge/accounts.css'],
            'active' => 'accounts',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ];
    }

    /** GET /in-charge/accounts */
    public function index(Request $request)
    {
        if (!$this->guard()) {
            $this->redirect('/login');
            return;
        }

        return $this->render('in_charge/accounts', array_merge($this->commonViewData(), [
            'title' => 'Accounts',
            'pageTitle' => 'Role Assignment',
            'pageSubtitle' => 'Manage key academic role holders for the department.',
            'holders' => (new StaffModel())->roleHolders(),
        ]));
    }

    /** GET /in-charge/accounts/change/{position}/{code} */
    public function change(Request $request, Response $response, array $params = [])
    {
        if (!$this->guard()) {
            $this->redirect('/login');
            return;
        }

        $position = $params['position'] ?? '';
        $code = $params['code'] ?? '';
        $holder = (new StaffModel())->findByCode($code);

        if (!in_array($position, ['coordinator', 'in_charge', 'timetable_officer'], true) || !$holder) {
            $this->redirect('/in-charge/accounts');
            return;
        }

        return $this->render('in_charge/accounts_change', array_merge($this->commonViewData(), [
            'title' => 'Change Role',
            'pageTitle' => 'Role Assignment',
            'pageSubtitle' => 'Select which role you want to reassign.',
            'position' => $position,
            'holder' => $holder,
        ]));
    }

    /** GET /in-charge/accounts/select/{position}/{code} */
    public function selectView(Request $request, Response $response, array $params = [])
    {
        if (!$this->guard()) {
            $this->redirect('/login');
            return;
        }

        $position = $params['position'] ?? '';
        $code = $params['code'] ?? '';
        $holder = (new StaffModel())->findByCode($code);

        if (!in_array($position, ['coordinator', 'in_charge', 'timetable_officer'], true) || !$holder) {
            $this->redirect('/in-charge/accounts');
            return;
        }

        // Coordinator needs a junior lecturer, In-Charge needs a senior one;
        // a Timetable Officer replacement can be any active academic staff.
        $staffModel = new StaffModel();
        $rank = $position === 'coordinator' ? 'junior' : ($position === 'in_charge' ? 'senior' : null);
        $candidates = $rank
            ? $staffModel->activeByRank($rank)
            : array_merge($staffModel->activeByRank('junior'), $staffModel->activeByRank('senior'));

        // Can't hand a role to yourself.
        $candidates = array_values(array_filter($candidates, fn($c) => $c['code'] !== $code));

        return $this->render('in_charge/accounts_select', array_merge($this->commonViewData(), [
            'title' => 'Select Replacement',
            'pageTitle' => 'Role Assignment',
            'pageSubtitle' => 'Search lecturer by name.',
            'position' => $position,
            'holder' => $holder,
            'candidates' => $candidates,
        ]));
    }

    /** POST /in-charge/accounts/select — starts the OTP challenge. */
    public function selectSubmit(Request $request, Response $response)
    {
        if (!$this->guard()) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $body = $request->getBody();
        $position = $body['position'] ?? '';
        $fromCode = $body['fromCode'] ?? '';
        $toCode = $body['toCode'] ?? '';
        $newRankForOutgoingOfficer = $body['fromNewRank'] ?? null;

        if (!in_array($position, ['coordinator', 'in_charge', 'timetable_officer'], true) || $fromCode === '' || $toCode === '') {
            $response->json(['success' => false, 'message' => 'Please choose a replacement.'], 400);
            return;
        }
        if ($position === 'timetable_officer' && !in_array($newRankForOutgoingOfficer, ['junior', 'senior'], true)) {
            $response->json(['success' => false, 'message' => 'Please choose the outgoing officer\'s new rank.'], 400);
            return;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['handover'] = [
            'position' => $position,
            'from_code' => $fromCode,
            'to_code' => $toCode,
            'from_new_rank' => $newRankForOutgoingOfficer,
            'otp' => $otp,
            'expires_at' => time() + 300, // 5 minutes
        ];

        // No mail infrastructure exists anywhere in this codebase yet, so the
        // OTP is surfaced directly instead of emailed — the verification is
        // still real (a wrong code is rejected), it's just not delivered out
        // of band. Swap this for a real mailer once one exists.
        $response->json(['success' => true, 'redirect' => '/in-charge/accounts/verify', 'dev_otp' => $otp]);
    }

    /** GET /in-charge/accounts/verify */
    public function verifyView(Request $request)
    {
        if (!$this->guard() || empty($_SESSION['handover'])) {
            $this->redirect('/in-charge/accounts');
            return;
        }

        $handover = $_SESSION['handover'];
        $staffModel = new StaffModel();

        return $this->render('in_charge/accounts_verify', array_merge($this->commonViewData(), [
            'title' => 'Verify OTP',
            'pageTitle' => 'Role Assignment',
            'pageSubtitle' => 'Enter the verification code to confirm this change.',
            'toStaff' => $staffModel->findByCode($handover['to_code']),
            // Dev-mode only: no email dispatch exists yet (see selectSubmit).
            'devOtp' => $handover['otp'],
        ]));
    }

    /** POST /in-charge/accounts/verify — confirms the OTP and performs the reassignment. */
    public function verifySubmit(Request $request, Response $response)
    {
        if (!$this->guard() || empty($_SESSION['handover'])) {
            $response->json(['success' => false, 'message' => 'No pending role change found.'], 400);
            return;
        }

        $handover = $_SESSION['handover'];
        $body = $request->getBody();
        $otp = trim($body['otp'] ?? '');

        if (time() > $handover['expires_at']) {
            unset($_SESSION['handover']);
            $response->json(['success' => false, 'message' => 'This code has expired. Please start again.'], 400);
            return;
        }
        if ($otp === '' || $otp !== $handover['otp']) {
            $response->json(['success' => false, 'message' => 'Incorrect verification code.'], 400);
            return;
        }

        $staffModel = new StaffModel();
        if ($handover['position'] === 'timetable_officer') {
            $ok = $staffModel->reassignTimetableOfficer($handover['from_code'], $handover['to_code'], $handover['from_new_rank']);
        } else {
            $ok = $staffModel->reassignPosition($handover['from_code'], $handover['to_code'], $handover['position']);
        }

        unset($_SESSION['handover']);

        if (!$ok) {
            $response->json(['success' => false, 'message' => 'Could not complete the role change.'], 500);
            return;
        }

        $response->json(['success' => true, 'redirect' => '/in-charge/accounts/updated']);
    }

    /** GET /in-charge/accounts/updated */
    public function updatedView(Request $request)
    {
        if (!$this->guard()) {
            $this->redirect('/login');
            return;
        }

        return $this->render('in_charge/accounts_updated', array_merge($this->commonViewData(), [
            'title' => 'Role Updated',
            'pageTitle' => 'Role Assignment',
            'pageSubtitle' => 'Manage key academic role holders for the department.',
            'holders' => (new StaffModel())->roleHolders(),
        ]));
    }
}
