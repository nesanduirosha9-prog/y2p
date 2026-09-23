<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;

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

        if ($isLecturer) {
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
            $pageTitle = 'My Assigned Course Modules';
            $pageSubtitle = 'Courses you coordinate or lecture, with session type filters and junior staff evaluation';
        } else {
            // Instructor (Junior Staff): show only courses assigned to this instructor
            $assignedCourses = array_values(array_filter($allCoursesCatalog, function ($c) use ($staffCode) {
                return in_array($staffCode, $c['instructors'], true);
            }));
            // Fallback for demo accounts if no direct match
            if (empty($assignedCourses)) {
                $assignedCourses = array_values(array_filter($allCoursesCatalog, function ($c) {
                    return in_array('MKA', $c['instructors'], true) || in_array('TMF', $c['instructors'], true);
                }));
            }
            $pageTitle = 'My Assigned Courses';
            $pageSubtitle = 'Courses and practical modules assigned to you for supportive instruction';
        }

        return $this->render('instructor/my_courses', [
            'title' => $pageTitle . ' — StaffSync',
            'css_file' => ['/css/directory.css', '/css/courses.css', '/css/evaluations.css'],
            'active' => 'courses',
            'pageTitle' => $pageTitle,
            'pageSubtitle' => $pageSubtitle,
            'assignedCourses' => $assignedCourses,
            'isLecturer' => $isLecturer,
            'staffCode' => $staffCode,
        ]);
    }
}
