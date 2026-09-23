<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

// WorkloadController (instructor): "My Workload" page.
// NOTE: renders the view with no workload data passed in — the
// `workload_tasks` table (migration 010) exists but nothing here queries
// it yet, so the view must be using static/placeholder content (see gaps).
class WorkloadController extends Controller
{
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

        $academicRank = $_SESSION['academic_rank'] ?? 'junior';
        $position = $_SESSION['position'] ?? null;

        // Senior lecturers without administrative roles do not use My Workload
        if ($academicRank === 'senior' && $position !== 'coordinator' && $position !== 'in_charge') {
            $this->redirect('/courses');
            return '';
        }

        $assignedCourses = [
            [
                'code' => 'CS1101',
                'name' => 'Introduction to Programming',
                'credits' => 3,
                'year' => 1,
                'program' => 'CS',
                'lecturers' => [
                    ['code' => 'DSC', 'name' => 'Dr. Sarah Chen'],
                ],
                'other_instructors' => [
                    ['code' => 'MKA', 'name' => 'Mr. Kwame Addo'],
                ],
                'role' => 'Practical Support',
                'sessions' => ['Lectures', 'Practicals', 'Lab Sessions'],
                'hours' => 4,
                'schedule' => 'Mon, Wed · 09:00 - 11:00',
            ],
            [
                'code' => 'CS2201',
                'name' => 'Data Structures & Algorithms',
                'credits' => 4,
                'year' => 2,
                'program' => 'CS',
                'lecturers' => [
                    ['code' => 'PRM', 'name' => 'Prof. Richard Mensah'],
                ],
                'other_instructors' => [
                    ['code' => 'MAB', 'name' => 'Mr. Ato Baidoo'],
                ],
                'role' => 'Tutorial Support',
                'sessions' => ['Lectures', 'Tutorials', 'Assignments'],
                'hours' => 3,
                'schedule' => 'Tue · 13:00 - 16:00',
            ],
            [
                'code' => 'CS3301',
                'name' => 'Software Engineering',
                'credits' => 4,
                'year' => 3,
                'program' => 'CS',
                'lecturers' => [
                    ['code' => 'DSC', 'name' => 'Dr. Sarah Chen'],
                    ['code' => 'PKA', 'name' => 'Prof. Kweku Asante'],
                ],
                'other_instructors' => [
                    ['code' => 'MNA', 'name' => 'Ms. Nana Ama'],
                ],
                'role' => 'Lab Lead',
                'sessions' => ['Lectures', 'Tutorials', 'Lab Sessions'],
                'hours' => 3,
                'schedule' => 'Thu · 10:00 - 13:00',
            ],
            [
                'code' => 'CS3401',
                'name' => 'Fundamentals of Computing Lab',
                'credits' => 3,
                'year' => 1,
                'program' => 'CS',
                'lecturers' => [
                    ['code' => 'DNP', 'name' => 'Dr. N. Perera'],
                ],
                'other_instructors' => [],
                'role' => 'Lab Supervisor',
                'sessions' => ['Lectures', 'Practicals', 'Lab Sessions'],
                'hours' => 3,
                'schedule' => 'Fri · 14:00 - 17:00',
            ],
        ];

        $evaluationHistory = [
            [
                'date' => '2026-03-20',
                'week' => 'Week 5',
                'month' => 'March 2026',
                'semester' => 'Semester 1 - 2026',
                'year' => '2026',
                'course_code' => 'CS3401',
                'course_name' => 'Fundamentals of Computing Lab',
                'session_type' => 'Lab Sessions',
                'rating' => null,
                'status' => 'Not Evaluated',
                'is_other' => false,
            ],
            [
                'date' => '2026-03-19',
                'week' => 'Week 5',
                'month' => 'March 2026',
                'semester' => 'Semester 1 - 2026',
                'year' => '2026',
                'course_code' => 'CS2203',
                'course_name' => 'Operating Systems',
                'session_type' => 'Cover Duty',
                'rating' => null,
                'status' => 'Not Evaluated',
                'is_other' => true,
            ],
            [
                'date' => '2026-03-18',
                'week' => 'Week 5',
                'month' => 'March 2026',
                'semester' => 'Semester 1 - 2026',
                'year' => '2026',
                'course_code' => 'CS1101',
                'course_name' => 'Introduction to Programming',
                'session_type' => 'Lab Sessions',
                'rating' => 4.8,
                'status' => 'Evaluated',
                'is_other' => false,
            ],
            [
                'date' => '2026-03-12',
                'week' => 'Week 4',
                'month' => 'March 2026',
                'semester' => 'Semester 1 - 2026',
                'year' => '2026',
                'course_code' => 'CS2201',
                'course_name' => 'Data Structures & Algorithms',
                'session_type' => 'Practicals',
                'rating' => 4.5,
                'status' => 'Evaluated',
                'is_other' => false,
            ],
            [
                'date' => '2026-03-11',
                'week' => 'Week 4',
                'month' => 'March 2026',
                'semester' => 'Semester 1 - 2026',
                'year' => '2026',
                'course_code' => 'IS1103',
                'course_name' => 'Spreadsheet Applications',
                'session_type' => 'Cover Duty',
                'rating' => 4.6,
                'status' => 'Evaluated',
                'is_other' => true,
            ],
            [
                'date' => '2026-03-05',
                'week' => 'Week 3',
                'month' => 'March 2026',
                'semester' => 'Semester 1 - 2026',
                'year' => '2026',
                'course_code' => 'CS3301',
                'course_name' => 'Software Engineering',
                'session_type' => 'Tutorials',
                'rating' => 4.2,
                'status' => 'Evaluated',
                'is_other' => false,
            ],
            [
                'date' => '2026-02-26',
                'week' => 'Week 2',
                'month' => 'February 2026',
                'semester' => 'Semester 1 - 2026',
                'year' => '2026',
                'course_code' => 'CS1101',
                'course_name' => 'Introduction to Programming',
                'session_type' => 'Practicals',
                'rating' => 4.7,
                'status' => 'Evaluated',
                'is_other' => false,
            ],
            [
                'date' => '2026-02-19',
                'week' => 'Week 1',
                'month' => 'February 2026',
                'semester' => 'Semester 1 - 2026',
                'year' => '2026',
                'course_code' => 'CS2201',
                'course_name' => 'Data Structures & Algorithms',
                'session_type' => 'Practicals',
                'rating' => 4.5,
                'status' => 'Evaluated',
                'is_other' => false,
            ],
            [
                'date' => '2025-11-20',
                'week' => 'Week 12',
                'month' => 'November 2025',
                'semester' => 'Semester 2 - 2025',
                'year' => '2025',
                'course_code' => 'CS2201',
                'course_name' => 'Data Structures & Algorithms',
                'session_type' => 'Practicals',
                'rating' => 5.0,
                'status' => 'Evaluated',
                'is_other' => false,
            ],
        ];

        return $this->render('instructor/workload', [
            'title' => 'My Workload',
            'css_file' => ['/css/directory.css', '/css/instructor/workload.css'],
            'active' => 'workload',
            'pageTitle' => 'My Workload',
            'assignedCourses' => $assignedCourses,
            'evaluationHistory' => $evaluationHistory,
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }
}
