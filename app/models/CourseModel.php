<?php

namespace app\models;

use app\core\Database;
use PDO;

// CourseModel: the course catalogue, plus its staff assignments (course_staff).
//   - forDeptSemYear()       backs the "Course Module" dropdowns on the Timetable page
//   - listing()              backs the Course Management table
//   - create()/update()      save a course AND replace its course_staff rows, in
//                            one transaction (see writeStaff())
//   - delete()/sessionCount() delete is only safe when sessionCount() is 0 —
//                            timetable_sessions.course_code is ON DELETE CASCADE
class CourseModel
{
    /**
     * Courses for one timetable grid (department + semester + year of study),
     * keyed by course code:
     *   ['CS1101' => ['title' => '...', 'lecturer' => 'Dr A, Dr B', 'lecturers' => ['Dr A', 'Dr B']], ...]
     *
     * Filtered by semester too, so a course added in Course Management only
     * shows on the grid of the semester it was given. `lecturer` is the
     * display string (empty if none) used by the schedule modal and the grid
     * blocks; `lecturers` is the same names as a list, for the "All Lecturers"
     * filter.
     */
    public function forDeptSemYear(string $dept, int $sem, int $year): array
    {
        $pdo = Database::getConnection();
        // 0x1F (unit separator) can't occur in a name, unlike ', '.
        $stmt = $pdo->prepare(
            "SELECT c.code, c.title,
                    COALESCE(GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR 0x1F), '') AS lecturers
             FROM courses c
             LEFT JOIN course_staff cs ON cs.course_code = c.code AND cs.assignment_role = 'lecturer'
             LEFT JOIN staff s         ON s.code = cs.staff_code
             WHERE c.department = :dept AND c.semester = :sem AND c.year_of_study = :year
             GROUP BY c.code, c.title
             ORDER BY c.code"
        );
        $stmt->execute(['dept' => $dept, 'sem' => $sem, 'year' => $year]);

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $names = $row['lecturers'] === '' ? [] : explode("\x1F", $row['lecturers']);
            $out[$row['code']] = [
                'title' => $row['title'],
                'lecturer' => implode(', ', $names),
                'lecturers' => $names,
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
     *   [['code','name','credits','year','semester','program','lecturers'=>[codes],'instructors'=>[codes]], ...]
     */
    public function listing(): array
    {
        $pdo = Database::getConnection();
        $rows = $pdo->query(
            "SELECT code, title, credits, year_of_study, semester, department
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
                'semester' => (int) $r['semester'],
                'program' => strtoupper($r['department']),
                'lecturers' => $lecturers[$r['code']] ?? [],
                'instructors' => $instructors[$r['code']] ?? [],
            ];
        }
        return $out;
    }

    /**
     * Insert a course and its staff assignments in one transaction.
     * $c must hold exactly: code, title, credits, department, year_of_study, semester.
     */
    public function create(array $c, array $lecturers, array $instructors): bool
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO courses (code, title, credits, department, year_of_study, semester)
                 VALUES (:code, :title, :credits, :department, :year_of_study, :semester)"
            )->execute($c);
            $this->writeStaff($pdo, $c['code'], $lecturers, $instructors);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Update a course's details and replace its staff assignments. The code
     * itself never changes — it's the PK, and five tables reference it with
     * no ON UPDATE CASCADE. Same $c shape as create().
     */
    public function update(array $c, array $lecturers, array $instructors): bool
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE courses
                    SET title = :title, credits = :credits, department = :department,
                        year_of_study = :year_of_study, semester = :semester
                  WHERE code = :code"
            )->execute($c);
            $this->writeStaff($pdo, $c['code'], $lecturers, $instructors);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /** Delete a course (its course_staff rows cascade). False if no row matched. */
    public function delete(string $code): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM courses WHERE code = :code");
        return $stmt->execute(['code' => $code]) && $stmt->rowCount() > 0;
    }

    /** How many timetable sessions are scheduled for this course. */
    public function sessionCount(string $code): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable_sessions WHERE course_code = :code");
        $stmt->execute(['code' => $code]);
        return (int) $stmt->fetchColumn();
    }

    /** Replace every course_staff row for one course. Runs inside the caller's transaction. */
    private function writeStaff(PDO $pdo, string $code, array $lecturers, array $instructors): void
    {
        $pdo->prepare("DELETE FROM course_staff WHERE course_code = :code")->execute(['code' => $code]);

        $insert = $pdo->prepare(
            "INSERT INTO course_staff (course_code, staff_code, assignment_role) VALUES (:course, :staff, :role)"
        );
        foreach ($lecturers as $staffCode) {
            $insert->execute(['course' => $code, 'staff' => $staffCode, 'role' => 'lecturer']);
        }
        foreach ($instructors as $staffCode) {
            $insert->execute(['course' => $code, 'staff' => $staffCode, 'role' => 'instructor']);
        }
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
