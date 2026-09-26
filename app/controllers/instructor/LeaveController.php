<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\LeaveRequestModel;
use app\models\NotificationModel;
use DateTime;
use PDOException;

// LeaveController (academic staff): the personal "Leave" page and its CRUD.
// Leave is approved on paper outside the system; saving here only records it
// (for history and workload planning), so there is no approval step.
// 1. index()   — GET /leave, the member's own leave + who may cover them.
// 2. store()   — POST /leave, records leave with a cover per day.
// 3. update()  — PUT /leave/{id}, edits it before its first day.
// 4. destroy() — DELETE /leave/{id}, cancels (deletes) it before its first day.
// Each change tells the affected cover staff (notifyCovers()).
//
// JSON body for store/update:
//   { leave_type: 'sick'|'other', reason: string|null,
//     time_from: 'HH:MM'|null, time_to: 'HH:MM'|null,
//     days: [{ date: 'YYYY-MM-DD', cover_code: 'ABC' }, ...] }
class LeaveController extends Controller
{
    private const MAX_DAYS = 31;

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

        $me = $_SESSION['staff_code'];
        $leave = new LeaveRequestModel();
        $tab = ($request->getQueryParams()['tab'] ?? '') === 'history' ? 'history' : 'upcoming';

        return $this->render('instructor/leave', [
            'title' => 'Leave Management',
            // staff.css for the tab bar, shared with the Leave Requests page.
            'css_file' => ['/css/directory.css', '/css/coordinator/staff.css', '/css/instructor/leave.css', '/css/leave_tables.css'],
            'active' => 'leave',
            'pageTitle' => 'Leave',
            'tab' => $tab,
            'notificationCount' => (new NotificationModel())->unreadCount($me),
            'leaveData' => [
                'today' => date('Y-m-d'),
                'rank' => $_SESSION['academic_rank'] ?? null,
                'records' => $leave->forRequester($me),
                'covers' => $leave->coverCandidates($me),
            ],
        ]);
    }

    /** POST /leave */
    public function store(Request $request, Response $response)
    {
        if (!$this->guardJson($response, 'role', 'academic_staff')) {
            return;
        }

        $me = $_SESSION['staff_code'];
        $leave = new LeaveRequestModel();
        [$data, $days, $error] = $this->readRequest($request, $leave, $me);
        if ($error !== null) {
            $response->json(['success' => false, 'message' => $error], 400);
            return;
        }

        $clashes = $leave->conflicts($me, $days);
        if ($clashes !== []) {
            $response->json(['success' => false, 'message' => implode(' ', $clashes)], 409);
            return;
        }

        try {
            $id = $leave->create($me, $data, $days);
        } catch (PDOException $e) {
            $this->failSave($response, $e);
            return;
        }

        $record = $leave->find($id);
        $this->notifyCovers(null, $record);
        $response->json(['success' => true, 'record' => $record]);
    }

    /** PUT /leave/{id} */
    public function update(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'role', 'academic_staff')) {
            return;
        }

        $me = $_SESSION['staff_code'];
        $id = $params['id'] ?? '';
        $leave = new LeaveRequestModel();
        [$data, $days, $error] = $this->readRequest($request, $leave, $me);
        if ($error !== null) {
            $response->json(['success' => false, 'message' => $error], 400);
            return;
        }

        $before = $leave->find($id);
        $clashes = $leave->conflicts($me, $days, $id);
        if ($clashes !== []) {
            $response->json(['success' => false, 'message' => implode(' ', $clashes)], 409);
            return;
        }

        try {
            $ok = $before !== null && $leave->updateUpcoming($id, $me, $data, $days);
        } catch (PDOException $e) {
            $this->failSave($response, $e);
            return;
        }
        if (!$ok) {
            $response->json(['success' => false, 'message' => 'This leave can no longer be edited. Leave can be changed up to the day before it starts.'], 409);
            return;
        }

        $record = $leave->find($id);
        $this->notifyCovers($before, $record);
        $response->json(['success' => true, 'record' => $record]);
    }

    /** DELETE /leave/{id} — cancelling a request deletes it. */
    public function destroy(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'role', 'academic_staff')) {
            return;
        }

        $me = $_SESSION['staff_code'];
        $id = $params['id'] ?? '';
        $leave = new LeaveRequestModel();
        $record = $leave->find($id);

        if (!$record || !$leave->deleteOwn($id, $me)) {
            $response->json(['success' => false, 'message' => 'This leave can no longer be cancelled. Leave can be cancelled up to the day before it starts.'], 409);
            return;
        }

        $this->notifyCovers($record, null);
        $response->json(['success' => true]);
    }

    // --- internals -----------------------------------------------------------

    /**
     * Validates the JSON body shared by store() and update().
     * Returns [$data, $days, null] on success, or [null, null, $message].
     */
    private function readRequest(Request $request, LeaveRequestModel $leave, string $me): array
    {
        $body = $request->getBody();
        $fail = fn(string $message) => [null, null, $message];

        $type = $body['leave_type'] ?? '';
        if (!in_array($type, LeaveRequestModel::TYPES, true)) {
            return $fail('Choose a leave type.');
        }

        $reason = trim((string)($body['reason'] ?? ''));
        if (mb_strlen($reason) > 500) {
            return $fail('Keep the reason under 500 characters.');
        }

        $timeFrom = $body['time_from'] ?? null;
        $timeTo = $body['time_to'] ?? null;
        if ($timeFrom !== null || $timeTo !== null) {
            $hhmm = '/^([01]\d|2[0-3]):[0-5]\d$/';
            if (!preg_match($hhmm, (string)$timeFrom) || !preg_match($hhmm, (string)$timeTo) || $timeFrom >= $timeTo) {
                return $fail('The end time must be after the start time.');
            }
        }

        $rawDays = $body['days'] ?? null;
        if (!is_array($rawDays) || $rawDays === []) {
            return $fail('Pick at least one date.');
        }
        if (count($rawDays) > self::MAX_DAYS) {
            return $fail('A single request can include at most ' . self::MAX_DAYS . ' days.');
        }

        $covers = array_column($leave->coverCandidates($me), 'name', 'code');
        $today = date('Y-m-d');
        $days = [];
        foreach ($rawDays as $day) {
            $date = is_array($day) ? (string)($day['date'] ?? '') : '';
            $cover = is_array($day) ? (string)($day['cover_code'] ?? '') : '';

            $parsed = DateTime::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                return $fail('One of the dates is not valid.');
            }
            if ($date < $today) {
                return $fail('Leave cannot be requested for a past date.');
            }
            if (isset($days[$date])) {
                return $fail('The same date is listed twice.');
            }
            if (!isset($covers[$cover])) {
                return $fail('Choose a cover person for ' . $parsed->format('D j M') . '.');
            }
            $days[$date] = ['date' => $date, 'cover_code' => $cover];
        }
        ksort($days);

        return [[
            'leave_type' => $type,
            'reason' => $reason === '' ? null : $reason,
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
        ], array_values($days), null];
    }

    /**
     * Tells cover staff what changed between two versions of a leave ($before
     * null = just recorded, $after null = cancelled): each cover whose dates
     * changed gets their new dates, each cover who was dropped is told they
     * are no longer needed. Someone whose dates did not change hears nothing.
     */
    private function notifyCovers(?array $before, ?array $after): void
    {
        $datesOf = function (?array $record): array {
            $by = [];
            foreach ($record['days'] ?? [] as $day) {
                $by[$day['cover_code']][] = date('D j M', strtotime($day['date']));
            }
            return $by;
        };
        $old = $datesOf($before);
        $new = $datesOf($after);
        $name = ($after ?? $before)['requester_name'];
        $notifications = new NotificationModel();

        foreach ($new as $cover => $dates) {
            if (($old[$cover] ?? null) === $dates) {
                continue;
            }
            $notifications->send([$cover], 'info', 'You are covering a leave',
                "You are covering {$name} on " . implode(', ', $dates) . '.');
        }
        foreach (array_diff_key($old, $new) as $cover => $dates) {
            $notifications->send([$cover], 'info', 'Leave cover no longer needed',
                "You no longer need to cover {$name} on " . implode(', ', $dates) . '.');
        }
    }

    /** A unique-key clash means another submission got there first. */
    private function failSave(Response $response, PDOException $e): void
    {
        if ($e->getCode() === '23000') {
            $response->json(['success' => false, 'message' => 'One of these dates was just taken — you or a cover person already has leave or a cover on it. Reload and try again.'], 409);
            return;
        }
        $response->json(['success' => false, 'message' => 'Could not save the leave request.'], 500);
    }
}
