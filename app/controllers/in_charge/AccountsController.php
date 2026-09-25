<?php

namespace app\controllers\in_charge;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;
use app\models\StaffModel;
use app\services\EmailService;

// In-Charge "Accounts" / Role Assignment screen — pulled from Figma node
// 34:5044 (canvas "In_Charge"): a 4-step handover flow (pick seat -> search
// replacement -> OTP verify -> success) to reassign the Coordinator(s) and
// In-Charge seats. The Timetable Officer is not a seat: it is its own account,
// and when the officer changes it is that account's details that change, so
// there is no handover for it. Only `position = 'in_charge'` may
// open this screen (confirmed with the user — not shared with Timetable
// Officer, even though the Figma mockup's sidebar profile card said "TO").
//
// The in-progress handover (which seat, chosen replacement, OTP) lives in
// $_SESSION['handover'] — there's no other multi-step wizard state anywhere
// in this codebase, so this mirrors the existing session-based auth guard
// pattern rather than introducing a new persistence mechanism for a
// short-lived, single-user flow.
//
// 1. commonViewData() — shared view-data every action uses. The auth check is
//    Controller::requirePosition('in_charge') / guardJson().
// 2. index()        — GET, lists the current seat holders.
// 3. change()        — GET, step 1: pick which seat to reassign.
// 4. selectView()     — GET, step 2: search a same-rank replacement.
// 5. selectSubmit()   — POST, step 2 submit: generates a 6-digit OTP into
//    the session and emails it (via EmailService) to the incoming staff
//    member, so they consciously confirm accepting the new role.
// 6. verifyView()     — GET, step 3: shows the OTP entry screen.
// 7. verifySubmit()   — POST, step 3 submit: checks the OTP + expiry, then
//    performs the actual reassignment.
// 8. updatedView()    — GET, step 4: confirmation screen.
// 9. add()            — GET, "Add coordinator": the same select/verify steps,
//    with nobody being replaced. The department may have any number of
//    Coordinators; the In-Charge seat stays single.
// 10. revoke()        — POST, takes the Coordinator seat away from someone
//    (they stay on staff as Junior Staff). Never the last Coordinator.
//
// DEMO_AUTH (config.php) simulates the OTP exactly as AuthController does: no
// email is sent and any 6-digit code is accepted.
class AccountsController extends Controller
{
    /** Seats that can be handed from one staff member to another. */
    private const SEATS = ['coordinator', 'in_charge'];
    /** Seats that can be held by more than one person at once. */
    private const MULTI_SEATS = ['coordinator'];
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    private function commonViewData(): array
    {
        return [
            'css_file' => ['/css/directory.css', '/css/in_charge/accounts.css'],
            'active' => 'accounts',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ];
    }

    /**
     * Standalone "Role Assignment" list page.
     *
     * NOTE: currently unreachable. No route calls it — /settings/handover
     * redirects to the handover tab inside Settings instead (as the old
     * /in-charge/accounts route did before it). Kept because the handover flow's
     * later steps still render views/in_charge/accounts*.php, and because it is
     * the page this controller was written around. See docs/ROUTING_REFACTOR.md.
     */
    public function index(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requirePosition('in_charge');
        if ($denied !== null) {
            return $denied;
        }

        return $this->render('in_charge/accounts', array_merge($this->commonViewData(), [
            'title' => 'Accounts',
            'pageTitle' => 'Role Assignment',
            'holders' => (new StaffModel())->roleHolders(),
        ]));
    }

    /** GET /settings/handover/change/{position}/{code} */
    public function change(Request $request, Response $response, array $params = [])
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requirePosition('in_charge');
        if ($denied !== null) {
            return $denied;
        }

        $position = $params['position'] ?? '';
        $code = $params['code'] ?? '';
        $holder = (new StaffModel())->findByCode($code);

        if (!in_array($position, self::SEATS, true) || !$holder) {
            $this->redirect('/settings/handover');
            return;
        }

