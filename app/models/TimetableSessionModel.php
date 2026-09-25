<?php

namespace app\models;

use app\core\Database;
use PDO;

// TimetableSessionModel: reads/writes the scheduled class sessions that
// render as blocks on the weekly grid. A session's key is now
// (room_code, day_of_week, start_hour) instead of a surrogate id — a room
// physically can't host two sessions at once, so that combination doubles
// as a "no double-booking" constraint at the database level. It only catches
// two sessions STARTING together, though — overlapping multi-hour sessions
// and cohort clashes are caught by findClash() before every write.
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
     * already booked for that day/hour. Called by
     * TimetableSessionsController::store(), after findClash() has passed.
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

    /** True if a session starts in this room at this day/hour. */
    public function exists(string $room, string $day, int $hour): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT 1 FROM timetable_sessions WHERE room_code = :room AND day_of_week = :day AND start_hour = :hour"
        );
        $stmt->execute(['room' => $room, 'day' => $day, 'hour' => $hour]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * The first session $d would overlap: same day, overlapping hours, and
     * either the same room (double-booked hall) or the same cohort — dept +
     * semester + year (students in two classes at once). The primary key alone
     * only stops two sessions *starting* in one room at the same hour.
     * $ignore is the original key of the session being edited, so it never
     * clashes with itself. Returns ['course_code','room_code','start_hour'] or null.
     */
    public function findClash(array $d, ?array $ignore = null): ?array
    {
        $sql = "SELECT course_code, room_code, start_hour
                FROM timetable_sessions
                WHERE day_of_week = :day
                  AND start_hour < :end_hour
                  AND start_hour + duration_hours > :start_hour
                  AND (room_code = :room
                       OR (department = :dept AND semester = :sem AND year_of_study = :year))";
        $params = [
            'day' => $d['day_of_week'],
            'start_hour' => $d['start_hour'],
            'end_hour' => $d['start_hour'] + $d['duration_hours'],
            'room' => $d['room_code'],
            'dept' => $d['department'],
            'sem' => $d['semester'],
            'year' => $d['year_of_study'],
        ];
        if ($ignore !== null) {
            $sql .= " AND NOT (room_code = :i_room AND day_of_week = :i_day AND start_hour = :i_hour)";
            $params += [
                'i_room' => $ignore['room_code'],
                'i_day' => $ignore['day_of_week'],
                'i_hour' => $ignore['start_hour'],
            ];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql . " LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Rewrite a session, including its key (room/day/hour) — i.e. move it.
     * $key is the session's ORIGINAL key. Relies on migrations 018/019
     * (ON UPDATE CASCADE) when the session has assignments or reschedule
     * requests. Caller checks exists() and findClash() first.
     */
    public function update(array $key, array $d): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE timetable_sessions
                SET room_code = :room_code, day_of_week = :day_of_week, start_hour = :start_hour,
                    course_code = :course_code, duration_hours = :duration_hours,
                    session_type = :session_type, managed_by_code = :managed_by_code
              WHERE room_code = :k_room AND day_of_week = :k_day AND start_hour = :k_hour"
        );
        return $stmt->execute([
            'room_code' => $d['room_code'],
            'day_of_week' => $d['day_of_week'],
            'start_hour' => $d['start_hour'],
            'course_code' => $d['course_code'],
            'duration_hours' => $d['duration_hours'],
            'session_type' => $d['session_type'],
            'managed_by_code' => $d['managed_by_code'] ?? null,
            'k_room' => $key['room_code'],
            'k_day' => $key['day_of_week'],
            'k_hour' => $key['start_hour'],
        ]);
    }

    /** Delete one session (its assignments/reschedule requests cascade). False if no row matched. */
    public function delete(string $room, string $day, int $hour): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "DELETE FROM timetable_sessions WHERE room_code = :room AND day_of_week = :day AND start_hour = :hour"
        );
        return $stmt->execute(['room' => $room, 'day' => $day, 'hour' => $hour]) && $stmt->rowCount() > 0;
    }
}
