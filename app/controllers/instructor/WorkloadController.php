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
                'lecturer' => 'Dr. Sarah Chen',
                'role' => 'Practical Support',
                'sessions' => 'Practicals & Lab Sessions',
                'hours' => 4,
                'schedule' => 'Mon, Wed · 09:00 - 11:00',
            ],
            [
                'code' => 'CS2201',
                'name' => 'Data Structures & Algorithms',
                'credits' => 4,
                'year' => 2,
                'program' => 'CS',
                'lecturer' => 'Prof. Richard Mensah',
                'role' => 'Tutorial Support',
                'sessions' => 'Tutorials & Assignments',
                'hours' => 3,
                'schedule' => 'Tue · 13:00 - 16:00',
            ],
            [
                'code' => 'CS3301',
                'name' => 'Software Engineering',
                'credits' => 4,
                'year' => 3,
                'program' => 'CS',
                'lecturer' => 'Prof. Kweku Asante',
                'role' => 'Lab Lead',
                'sessions' => 'Lab Sessions',
                'hours' => 3,
                'schedule' => 'Thu · 10:00 - 13:00',
            ],
            [
                'code' => 'CS3401',
                'name' => 'Fundamentals of Computing Lab',
                'credits' => 3,
                'year' => 1,
                'program' => 'CS',
                'lecturer' => 'Dr. N. Perera',
                'role' => 'Lab Supervisor',
                'sessions' => 'Lab Sessions',
                'hours' => 3,
                'schedule' => 'Fri · 14:00 - 17:00',
            ],
        ];

        $evaluationHistory = [
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
                'evaluator_name' => 'Dr. Sarah Chen',
                'comment' => 'Excellent engagement and patient guidance during recursion lab exercises.',
                'status' => 'Evaluated',
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
                'evaluator_name' => 'Prof. Richard Mensah',
                'comment' => 'Well prepared on tree traversals, helped troubleshoot students code quickly.',
                'status' => 'Evaluated',
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
                'evaluator_name' => 'Prof. Kweku Asante',
                'comment' => 'Guided the agile sprint reviews effectively. Good feedback given to students on Jira boards.',
                'status' => 'Evaluated',
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
                'evaluator_name' => 'Dr. Sarah Chen',
                'comment' => 'Strong lab support; students completed all practical exercises within the allocated time.',
                'status' => 'Evaluated',
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
                'evaluator_name' => 'Prof. Richard Mensah',
                'comment' => 'Outstanding assistance during final project evaluations and code review sessions.',
                'status' => 'Evaluated',
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
