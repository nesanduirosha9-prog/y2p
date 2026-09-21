<?php

namespace app\models;

use app\core\Database;
use PDO;

// TimetableSessionModel: reads/writes the scheduled class sessions that
// render as blocks on the weekly grid. A session's key is now
// (room_code, day_of_week, start_hour) instead of a surrogate id — a room
// physically can't host two sessions at once, so that combination doubles
// as a hard "no double-booking" constraint at the database level.
class TimetableSessionModel
{
    /**
     * Sessions for a department + semester + year, as a flat list:
     *   [['day' => 'mon', 'start' => 8, 'duration' => 1, 'code' => 'CS1101',
     *     'title' => '...', 'location' => '...', 'type' => 'lab'], ...]
     *
     * Shape matches what timetable/index.php expects (was TimetableController::sessions()).
     * `location` is now the room's own code (joined from `rooms`).
     */
    public function forDeptSemYear(string $dept, int $sem, int $year): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT ts.day_of_week, ts.start_hour, ts.duration_hours,
                    r.code AS room_code, ts.session_type, c.code, c.title
             FROM timetable_sessions ts
             JOIN courses c ON c.code = ts.course_code
             JOIN rooms r   ON r.code = ts.room_code
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
                'location' => $row['room_code'],
                'type' => $row['session_type'],
            ];
        }
        return $out;
    }

    /**
     * Insert a scheduled session. $data keys:
     *   room_code, course_code, department, semester, year_of_study,
     *   day_of_week, start_hour, duration_hours, session_type, managed_by_code
     * Returns true on success; fails (unique constraint) if the room is
     * already booked for that day/hour.
     */
    public function create(array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO timetable_sessions
                (room_code, day_of_week, start_hour, course_code, department,
                 semester, year_of_study, duration_hours, session_type, managed_by_code)
             VALUES
                (:room_code, :day_of_week, :start_hour, :course_code, :department,
                 :semester, :year_of_study, :duration_hours, :session_type, :managed_by_code)"
        );
        return $stmt->execute([
            'room_code' => $data['room_code'],
            'day_of_week' => $data['day_of_week'],
            'start_hour' => $data['start_hour'],
            'course_code' => $data['course_code'],
            'department' => $data['department'],
            'semester' => $data['semester'],
            'year_of_study' => $data['year_of_study'],
            'duration_hours' => $data['duration_hours'],
            'session_type' => $data['session_type'],
            'managed_by_code' => $data['managed_by_code'] ?? null,
        ]);
    }
}
