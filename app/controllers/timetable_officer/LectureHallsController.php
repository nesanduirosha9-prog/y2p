<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;
use app\models\RoomModel;

// LectureHallsController (timetable officer): view + edit the room catalog.
// 1. index()  — GET, lists every room (guarded to timetable officers).
// 2. update() — PUT /lecture-halls/{code}, validates type/capacity from the
//    JSON body (note: only JSON bodies are parsed for non-GET/POST verbs —
//    see Request::getBody()) and persists via RoomModel::update().
class LectureHallsController extends Controller
{
    private const VALID_TYPES = ['lab', 'lecture_hall', 'tutorial_room', 'other'];

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
}
