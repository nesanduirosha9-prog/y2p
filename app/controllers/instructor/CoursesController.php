<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\core\WorkloadPrototypeData;

class CoursesController extends Controller
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

        $staffCode = $_SESSION['staff_code'] ?? 'MKA';
        $academicRank = $_SESSION['academic_rank'] ?? 'junior';
        $position = $_SESSION['position'] ?? null;
        $isLecturer = ($academicRank === 'senior' || $position === 'in_charge');

        // Master courses catalog matching database seeds and timetable_officer/courses.php
        $allCoursesCatalog = [
            [
                'code' => 'CS1101',
                'name' => 'Introduction to Programming',
                'credits' => 3,
                'year' => 1,
                'program' => 'CS',
                'lecturers' => ['DSC'],
                'lecturer_names' => ['DSC' => 'Dr. Sarah Chen'],
                'instructors' => ['MKA', 'TMF'],
                'instructor_details' => [
                    ['code' => 'MKA', 'name' => 'Mr. Kwame Addo', 'email' => 'mka@ucsc.cmb.ac.lk', 'role' => 'Lab Lead'],
                    ['code' => 'TMF', 'name' => 'Ms. Thilini Fernando', 'email' => 'tmf@ucsc.cmb.ac.lk', 'role' => 'Practical Support'],
                ],
                'sessions' => ['Lectures', 'Practicals', 'Lab Sessions'],
            ],
            [
                'code' => 'CS1102',
                'name' => 'Discrete Mathematics',
                'credits' => 3,
                'year' => 1,
                'program' => 'CS',
                'lecturers' => ['PJO'],
                'lecturer_names' => ['PJO' => 'Prof. James Osei'],
                'instructors' => ['MEM'],
                'instructor_details' => [
                    ['code' => 'MEM', 'name' => 'Ms. Efua Mensah', 'email' => 'mem@ucsc.cmb.ac.lk', 'role' => 'Tutorials Lead'],
                ],
                'sessions' => ['Lectures', 'Tutorials'],
            ],
            [
                'code' => 'CS1103',
                'name' => 'Digital Logic Design',
                'credits' => 3,
                'year' => 1,
                'program' => 'CS',
                'lecturers' => ['DAD'],
                'lecturer_names' => ['DAD' => 'Dr. Amara Diallo'],
                'instructors' => ['MKA', 'MEM'],
                'instructor_details' => [
                    ['code' => 'MKA', 'name' => 'Mr. Kwame Addo', 'email' => 'mka@ucsc.cmb.ac.lk', 'role' => 'Lab Supervisor'],
                    ['code' => 'MEM', 'name' => 'Ms. Efua Mensah', 'email' => 'mem@ucsc.cmb.ac.lk', 'role' => 'Practical Assistant'],
                ],
                'sessions' => ['Lectures', 'Practicals', 'Lab Sessions', 'Assignments'],
            ],
            [
                'code' => 'CS2201',
                'name' => 'Data Structures & Algorithms',
                'credits' => 4,
                'year' => 2,
                'program' => 'CS',
                'lecturers' => ['PRM'],
                'lecturer_names' => ['PRM' => 'Prof. Richard Mensah'],
                'instructors' => ['MAB', 'TMF'],
                'instructor_details' => [
                    ['code' => 'MAB', 'name' => 'Mr. Ato Baidoo', 'email' => 'mab@ucsc.cmb.ac.lk', 'role' => 'Practical Lead'],
                    ['code' => 'TMF', 'name' => 'Ms. Thilini Fernando', 'email' => 'tmf@ucsc.cmb.ac.lk', 'role' => 'Tutorial Support'],
                ],
                'sessions' => ['Lectures', 'Tutorials', 'Practicals', 'Assignments'],
            ],
            [
                'code' => 'CS2202',
                'name' => 'Database Systems',
                'credits' => 3,
                'year' => 2,
                'program' => 'CS',
                'lecturers' => ['DLW'],
                'lecturer_names' => ['DLW' => 'Dr. Liu Wei'],
                'instructors' => ['MYD'],
                'instructor_details' => [
                    ['code' => 'MYD', 'name' => 'Ms. Yaa Darko', 'email' => 'myd@ucsc.cmb.ac.lk', 'role' => 'Lab Supervision'],
                ],
                'sessions' => ['Lectures', 'Lab Sessions', 'Assignments'],
            ],
            [
                'code' => 'CS2203',
                'name' => 'Operating Systems',
                'credits' => 3,
                'year' => 2,
                'program' => 'CS',
                'lecturers' => ['DEP'],
                'lecturer_names' => ['DEP' => 'Dr. Elena Petrov'],
                'instructors' => ['MKO'],
                'instructor_details' => [
                    ['code' => 'MKO', 'name' => 'Mr. Kojo Amoah', 'email' => 'mko@ucsc.cmb.ac.lk', 'role' => 'Lab Lead'],
                ],
                'sessions' => ['Lectures', 'Practicals', 'Lab Sessions'],
            ],
            [
                'code' => 'CS3301',
                'name' => 'Software Engineering',
                'credits' => 4,
                'year' => 3,
                'program' => 'CS',
                'lecturers' => ['DSC', 'PKA'],
                'lecturer_names' => ['DSC' => 'Dr. Sarah Chen', 'PKA' => 'Prof. Kweku Asante'],
                'instructors' => ['MNA', 'TMF'],
                'instructor_details' => [
                    ['code' => 'MNA', 'name' => 'Ms. Nana Ama', 'email' => 'mna@ucsc.cmb.ac.lk', 'role' => 'Project Mentor'],
                    ['code' => 'TMF', 'name' => 'Ms. Thilini Fernando', 'email' => 'tmf@ucsc.cmb.ac.lk', 'role' => 'Lab Lead'],
                ],
                'sessions' => ['Lectures', 'Tutorials', 'Assignments'],
            ],
            [
                'code' => 'CS3302',
                'name' => 'Computer Networks',
                'credits' => 3,
                'year' => 3,
                'program' => 'CS',
                'lecturers' => ['DFA'],
                'lecturer_names' => ['DFA' => 'Dr. Fatima Ahmed'],
                'instructors' => ['MAT'],
                'instructor_details' => [
                    ['code' => 'MAT', 'name' => 'Mr. Atta Tetteh', 'email' => 'mat@ucsc.cmb.ac.lk', 'role' => 'Lab Lead'],
                ],
                'sessions' => ['Lectures', 'Lab Sessions'],
            ],
            [
                'code' => 'CS3303',
                'name' => 'AI & Machine Learning',
                'credits' => 3,
                'year' => 3,
                'program' => 'CS',
                'lecturers' => ['DLW', 'PDN'],
                'lecturer_names' => ['DLW' => 'Dr. Liu Wei', 'PDN' => 'Prof. David Nkrumah'],
                'instructors' => ['MEQ'],
                'instructor_details' => [
                    ['code' => 'MEQ', 'name' => 'Ms. Esi Quaye', 'email' => 'meq@ucsc.cmb.ac.lk', 'role' => 'Lab Supervision'],
                ],
                'sessions' => ['Lectures', 'Practicals', 'Assignments'],
            ],
            [
                'code' => 'CS4401',
                'name' => 'Final Year Project',
                'credits' => 6,
                'year' => 4,
                'program' => 'CS',
                'lecturers' => ['DSC'],
                'lecturer_names' => ['DSC' => 'Dr. Sarah Chen'],
                'instructors' => ['MAB', 'MKO'],
                'instructor_details' => [
                    ['code' => 'MAB', 'name' => 'Mr. Ato Baidoo', 'email' => 'mab@ucsc.cmb.ac.lk', 'role' => 'Project Evaluator'],
                    ['code' => 'MKO', 'name' => 'Mr. Kojo Amoah', 'email' => 'mko@ucsc.cmb.ac.lk', 'role' => 'Viva Assistant'],
                ],
                'sessions' => ['Lectures', 'Assignments'],
            ],
            [
                'code' => 'IS1101',
                'name' => 'Intro to Information Systems',
                'credits' => 3,
                'year' => 1,
                'program' => 'IS',
                'lecturers' => ['DKA'],
                'lecturer_names' => ['DKA' => 'Dr. Kofi Anning'],
                'instructors' => ['MAD'],
                'instructor_details' => [
                    ['code' => 'MAD', 'name' => 'Mr. Adom Boateng', 'email' => 'mad@ucsc.cmb.ac.lk', 'role' => 'Tutorials Lead'],
                ],
                'sessions' => ['Lectures', 'Tutorials'],
            ],
            [
                'code' => 'IS1103',
                'name' => 'Spreadsheet Applications',
                'credits' => 3,
                'year' => 1,
                'program' => 'IS',
                'lecturers' => ['DLO'],
                'lecturer_names' => ['DLO' => 'Dr. Linda Osei'],
                'instructors' => ['MYB'],
                'instructor_details' => [
                    ['code' => 'MYB', 'name' => 'Ms. Yaw Bediako', 'email' => 'myb@ucsc.cmb.ac.lk', 'role' => 'Lab Assistant'],
                ],
                'sessions' => ['Lectures', 'Practicals', 'Lab Sessions'],
            ],
        ];

        if (!$isLecturer) {
            // Junior staff have their courses under /workload
            $this->redirect('/workload');
            return '';
        }

        // Lecturer: show only courses assigned to this lecturer
        $assignedCourses = array_values(array_filter($allCoursesCatalog, function ($c) use ($staffCode) {
            return in_array($staffCode, $c['lecturers'], true);
        }));
        // Fallback for demo accounts if no direct match
        if (empty($assignedCourses)) {
            $assignedCourses = array_values(array_filter($allCoursesCatalog, function ($c) {
                return in_array('DSC', $c['lecturers'], true) || in_array('DAD', $c['lecturers'], true);
            }));
        }

        $pageTitle = 'My Courses';
        $pageSubtitle = 'Manage assigned course modules, evaluate supportive instructors, and review evaluation history';

        // Extract unique instructors assisting in this lecturer's assigned courses
        $instructorMap = [];
        $phoneDirectory = [
            'MKA' => '+94 77 123 4567',
            'TMF' => '+94 71 987 6543',
            'MEM' => '+94 76 234 5678',
            'MAB' => '+94 70 345 6789',
            'MYD' => '+94 75 456 7890',
            'MKO' => '+94 72 567 8901',
            'MNA' => '+94 78 678 9012',
            'MAT' => '+94 74 789 0123',
            'MEQ' => '+94 77 890 1234',
            'MAD' => '+94 71 901 2345',
            'MYB' => '+94 76 012 3456',
        ];

        foreach ($assignedCourses as $c) {
            if (!empty($c['instructor_details'])) {
                foreach ($c['instructor_details'] as $inst) {
                    $code = $inst['code'];
                    if (!isset($instructorMap[$code])) {
                        $instructorMap[$code] = [
                            'code' => $code,
                            'name' => $inst['name'],
                            'email' => $inst['email'] ?? strtolower($code) . '@ucsc.cmb.ac.lk',
                            'phone' => $phoneDirectory[$code] ?? '+94 77 000 0000',
                            'department' => 'Computer Science',
                            'designation' => 'Instructor',
                            'role' => $inst['role'] ?? 'Supportive Staff',
                            'courses' => [],
                            'status' => 'Pending Evaluation',
                        ];
                    }
                    if (!in_array($c['code'], $instructorMap[$code]['courses'], true)) {
                        $instructorMap[$code]['courses'][] = $c['code'];
                    }
                }
            }
        }

        // Evaluation History dataset for this lecturer. Dates are relative to
        // this week's Monday, so the newest one always lands in the current week.
        $evaluationHistory = [
            [
                'id' => 'eval-01',
                'date' => WorkloadPrototypeData::day(2),
                'course_code' => 'CS1101',
                'course_name' => 'Introduction to Programming',
                'instructor_code' => 'TMF',
                'instructor_name' => 'Ms. Thilini Fernando',
                'rating' => 4.8,
                'comment' => 'Very active during lab hours and assisted students in debugging recursion problems.',
                'status' => 'Evaluated',
            ],
            [
                'id' => 'eval-02',
                'date' => WorkloadPrototypeData::day(-5),
                'course_code' => 'CS1101',
                'course_name' => 'Introduction to Programming',
                'instructor_code' => 'MKA',
                'instructor_name' => 'Mr. Kwame Addo',
                'rating' => 4.5,
                'comment' => 'Punctual, well-prepared with lab worksheets, and clear explanations on pointer arithmetic.',
                'status' => 'Evaluated',
            ],
            [
                'id' => 'eval-03',
                'date' => WorkloadPrototypeData::day(-12),
                'course_code' => 'CS3301',
                'course_name' => 'Software Engineering',
                'instructor_code' => 'MNA',
                'instructor_name' => 'Ms. Nana Ama',
                'rating' => 4.2,
                'comment' => 'Guided the agile sprint reviews effectively. Good feedback given to students on Jira boards.',
                'status' => 'Evaluated',
            ],
            [
                'id' => 'eval-04',
                'date' => WorkloadPrototypeData::day(-19),
                'course_code' => 'CS4401',
                'course_name' => 'Final Year Project',
                'instructor_code' => 'MAB',
                'instructor_name' => 'Mr. Ato Baidoo',
                'rating' => 5.0,
                'comment' => 'Thorough review of architecture deliverables. Kept detailed scoring notes for project viva.',
                'status' => 'Evaluated',
            ],
            [
                'id' => 'eval-05',
                'date' => WorkloadPrototypeData::day(-26),
                'course_code' => 'CS4401',
                'course_name' => 'Final Year Project',
                'instructor_code' => 'MKO',
                'instructor_name' => 'Mr. Kojo Amoah',
                'rating' => 4.6,
                'comment' => 'Dependable and communicative. Facilitated demonstration setups smoothly.',
                'status' => 'Evaluated',
            ],
        ];

        // The week, month and semester of each record come from the shared
        // teaching calendar (the same one the Coordinator's Evaluations page
        // navigates), not from hand-typed labels that could disagree with it.
        $calendar = WorkloadPrototypeData::calendar();
        foreach ($evaluationHistory as &$eh) {
            $info = WorkloadPrototypeData::weekInfo($eh['date']);
            $eh['week_start'] = $info['start'] ?? '';
            $eh['week_label'] = $info['label'] ?? 'Outside term';
        }
        unset($eh);
        $evaluationHistory = $this->weeklyHistory($assignedCourses, $evaluationHistory, $calendar['current']);

        // Which instructors are already evaluated in the current week
        foreach ($evaluationHistory as $eh) {
            if ($eh['week_start'] === $calendar['current'] && isset($instructorMap[$eh['instructor_code']])) {
                $instructorMap[$eh['instructor_code']]['status'] = 'Evaluated';
                $instructorMap[$eh['instructor_code']]['rating'] = $eh['rating'];
                $instructorMap[$eh['instructor_code']]['evaluated_this_week'] = true;
            }
        }
        $assignedInstructors = array_values($instructorMap);

        return $this->render('instructor/my_courses', [
            'title' => $pageTitle . ' — StaffSync',
            'css_file' => ['/css/directory.css', '/css/courses.css', '/css/evaluations.css'],
            'active' => 'courses',
            'pageTitle' => $pageTitle,
            'pageSubtitle' => $pageSubtitle,
            'assignedCourses' => $assignedCourses,
            'assignedInstructors' => $assignedInstructors,
            'evaluationHistory' => $evaluationHistory,
            'calendar' => $calendar,
            'isLecturer' => $isLecturer,
            'staffCode' => $staffCode,
        ]);
    }

    /**
     * The lecturer's history as one row per instructor, per course, per past
     * teaching week — Evaluated, or Not evaluated when the week went by
     * without one. A forgotten week used to simply not appear, so a lecturer
     * had no way to see it.
     *
     * The current week is left out unless already evaluated: it is still
     * open, and its pending evaluations live on the "Evaluate by Instructor"
     * tab. It turns into a Not evaluated row once the week has passed.
     *
     * $written are the evaluations with comments; the other weeks come from
     * WorkloadPrototypeData::simulatedEvaluation(), the same rule the
     * Coordinator's Evaluations page uses. Newest week first.
     */
    private function weeklyHistory(array $courses, array $written, string $currentWeek): array
    {
        $byKey = [];
        foreach ($written as $w) {
            $byKey[$w['instructor_code'] . '|' . $w['course_code'] . '|' . $w['week_start']] = $w;
        }

        $rows = [];
        foreach (WorkloadPrototypeData::teachingWeeks() as $week) {
            foreach ($courses as $c) {
                foreach ($c['instructor_details'] ?? [] as $inst) {
                    $key = $inst['code'] . '|' . $c['code'] . '|' . $week['start'];
                    $base = [
                        'id'              => 'hist-' . substr(md5($key), 0, 10),
                        'week_start'      => $week['start'],
                        'week_label'      => $week['label'],
                        'course_code'     => $c['code'],
                        'course_name'     => $c['name'],
                        'instructor_code' => $inst['code'],
                        'instructor_name' => $inst['name'],
                    ];

                    if (isset($byKey[$key])) {
                        $rows[] = $byKey[$key];
                        unset($byKey[$key]);
                        continue;
                    }

                    $sim = WorkloadPrototypeData::simulatedEvaluation($c['code'], $inst['code'], $week['start']);
                    $isCurrent = $week['start'] === $currentWeek;
                    if ($sim !== null && !$isCurrent) {
                        $rows[] = $base + [
                            'date'    => $sim['date'],
                            'rating'  => $sim['rating'],
                            'comment' => '',
                            'status'  => 'Evaluated',
                        ];
                    } elseif ($sim === null && !$isCurrent) {
                        $rows[] = $base + [
                            'date'    => '',
                            'rating'  => null,
                            'comment' => '',
                            'status'  => 'Not evaluated',
                        ];
                    }
                }
            }
        }

        // Written evaluations for a course no longer on this lecturer's list
        // are still their history.
        foreach ($byKey as $w) {
            $rows[] = $w;
        }

        usort($rows, fn($a, $b) => strcmp($b['week_start'], $a['week_start'])
            ?: strcmp($a['course_code'], $b['course_code'])
            ?: strcmp($a['instructor_name'], $b['instructor_name']));
        return $rows;
    }
}
