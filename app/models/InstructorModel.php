<?php

namespace app\models;

use app\core\Database;
use PDO;

// InstructorModel: teaching assistants for the instructor picker in the
// "Add / Edit Course" modal and the green badges on the Courses table.
// "Instructor" is now a per-course assignment role
// (course_staff.assignment_role = 'instructor'), not a separate table.
class InstructorModel
{
    /** The staff row for a given code, or false if none. */
    public function findByCode(string $code)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Every staff member with at least one 'instructor' assignment, as code => name. */
    public function all(): array
    {
        $pdo = Database::getConnection();
        $rows = $pdo->query(
            "SELECT DISTINCT s.code, s.name
             FROM staff s
             JOIN course_staff cs ON cs.staff_code = s.code AND cs.assignment_role = 'instructor'
             ORDER BY s.code"
        )->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[$r['code']] = $r['name'];
        }
        return $out;
    }

    /**
     * Every instructor (junior staff) with full directory details, keyed by
     * code, same shape as LecturerModel::all() for the "Junior Staff
     * Details" tab on the Staff Details screen:
     *   ['MKA' => ['name'=>..., 'dept'=>..., 'email'=>..., 'courses'=>['CS1101', ...]], ...]
     */
    public function directory(): array
    {
        $pdo = Database::getConnection();
        $rows = $pdo->query(
            "SELECT DISTINCT s.code, s.name, s.department, s.email
             FROM staff s
             JOIN course_staff cs ON cs.staff_code = s.code AND cs.assignment_role = 'instructor'
             ORDER BY s.name"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return [];
        }

        $courses = $pdo->query(
            "SELECT staff_code, course_code
             FROM course_staff
             WHERE assignment_role = 'instructor'
             ORDER BY course_code"
        )->fetchAll(PDO::FETCH_ASSOC);

        $byStaff = [];
        foreach ($courses as $row) {
            $byStaff[$row['staff_code']][] = $row['course_code'];
        }

        $out = [];
        foreach ($rows as $r) {
            $out[$r['code']] = [
                'name' => $r['name'],
                'dept' => $r['department'],
                'email' => $r['email'],
                'courses' => $byStaff[$r['code']] ?? [],
            ];
        }
        return $out;
    }
}
