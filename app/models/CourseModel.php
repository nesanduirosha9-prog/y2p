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
                    COALESCE(GROUP_CONCAT(l.name ORDER BY l.name SEPARATOR ', '), '') AS lecturer
             FROM courses c
             LEFT JOIN course_lecturers cl ON cl.course_id = c.id
             LEFT JOIN lecturers l         ON l.id = cl.lecturer_id
             WHERE c.department = :dept AND c.year_of_study = :year
             GROUP BY c.id, c.code, c.title
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
            "SELECT id, code, title, credits, year_of_study, department
             FROM courses ORDER BY code"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return [];
        }

        $lecturers = $this->linkCodes('course_lecturers', 'lecturer_id', 'lecturers');
        $instructors = $this->linkCodes('course_instructors', 'instructor_id', 'instructors');

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'code' => $r['code'],
                'name' => $r['title'],
                'credits' => (int) $r['credits'],
                'year' => (int) $r['year_of_study'],
                'program' => strtoupper($r['department']),
                'lecturers' => $lecturers[$r['id']] ?? [],
                'instructors' => $instructors[$r['id']] ?? [],
            ];
        }
        return $out;
    }

    /** course id => [staff codes], from a course_* join table. */
    private function linkCodes(string $joinTable, string $fkColumn, string $staffTable): array
    {
        $pdo = Database::getConnection();
        $rows = $pdo->query(
            "SELECT j.course_id, s.code
             FROM {$joinTable} j
             JOIN {$staffTable} s ON s.id = j.{$fkColumn}
             ORDER BY s.code"
        )->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['course_id']][] = $row['code'];
        }
        return $map;
    }
}
