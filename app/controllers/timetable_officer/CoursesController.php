<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\CourseModel;
use app\models\LecturerModel;
use app\models\InstructorModel;
use app\models\NotificationModel;
use app\models\StaffModel;

// CoursesController (timetable officer): the Course Management table.
// 1. index()   — GET /courses. Guards to a logged-in timetable officer, loads
//    the full course listing plus lecturer/instructor name lookups (used to
//    render the assigned-staff badges and the Add/Edit dropdowns).
// 2. store()   — POST /courses, adds a course + its staff assignments.
// 3. update()  — PUT /courses/{code}, edits everything except the code (PK).
// 4. destroy() — DELETE /courses/{code}, refused while it is on the timetable.
// The three writes answer JSON and are called by js/courses.js.
class CoursesController extends Controller
{
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

        return $this->render('timetable_officer/course_details', [
            'title' => 'Course Details',
            'css_file' => ['/css/directory.css', '/css/courses.css'],
            'active' => 'courses',
            'pageTitle' => 'Course Details',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'courses' => (new CourseModel())->listing(),
            'lecturers' => (new LecturerModel())->all(),
            'instructors' => (new InstructorModel())->all(),
        ]);
    }

    /** POST /courses — body: { code, title, credits, year, semester, program, lecturers[], instructors[] } */
    public function store(Request $request, Response $response)
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        [$course, $lecturers, $instructors, $error] = $this->readCourse($request);
        if ($error !== null) {
            $response->json(['success' => false, 'message' => $error], 400);
            return;
        }

        $model = new CourseModel();
        if ($model->findByCode($course['code'])) {
            $response->json(['success' => false, 'message' => "Course {$course['code']} already exists."], 409);
            return;
        }
        if (!$model->create($course, $lecturers, $instructors)) {
            $response->json(['success' => false, 'message' => 'Could not save the course.'], 500);
            return;
        }

        $response->json(['success' => true]);
    }

    /** PUT /courses/{code} — same body; the code in the URL wins (codes are not editable). */
    public function update(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        [$course, $lecturers, $instructors, $error] = $this->readCourse($request);
        if ($error !== null) {
            $response->json(['success' => false, 'message' => $error], 400);
            return;
        }

        $course['code'] = $params['code'] ?? '';
        $model = new CourseModel();
        if (!$model->findByCode($course['code'])) {
            $response->json(['success' => false, 'message' => 'Course not found.'], 404);
            return;
        }
        if (!$model->update($course, $lecturers, $instructors)) {
            $response->json(['success' => false, 'message' => 'Could not save the course.'], 500);
            return;
        }

        $response->json(['success' => true]);
    }

    /**
     * DELETE /courses/{code}. Refused (409) while the course has timetable
     * sessions — the FK is ON DELETE CASCADE, so deleting it anyway would
     * silently remove them from the timetable.
     */
    public function destroy(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        $code = $params['code'] ?? '';
        $model = new CourseModel();
        if (!$model->findByCode($code)) {
            $response->json(['success' => false, 'message' => 'Course not found.'], 404);
            return;
        }

        $booked = $model->sessionCount($code);
        if ($booked > 0) {
            $response->json(['success' => false, 'message' => "{$code} has {$booked} timetable session(s). Remove them from the timetable first."], 409);
            return;
        }

        if (!$model->delete($code)) {
            $response->json(['success' => false, 'message' => 'Could not delete the course.'], 500);
            return;
        }

        $response->json(['success' => true]);
    }

    /**
     * Reads + validates the drawer's JSON body.
     * Returns [course, lecturers, instructors, errorMessageOrNull], where
     * course has exactly the keys CourseModel::create()/update() bind.
     */
    private function readCourse(Request $request): array
    {
        $b = $request->getBody();
        $course = [
            'code'          => strtoupper(trim((string)($b['code'] ?? ''))),
            'title'         => trim((string)($b['title'] ?? '')),
            'credits'       => filter_var($b['credits'] ?? null, FILTER_VALIDATE_INT),
            'department'    => strtolower((string)($b['program'] ?? '')),
            'year_of_study' => filter_var($b['year'] ?? null, FILTER_VALIDATE_INT),
            'semester'      => filter_var($b['semester'] ?? null, FILTER_VALIDATE_INT),
        ];
        $lecturers = array_values(array_unique(array_map('strval', (array)($b['lecturers'] ?? []))));
        $instructors = array_values(array_unique(array_map('strval', (array)($b['instructors'] ?? []))));

        $error = null;
        if (!preg_match('/^[A-Z0-9]{3,20}$/', $course['code'])) {
            $error = 'Course code must be 3–20 letters or digits.';
        } elseif ($course['title'] === '' || mb_strlen($course['title']) > 150) {
            $error = 'Course name is required (max 150 characters).';
        } elseif ($course['credits'] === false || $course['credits'] < 1 || $course['credits'] > 12) {
            $error = 'Credits must be between 1 and 12.';
        } elseif (!in_array($course['department'], ['cs', 'is'], true)) {
            $error = 'Choose a program (CS or IS).';
        } elseif (!in_array($course['year_of_study'], [1, 2, 3, 4], true)) {
            $error = 'Choose an academic year.';
        } elseif (!in_array($course['semester'], [1, 2], true)) {
            $error = 'Choose a semester.';
        } elseif (array_intersect($lecturers, $instructors)) {
            // course_staff's PK is (course_code, staff_code): one role per person per course.
            $error = 'One person cannot be both lecturer and instructor on the same course.';
        } else {
            $staff = new StaffModel();
            foreach (array_merge($lecturers, $instructors) as $code) {
                $s = $staff->findByCode($code);
                if (!$s || $s['role'] !== 'academic_staff' || $s['status'] !== 'active') {
                    $error = "Unknown staff member: {$code}.";
                    break;
                }
            }
        }

        return [$course, $lecturers, $instructors, $error];
    }
}
