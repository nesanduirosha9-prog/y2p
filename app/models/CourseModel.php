<?php

namespace app\models;

use app\core\Database;
use PDO;

// CourseModel: reads the course catalogue.
//   - forDeptYear()  backs the "Course Module" dropdown on the Timetable page
//   - listing()      backs the Course Management table
class CourseModel
{
    /**
     * Courses for a department + year of study, keyed by course code:
     *   ['CS1101' => ['title' => '...', 'lecturer' => 'Dr A, Dr B'], ...]
     *
     * `lecturer` is the comma-joined lecturer names (empty string if none) —
     * timetable/index.php uses it for the "All Lecturers" filter and the
     * schedule modal's data-lecturer attribute.
     */
    public function forDeptYear(string $dept, int $year): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT c.code, c.title,
                    COALESCE(GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', '), '') AS lecturer
             FROM courses c
             LEFT JOIN course_staff cs ON cs.course_code = c.code AND cs.assignment_role = 'lecturer'
             LEFT JOIN staff s         ON s.code = cs.staff_code
             WHERE c.department = :dept AND c.year_of_study = :year
             GROUP BY c.code, c.title
             ORDER BY c.code"
        );
        $stmt->execute(['dept' => $dept, 'year' => $year]);

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['code']] = [
                'title' => $row['title'],
                'lecturer' => $row['lecturer'],
            ];
        }
        return $out;
    }

    /** Single course by code, or null. Used when persisting a scheduled session. */
    public function findByCode(string $code): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Every course for the Course Management table, ordered by code:
     *   [['code','name','credits','year','program','lecturers'=>[codes],'instructors'=>[codes]], ...]
     */
    public function listing(): array
    {
        $pdo = Database::getConnection();
        $rows = $pdo->query(
            "SELECT code, title, credits, year_of_study, department
             FROM courses ORDER BY code"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return [];
        }

        $lecturers = $this->linkCodes('lecturer');
        $instructors = $this->linkCodes('instructor');

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'code' => $r['code'],
                'name' => $r['title'],
                'credits' => (int) $r['credits'],
                'year' => (int) $r['year_of_study'],
                'program' => strtoupper($r['department']),
                'lecturers' => $lecturers[$r['code']] ?? [],
                'instructors' => $instructors[$r['code']] ?? [],
            ];
        }
        return $out;
    }

    /** course code => [staff codes], for a given course_staff.assignment_role. */
    private function linkCodes(string $assignmentRole): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT cs.course_code, s.code
             FROM course_staff cs
             JOIN staff s ON s.code = cs.staff_code
             WHERE cs.assignment_role = :role
             ORDER BY s.code"
        );
        $stmt->execute(['role' => $assignmentRole]);

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$row['course_code']][] = $row['code'];
        }
        return $map;
    }
}