        return $this->render('in_charge/accounts_change', array_merge($this->commonViewData(), [
            'title' => 'Change Role',
            'pageTitle' => 'Change Role',
            'position' => $position,
            'holder' => $holder,
        ]));
    }

    /** GET /settings/handover/select/{position}/{code} */
    public function selectView(Request $request, Response $response, array $params = [])
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requirePosition('in_charge');
        if ($denied !== null) {
            return $denied;
        }

        $position = $params['position'] ?? '';
        $code = $params['code'] ?? '';
        $holder = (new StaffModel())->findByCode($code);

        if (!in_array($position, self::SEATS, true) || !$holder) {
            $this->redirect('/settings/handover');
            return;
        }

        return $this->render('in_charge/accounts_select', array_merge($this->commonViewData(), [
            'title' => 'Select Replacement',
            'position' => $position,
            'holder' => $holder,
            'candidates' => $this->candidatesFor($position, $code),
        ]));
    }

    /** GET /settings/handover/add/{position} — add one more holder of a multi-seat role. */
    public function add(Request $request, Response $response, array $params = [])
    {
        $denied = $this->requirePosition('in_charge');
        if ($denied !== null) {
            return $denied;
        }

        $position = $params['position'] ?? '';
        if (!in_array($position, self::MULTI_SEATS, true)) {
            $this->redirect('/settings/handover');
            return;
        }

        return $this->render('in_charge/accounts_select', array_merge($this->commonViewData(), [
            'title' => 'Add Coordinator',
            'position' => $position,
            'holder' => null,
            'candidates' => $this->candidatesFor($position, ''),
        ]));
    }

    /**
     * Who may receive a seat. Coordinator needs Junior Staff, In-Charge needs a
     * Lecturer — and in both cases someone with no seat already, so a seat is
     * never silently taken from another holder. The outgoing holder is excluded.
     */
    private function candidatesFor(string $position, string $excludeCode): array
    {
        $rank = $position === 'coordinator' ? 'junior' : 'senior';

        return array_values(array_filter((new StaffModel())->activeByRank($rank), fn($c) =>
            $c['code'] !== $excludeCode && empty($c['position'])
        ));
    }

    /**
     * GET /settings/handover/candidates/{position}?exclude=CODE — who may take
     * the seat, as JSON, for the Change / Add panel on the Settings handover
     * tab. `exclude` is the outgoing holder (empty when adding a Coordinator).
     */
    public function candidates(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'position', 'in_charge')) {
            return;
        }

        $position = $params['position'] ?? '';
        if (!in_array($position, self::SEATS, true)) {
            $response->json(['success' => false, 'message' => 'Unknown role.'], 400);
            return;
        }

        $exclude = (string)($request->getQueryParams()['exclude'] ?? '');
        $candidates = array_map(fn($c) => [
            'code'  => $c['code'],
            'name'  => $c['name'],
            'email' => $c['email'],
            'kind'  => ($c['academic_rank'] ?? '') === 'senior' ? 'lecturer' : 'staff',
        ], $this->candidatesFor($position, $exclude));

        $response->json(['success' => true, 'candidates' => $candidates]);
    }

    /** POST /settings/handover/revoke — body: { code }. Coordinator seat only. */
    public function revoke(Request $request, Response $response)
    {
        if (!$this->guardJson($response, 'position', 'in_charge')) {
            return;
        }

        $code = (string)($request->getBody()['code'] ?? '');
        $staffModel = new StaffModel();
        $holder = $staffModel->findByCode($code);

        if (!$holder || ($holder['position'] ?? '') !== 'coordinator') {
            $response->json(['success' => false, 'message' => 'That person is not a Coordinator.'], 404);
            return;
        }
        // Someone has to run the Duty Scheduler and the Workload Matrix.
        if ($staffModel->countByPosition('coordinator') <= 1) {
            $response->json(['success' => false, 'message' => 'The department needs at least one Coordinator. Add another before revoking this one.'], 409);
            return;
        }
        if (!$staffModel->revokePosition($code, 'coordinator')) {
            $response->json(['success' => false, 'message' => 'Could not revoke the role. Please try again.'], 500);
            return;
        }

        $response->json(['success' => true]);
    }

    /** POST /settings/handover/select — starts the OTP challenge. */
    public function selectSubmit(Request $request, Response $response)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        if (!$this->guardJson($response, 'position', 'in_charge')) {
            return;
        }

        $body = $request->getBody();
        $position = $body['position'] ?? '';
        $fromCode = $body['fromCode'] ?? '';
        $toCode = $body['toCode'] ?? '';

        // An empty fromCode means "add another holder" — only for multi seats.
        $isAdd = $fromCode === '';
        if (!in_array($position, self::SEATS, true) || $toCode === ''
            || ($isAdd && !in_array($position, self::MULTI_SEATS, true))) {
            $response->json(['success' => false, 'message' => 'Please choose a replacement.'], 400);
            return;
        }
        // The list on screen is only a suggestion; check eligibility here too.
        $eligible = array_column($this->candidatesFor($position, $fromCode), 'code');
        if (!in_array($toCode, $eligible, true)) {
            $response->json(['success' => false, 'message' => 'That person cannot take this role.'], 400);
            return;
        }

        // The incoming staff member (the one receiving the new role) is who
        // needs to consciously confirm accepting the handover.
        $toStaff = (new StaffModel())->findByCode($toCode);
        if (!$toStaff) {
            $response->json(['success' => false, 'message' => 'Could not find the selected replacement.'], 404);
            return;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $demo = defined('DEMO_AUTH') && DEMO_AUTH;

        if (!$demo && !EmailService::sendOtpEmail($toStaff['email'], $otp, 'role_handover')) {
            $response->json(['success' => false, 'message' => 'Could not send the verification code. Please try again.'], 500);
            return;
        }

        $_SESSION['handover'] = [
            'position' => $position,
            'from_code' => $fromCode,
            'to_code' => $toCode,
            'otp' => $otp,
            'demo' => $demo,
            'expires_at' => time() + 300, // 5 minutes
        ];

        $response->json(['success' => true, 'redirect' => '/settings/handover/verify']);
    }

    /** GET /settings/handover/verify */
    public function verifyView(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php. The
        // handover check is separate: a valid In-Charge who has not started a
        // role change has nothing to verify, so send them back to the list.
        $denied = $this->requirePosition('in_charge');
        if ($denied !== null) {
            return $denied;
        }
        if (empty($_SESSION['handover'])) {
            $this->redirect('/settings/handover');
            return;
        }

        $handover = $_SESSION['handover'];
        $staffModel = new StaffModel();

        return $this->render('in_charge/accounts_verify', array_merge($this->commonViewData(), [
            'title' => 'Verify OTP',
            'pageTitle' => 'Verify Role Change',
            'toStaff' => $staffModel->findByCode($handover['to_code']),
        ]));
    }

    /** POST /settings/handover/verify — confirms the OTP and performs the reassignment. */
    public function verifySubmit(Request $request, Response $response)
    {
        // Guard lives on Controller now — see app/core/Controller.php. A wrong
        // position is now a 401, not the 400 it used to share with "no pending
        // change"; the two failures are genuinely different.
        if (!$this->guardJson($response, 'position', 'in_charge')) {
            return;
        }
        if (empty($_SESSION['handover'])) {
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
        $demoOk = !empty($handover['demo']) && preg_match('/^\d{6}$/', $otp);
        if (!$demoOk && ($otp === '' || !hash_equals($handover['otp'], $otp))) {
            $response->json(['success' => false, 'message' => 'Incorrect verification code.'], 400);
            return;
        }

        $staffModel = new StaffModel();
        if ($handover['from_code'] === '') {
            $ok = $staffModel->assignPosition($handover['to_code'], $handover['position']);
        } else {
            $ok = $staffModel->reassignPosition($handover['from_code'], $handover['to_code'], $handover['position']);
        }

        unset($_SESSION['handover']);

        if (!$ok) {
            $response->json(['success' => false, 'message' => 'Could not complete the role change.'], 500);
            return;
        }

        $response->json(['success' => true, 'redirect' => '/settings/handover/updated']);
    }

    /** GET /settings/handover/updated */
    public function updatedView(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requirePosition('in_charge');
        if ($denied !== null) {
            return $denied;
        }

        return $this->render('in_charge/accounts_updated', array_merge($this->commonViewData(), [
            'title' => 'Role Updated',
            'pageTitle' => 'Role Assignment',
            'holders' => (new StaffModel())->roleHolders(),
        ]));
    }
}
