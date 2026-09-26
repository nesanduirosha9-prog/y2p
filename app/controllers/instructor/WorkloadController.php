<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\core\WorkloadPrototypeData;
use app\models\LeaveRequestModel;
use app\models\NotificationModel;

// WorkloadController (instructor): "My Workload" page.
//
// The Overview tab is the member's workload statement for the current
// semester — the numbers they would otherwise add up by hand for a report or
// a conversation with the Coordinator. See overview(). Where each part comes
// from, and what replaces the fixtures:
//
//   courses + weekly hours   fixture $assignedCourses['slots']
//                            -> course_staff JOIN timetable_sessions
//   semester / week number   WorkloadPrototypeData::currentSemester()
//                            -> an academic_calendar table
//   last rating per course   fixture $evaluationHistory -> evaluations
//   leave taken, covers owed LIVE: LeaveRequestModel::forRequester() / coveredBy()
//
// The cover requests and evaluation history lists are still fixtures too.
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
                'slots' => [['day' => 'mon', 'from' => '09:00', 'to' => '11:00'], ['day' => 'wed', 'from' => '09:00', 'to' => '11:00']],
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
                'slots' => [['day' => 'tue', 'from' => '13:00', 'to' => '16:00']],
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
                'slots' => [['day' => 'thu', 'from' => '10:00', 'to' => '13:00']],
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
                'slots' => [['day' => 'fri', 'from' => '14:00', 'to' => '17:00']],
            ],
        ];

        // Cover requests from colleagues going on leave (was hardcoded in the
        // view; the Overview needs them too).
        $coverRequests = [
            [
                'id' => 1,
                'code' => 'CS2203',
                'title' => 'Operating Systems',
                'staff_on_leave' => 'Mr. Kojo Amoah',
                'staff_code' => 'MKO',
                'lecturer_name' => 'Dr. Elena Petrov',
                'lecturer_code' => 'DEP',
                'date' => '2026-09-30',
                'time_from' => '14:00',
                'time_to' => '16:00',
                'credits' => 3,
                'year' => 2,
                'program' => 'CS',
                'role' => 'Lab Assistant',
                'hours' => 2,
                'sessions' => ['Lectures', 'Practicals', 'Lab Sessions'],
            ],
            [
                'id' => 2,
                'code' => 'IS1103',
                'title' => 'Spreadsheet Applications',
                'staff_on_leave' => 'Ms. Yaw Bediako',
                'staff_code' => 'MYB',
                'lecturer_name' => 'Dr. Linda Osei',
                'lecturer_code' => 'DLO',
                'date' => '2026-10-01',
                'time_from' => '09:00',
                'time_to' => '12:00',
                'credits' => 3,
                'year' => 1,
                'program' => 'IS',
                'role' => 'Practical Support',
                'hours' => 3,
                'sessions' => ['Lectures', 'Practicals', 'Lab Sessions'],
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
            'coverRequests' => $coverRequests,
            'evaluationHistory' => $evaluationHistory,
            'overview' => $this->overview($_SESSION['staff_code'], $assignedCourses, $evaluationHistory),
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }

    /**
     * The Overview tab: this semester's workload statement.
     *
     *   semester  name, week number, weeks done / total
     *   courses   per course: role, schedule, h/week, hours so far, hours
     *             planned for the semester, latest rating
     *   totals    weekly hours, hours taught so far, hours planned, hours
     *             missed while on leave
     *   leave     own leave this semester (live)
     *   covering  days covering for colleagues this semester (live)
     *
     * "Taught so far" is weekly hours x finished weeks, minus the sessions
     * that fell on the member's own leave days — the same sum a
     * course_staff + timetable_sessions + leave_days query would do.
     */
    private function overview(string $me, array $courses, array $evaluations): array
    {
        $sem = WorkloadPrototypeData::currentSemester();
        $start = new \DateTimeImmutable($sem['start']);
        $end = $start->modify('+' . ($sem['weeks'] * 7 - 3) . ' days');   // Friday of the last week
        $today = new \DateTimeImmutable('today');
        $todayIso = $today->format('Y-m-d');

        // Finished teaching weeks: a week counts once its Friday has passed.
        $weeksDone = 0;
        for ($w = 0; $w < $sem['weeks']; $w++) {
            if ($start->modify('+' . ($w * 7 + 4) . ' days') < $today) {
                $weeksDone++;
            }
        }
        $info = WorkloadPrototypeData::weekInfo($todayIso);
        $inSemester = fn(string $iso) => $iso >= $sem['start'] && $iso <= $end->format('Y-m-d');

        $hoursBetween = fn(string $from, string $to) =>
            ((int)substr($to, 0, 2) * 60 + (int)substr($to, 3, 2) - (int)substr($from, 0, 2) * 60 - (int)substr($from, 3, 2)) / 60;

        // Own leave this semester (live), and the hours of own sessions it
        // took out — per course, so each course's "so far" is right.
        $leaveModel = new LeaveRequestModel();
        $leave = [];
        $missedByCourse = [];
        foreach ($leaveModel->forRequester($me) as $l) {
            $days = array_values(array_filter($l['days'], fn($d) => $inSemester($d['date'])));
            if (!$days) {
                continue;
            }
            foreach ($days as $d) {
                if ($d['date'] >= $todayIso) {
                    continue;               // not missed yet
                }
                $weekday = strtolower((new \DateTimeImmutable($d['date']))->format('D'));
                foreach ($courses as $c) {
                    foreach ($c['slots'] ?? [] as $slot) {
                        if ($slot['day'] !== $weekday) {
                            continue;
                        }
                        // A part-day leave only takes out the sessions it overlaps.
                        if ($l['time_from'] && ($slot['to'] <= $l['time_from'] || $slot['from'] >= $l['time_to'])) {
                            continue;
                        }
                        $missedByCourse[$c['code']] = ($missedByCourse[$c['code']] ?? 0) + $hoursBetween($slot['from'], $slot['to']);
                    }
                }
            }
            $leave[] = [
                'type'     => $l['leave_type'] === 'sick' ? 'Sick leave' : 'Other',
                'from'     => $days[0]['date'],
                'to'       => end($days)['date'],
                'days'     => count($days),
                'hours'    => $l['time_from'] ? $l['time_from'] . ' – ' . $l['time_to'] : 'Full day',
                'covers'   => array_values(array_unique(array_column($days, 'cover_code'))),
                'upcoming' => end($days)['date'] >= $todayIso,
            ];
        }
        usort($leave, fn($a, $b) => strcmp($a['from'], $b['from']));

        // Days covering for colleagues this semester (live).
        $covering = [];
        foreach ($leaveModel->coveredBy($me) as $l) {
            foreach ($l['days'] as $d) {
                if ($d['cover_code'] !== $me || !$inSemester($d['date'])) {
                    continue;
                }
                $covering[] = [
                    'date'     => $d['date'],
                    'for_code' => $l['requester_code'],
                    'for_name' => $l['requester_name'],
                    'hours'    => $l['time_from'] ? $l['time_from'] . ' – ' . $l['time_to'] : 'Full day',
                    'upcoming' => $d['date'] >= $todayIso,
                ];
            }
        }
        usort($covering, fn($a, $b) => strcmp($a['date'], $b['date']));

        // Per course.
        $rows = [];
        foreach ($courses as $c) {
            $weekly = 0;
            foreach ($c['slots'] ?? [] as $slot) {
                $weekly += $hoursBetween($slot['from'], $slot['to']);
            }
            $latest = null;
            foreach ($evaluations as $e) {           // newest first
                if ($e['course_code'] === $c['code'] && $e['rating'] !== null) {
                    $latest = ['rating' => (int)round($e['rating']), 'week' => $e['week']];
                    break;
                }
            }
            $missed = $missedByCourse[$c['code']] ?? 0;
            $rows[] = [
                'code'     => $c['code'],
                'name'     => $c['name'],
                'role'     => $c['role'],
                'schedule' => $c['schedule'],
                'weekly'   => $weekly,
                'done'     => max(0, $weekly * $weeksDone - $missed),
                'missed'   => $missed,
                'planned'  => $weekly * $sem['weeks'],
                'latest'   => $latest,
            ];
        }

        $sum = fn(string $k) => array_sum(array_column($rows, $k));

        return [
            'semester'  => $sem['name'] . ' ' . $sem['year'],
            'week'      => $info['number'] ?? null,
            'weeks'     => $sem['weeks'],
            'weeksDone' => $weeksDone,
            'dates'     => $start->format('j M') . ' – ' . $end->format('j M Y'),
            'courses'   => $rows,
            'weekly'    => $sum('weekly'),
            'done'      => $sum('done'),
            'planned'   => $sum('planned'),
            'missed'    => $sum('missed'),
            'leave'     => $leave,
            'leaveDays' => array_sum(array_column($leave, 'days')),
            'covering'  => $covering,
            'coverDone' => count(array_filter($covering, fn($c) => !$c['upcoming'])),
            'coverNext' => count(array_filter($covering, fn($c) => $c['upcoming'])),
        ];
    }
}
