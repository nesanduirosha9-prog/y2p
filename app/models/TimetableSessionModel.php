<?php

namespace app\models;

use app\core\Database;
use PDO;

// TimetableSessionModel: reads/writes the scheduled class sessions that
// render as blocks on the weekly grid.
class TimetableSessionModel
{
    /**
     * Sessions for a department + semester + year, as a flat list:
     *   [['day' => 'mon', 'start' => 8, 'duration' => 1, 'code' => 'CS1101',
     *     'title' => '...', 'location' => '...', 'type' => 'lab'], ...]
     *
     * Shape matches what timetable/index.php expects (was TimetableController::sessions()).
     */
    public function forDeptSemYear(string $dept, int $sem, int $year): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT ts.day_of_week, ts.start_hour, ts.duration_hours,
                    ts.location, ts.session_type, c.code, c.title
             FROM timetable_sessions ts
             JOIN courses c ON c.id = ts.course_id
             WHERE ts.department = :dept AND ts.semester = :sem AND ts.year_of_study = :year
             ORDER BY ts.day_of_week, ts.start_hour"
        );
        $stmt->execute(['dept' => $dept, 'sem' => $sem, 'year' => $year]);

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[] = [
                'day' => $row['day_of_week'],
                'start' => (int) $row['start_hour'],
                'duration' => (int) $row['duration_hours'],
                'code' => $row['code'],
                'title' => $row['title'],
                'location' => $row['location'],
                'type' => $row['session_type'],
            ];
        }
        return $out;
    }

    /**
     * Insert a scheduled session. $data keys:
     *   course_id, department, semester, year_of_study,
     *   day_of_week, start_hour, duration_hours, location, session_type
     * Returns the new row id.
     */
    public function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO timetable_sessions
                (course_id, department, semester, year_of_study,
                 day_of_week, start_hour, duration_hours, location, session_type)
             VALUES
                (:course_id, :department, :semester, :year_of_study,
                 :day_of_week, :start_hour, :duration_hours, :location, :session_type)"
        );
        $stmt->execute([
            'course_id' => $data['course_id'],
            'department' => $data['department'],
            'semester' => $data['semester'],
            'year_of_study' => $data['year_of_study'],
            'day_of_week' => $data['day_of_week'],
            'start_hour' => $data['start_hour'],
            'duration_hours' => $data['duration_hours'],
            'location' => $data['location'],
            'session_type' => $data['session_type'],
        ]);
        return (int) $pdo->lastInsertId();
    }
}
