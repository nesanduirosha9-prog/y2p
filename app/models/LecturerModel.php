<?php

namespace app\models;

use app\core\Database;
use PDO;

// LecturerModel: teaching staff for the "Lecturer Details" screen and the
// lecturer picker in the "Add / Edit Course" modal. "Lecturer" is now a
// per-course assignment role (course_staff.assignment_role = 'lecturer'),
// not a separate table — the same staff member can be a lecturer on one
// course and an instructor (TA) on another.
class LecturerModel
{
    /**
     * Every staff member with at least one 'lecturer' assignment, keyed by
     * code: ['DSC' => ['name'=>..., 'dept'=>..., 'email'=>..., 'courses'=>['CS1101', ...]], ...]
     */
    public function all(): array
    {
        $pdo = Database::getConnection();
        $rows = $pdo->query(
            "SELECT DISTINCT s.code, s.name, s.department, s.email
             FROM staff s
             JOIN course_staff cs ON cs.staff_code = s.code AND cs.assignment_role = 'lecturer'
             ORDER BY s.name"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return [];
        }

        $courses = $pdo->query(
            "SELECT staff_code, course_code
             FROM course_staff
             WHERE assignment_role = 'lecturer'
             ORDER BY course_code"
        )->fetchAll(PDO::FETCH_ASSOC);

        $byLecturer = [];
        foreach ($courses as $row) {
            $byLecturer[$row['staff_code']][] = $row['course_code'];
        }

        $out = [];
        foreach ($rows as $r) {
            $out[$r['code']] = [
                'name' => $r['name'],
                'dept' => $r['department'],
                'email' => $r['email'],
                'courses' => $byLecturer[$r['code']] ?? [],
            ];
        }
        return $out;
    }
}
