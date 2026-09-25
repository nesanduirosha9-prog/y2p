<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\core\ViewHelpers;
use app\models\CourseModel;
use app\models\RoomModel;
use app\models\TimetableSessionModel;

// TimetableSessionsController (timetable officer): the JSON writes behind the
// weekly grid, called by js/timetable.js. The grid itself is still rendered by
// app\controllers\TimetableController (shared with academic staff) — this
// class only saves.
// 1. store()   — POST   /timetable/sessions
// 2. update()  — PUT    /timetable/sessions/{room}/{day}/{hour}  (URL = the ORIGINAL key)
// 3. destroy() — DELETE /timetable/sessions/{room}/{day}/{hour}
// Creates and edits are refused (409) if they would double-book a room or
// give one cohort two classes at once — see TimetableSessionModel::findClash().
class TimetableSessionsController extends Controller
{
    private const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri'];
    private const TYPES = ['lecture', 'tutorial', 'lab', 'practical'];
    private const DAY_START = 8;  // first grid row
    private const DAY_END = 17;   // a session must finish by 5 PM (last row is 4 PM)
    private const LUNCH = 12;     // the 12–1 PM row is the lunch break

    public function store(Request $request, Response $response)
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        [$data, $error] = $this->readSession($request);
        if ($error !== null) {
            $response->json(['success' => false, 'message' => $error], 400);
            return;
        }

        $model = new TimetableSessionModel();
        $clash = $model->findClash($data);
        if ($clash !== null) {
            $response->json(['success' => false, 'message' => $this->clashMessage($clash)], 409);
            return;
        }

        if (!$model->create($data)) {
            $response->json(['success' => false, 'message' => 'Could not save the session.'], 500);
            return;
        }

        $response->json(['success' => true]);
    }

    public function update(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        $key = $this->keyFrom($params);
        $model = new TimetableSessionModel();
        if ($key === null || !$model->exists($key['room_code'], $key['day_of_week'], $key['start_hour'])) {
            $response->json(['success' => false, 'message' => 'Session not found.'], 404);
            return;
        }

        [$data, $error] = $this->readSession($request);
        if ($error !== null) {
            $response->json(['success' => false, 'message' => $error], 400);
            return;
        }

        $clash = $model->findClash($data, $key);
        if ($clash !== null) {
            $response->json(['success' => false, 'message' => $this->clashMessage($clash)], 409);
            return;
        }

        if (!$model->update($key, $data)) {
            $response->json(['success' => false, 'message' => 'Could not save the session.'], 500);
            return;
        }

        $response->json(['success' => true]);
    }

    public function destroy(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        $key = $this->keyFrom($params);
        if ($key === null || !(new TimetableSessionModel())->delete($key['room_code'], $key['day_of_week'], $key['start_hour'])) {
            $response->json(['success' => false, 'message' => 'Session not found.'], 404);
            return;
        }

        $response->json(['success' => true]);
    }

    /** {room}/{day}/{hour} from the URL -> a session key, or null if malformed. */
    private function keyFrom(array $params): ?array
    {
        $room = $params['room'] ?? '';
        $day = $params['day'] ?? '';
        $hour = filter_var($params['hour'] ?? null, FILTER_VALIDATE_INT);
        if ($room === '' || !in_array($day, self::DAYS, true) || $hour === false) {
            return null;
        }
        return ['room_code' => $room, 'day_of_week' => $day, 'start_hour' => $hour];
    }

    /** Validates the JSON body. Returns [data, null] or [null, errorMessage]. */
    private function readSession(Request $request): array
    {
        $b = $request->getBody();
        $d = [
            'course_code'     => strtoupper(trim((string)($b['course_code'] ?? ''))),
            'room_code'       => trim((string)($b['room_code'] ?? '')),
            'day_of_week'     => (string)($b['day_of_week'] ?? ''),
            'start_hour'      => filter_var($b['start_hour'] ?? null, FILTER_VALIDATE_INT),
            'duration_hours'  => filter_var($b['duration_hours'] ?? null, FILTER_VALIDATE_INT),
            'session_type'    => (string)($b['session_type'] ?? ''),
            'department'      => (string)($b['department'] ?? ''),
            'semester'        => filter_var($b['semester'] ?? null, FILTER_VALIDATE_INT),
            'year_of_study'   => filter_var($b['year_of_study'] ?? null, FILTER_VALIDATE_INT),
            'managed_by_code' => $_SESSION['staff_code'],   // EER "Manages"
        ];

        if (!in_array($d['day_of_week'], self::DAYS, true)) {
            return [null, 'Invalid day.'];
        }
        if (!in_array($d['session_type'], self::TYPES, true)) {
            return [null, 'Invalid session type.'];
        }
        if (!in_array($d['department'], ['cs', 'is'], true)
            || !in_array($d['semester'], [1, 2], true)
            || !in_array($d['year_of_study'], [1, 2, 3, 4], true)) {
            return [null, 'Invalid department, semester or year.'];
        }
        if ($d['start_hour'] === false || $d['duration_hours'] === false
            || $d['duration_hours'] < 1 || $d['duration_hours'] > 3) {
            return [null, 'Invalid start time or duration.'];
        }

        $end = $d['start_hour'] + $d['duration_hours'];
        if ($d['start_hour'] < self::DAY_START || $end > self::DAY_END) {
            return [null, 'Sessions must run between 8 AM and 5 PM.'];
        }
        if ($d['start_hour'] <= self::LUNCH && $end > self::LUNCH) {
            return [null, 'Sessions cannot run through the 12–1 PM lunch break.'];
        }

        if (!(new RoomModel())->exists($d['room_code'])) {
            return [null, 'Choose a valid hall or lab.'];
        }
        $course = (new CourseModel())->findByCode($d['course_code']);
        if (!$course || $course['department'] !== $d['department']
            || (int)$course['year_of_study'] !== $d['year_of_study']
            || (int)$course['semester'] !== $d['semester']) {
            return [null, 'That course does not belong to this department, semester and year.'];
        }

        return [$d, null];
    }

    private function clashMessage(array $clash): string
    {
        return 'Clashes with ' . $clash['course_code'] . ' in ' . $clash['room_code']
            . ' at ' . ViewHelpers::hourLabel((int)$clash['start_hour']) . '.';
    }
}
