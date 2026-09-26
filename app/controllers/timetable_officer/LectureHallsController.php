<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;
use app\models\RoomModel;

// LectureHallsController (timetable officer): full CRUD over the room catalog.
// 1. index()   — GET, lists every room (guarded to timetable officers).
// 2. store()   — POST /lecture-halls, adds a room via RoomModel::create().
// 3. update()  — PUT /lecture-halls/{code}, validates type/capacity from the
//    JSON body (note: only JSON bodies are parsed for non-GET/POST verbs —
//    see Request::getBody()) and persists via RoomModel::update().
// 4. destroy() — DELETE /lecture-halls/{code}, refused while the room has
//    timetable sessions (see RoomModel::sessionCount()).
class LectureHallsController extends Controller
{
    /** A room is a lecture hall or a lab — see migration 022. */
    private const VALID_TYPES = ['lecture_hall', 'lab'];

    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requireRole('timetable_officer');
        if ($denied !== null) {
            return $denied;
        }

        return $this->render('timetable_officer/lecture_halls', [
            'title' => 'Lecture Halls',
            'css_file' => ['/css/directory.css'],
            'active' => 'lecture-halls',
            'pageTitle' => 'Lecture Halls',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'rooms' => (new RoomModel())->all(),
        ]);
    }

    /** PUT /lecture-halls/{code} — updates a room's type/capacity. */
    public function update(Request $request, Response $response, array $params = [])
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        $code = $params['code'] ?? '';
        $body = $request->getBody();
        $type = $body['type'] ?? '';
        $capacity = filter_var($body['capacity'] ?? null, FILTER_VALIDATE_INT);

        if ($code === '' || !in_array($type, self::VALID_TYPES, true) || $capacity === false || $capacity < 1) {
            $response->json(['success' => false, 'message' => 'Invalid hall type or capacity.'], 400);
            return;
        }

        $ok = (new RoomModel())->update($code, $type, $capacity);
        if (!$ok) {
            $response->json(['success' => false, 'message' => 'Hall not found.'], 404);
            return;
        }

        $response->json(['success' => true]);
    }

    /** POST /lecture-halls — body: { code, type, capacity }. Adds a room. */
    public function store(Request $request, Response $response)
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        $body = $request->getBody();
        $code = strtoupper(trim((string)($body['code'] ?? '')));
        $type = $body['type'] ?? '';
        $capacity = filter_var($body['capacity'] ?? null, FILTER_VALIDATE_INT);

        // rooms.code is VARCHAR(20); keep it to the shape the seeds use (LT-301, LAB-A201).
        if (!preg_match('/^[A-Z0-9][A-Z0-9-]{1,19}$/', $code)) {
            $response->json(['success' => false, 'message' => 'Hall code must be 2–20 letters, digits or dashes.'], 400);
            return;
        }
        if (!in_array($type, self::VALID_TYPES, true) || $capacity === false || $capacity < 1) {
            $response->json(['success' => false, 'message' => 'Invalid hall type or capacity.'], 400);
            return;
        }

        $rooms = new RoomModel();
        if ($rooms->exists($code)) {
            $response->json(['success' => false, 'message' => "A hall called {$code} already exists."], 409);
            return;
        }
        if (!$rooms->create($code, $type, $capacity)) {
            $response->json(['success' => false, 'message' => 'Could not save the hall.'], 500);
            return;
        }

        $response->json(['success' => true, 'code' => $code]);
    }

    /**
     * DELETE /lecture-halls/{code}. Refused (409) while any timetable session
     * is booked in the room — the FK is ON DELETE CASCADE, so deleting it
     * anyway would silently wipe those sessions from the timetable.
     */
    public function destroy(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        $code = $params['code'] ?? '';
        $rooms = new RoomModel();
        if (!$rooms->exists($code)) {
            $response->json(['success' => false, 'message' => 'Hall not found.'], 404);
            return;
        }

        $booked = $rooms->sessionCount($code);
        if ($booked > 0) {
            $response->json(['success' => false, 'message' => "{$code} has {$booked} timetable session(s). Move or delete them first."], 409);
            return;
        }

        if (!$rooms->delete($code)) {
            $response->json(['success' => false, 'message' => 'Could not delete the hall.'], 500);
            return;
        }

        $response->json(['success' => true]);
    }
}
