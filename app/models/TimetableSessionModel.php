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

    public function forStaffManager(string $staffCode): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT ts.course_code, c.title, ts.day_of_week, ts.start_hour, ts.duration_hours, ts.session_type
             FROM timetable_sessions ts
             JOIN courses c ON c.code = ts.course_code
             WHERE ts.managed_by_code = :staffCode
             ORDER BY ts.day_of_week, ts.start_hour"
        );
        $stmt->execute(['staffCode' => $staffCode]);

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[] = [
                'course_code'    => $row['course_code'],
                'course_name'    => $row['title'],
                'day_of_week'    => $row['day_of_week'],
                'start_hour'     => (int) $row['start_hour'],
                'duration_hours' => (int) $row['duration_hours'],
                'session_type'   => $row['session_type'],
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



    public function session_request_create(array $data): bool
    {
    $pdo = Database::getConnection();

    try {
        // Begin transaction to ensure both parent request and child slots are saved together
        $pdo->beginTransaction();

        // 1. Insert the main request
        $stmt = $pdo->prepare(
            "INSERT INTO timetable_schedule_requests
                (course_code, requester_code, for_how_many_weeks, description)
             VALUES
                (:course_code, :requester_code, :for_how_many_weeks, :description)"
        );

        $stmt->execute([
            'course_code'        => $data['course_code'],
            'requester_code'     => $data['requester_code'],
            'for_how_many_weeks' => $data['for_how_many_weeks'],
            'description'        => $data['description'] ?? null,
        ]);

        // Retrieve the newly generated request ID
        $requestId = $pdo->lastInsertId();

        // 2. Insert all the associated slots
        $slotStmt = $pdo->prepare(
            "INSERT INTO timetable_schedule_request_slots
                (request_id, day_of_week, start_hour, duration_hours)
             VALUES
                (:request_id, :day_of_week, :start_hour, :duration_hours)"
        );

        foreach ($data['slots'] as $slot) {
            $slotStmt->execute([
                'request_id'     => $requestId,
                'day_of_week'    => $slot['day_of_week'],
                'start_hour'     => $slot['start_hour'],
                'duration_hours' => $slot['duration_hours'] ?? 1,
            ]);
        }

        // Commit transaction if everything succeeds
        $pdo->commit();

        return true;

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Failed to create schedule request: " . $e->getMessage());

        return false;
    }
    }



    public function getRequestsForStaff(string $staffCode): array
    {
    $pdo = \app\core\Database::getConnection();

    // Fetch both pending and approved requests.
    $stmt = $pdo->prepare("
        SELECT 
            r.course_code AS course_code,
            r.id AS request_id,
            r.status,
            r.description,
            r.for_how_many_weeks AS weeks,
            s.day_of_week,
            s.start_hour,
            s.duration_hours
        FROM timetable_schedule_requests r
        JOIN timetable_schedule_request_slots s 
            ON r.id = s.request_id
        WHERE r.requester_code = :staff_code
        ORDER BY s.day_of_week, s.start_hour
    ");

    $stmt->execute([
        'staff_code' => $staffCode
    ]);

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public function session_request_update(array $data)
    {
    $pdo = Database::getConnection();

    try {
        // 1. Fetch the existing request so we can check it exists and who owns it
        $checkStmt = $pdo->prepare(
            "SELECT requester_code FROM timetable_schedule_requests WHERE id = :id"
        );
        $checkStmt->execute(['id' => $data['request_id']]);
        $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            return 'not_found';
        }

        // 2. Only the original requester may edit their own request.
        //    (If timetable_officer should be able to edit anyone's request,
        //    pass the caller's role into $data and bypass this check for that role.)
        if ($existing['requester_code'] !== $data['requester_code']) {
            return 'forbidden';
        }

        // 3. Build a partial UPDATE from only the fields that were actually sent
        $fields = [];
        $params = ['id' => $data['request_id']];

        if ($data['for_how_many_weeks'] !== null) {
            $fields[] = 'for_how_many_weeks = :for_how_many_weeks';
            $params['for_how_many_weeks'] = $data['for_how_many_weeks'];
        }

        if ($data['description'] !== null) {
            $fields[] = 'description = :description';
            $params['description'] = $data['description'];
        }

        if (empty($fields)) {
            // Nothing to update — treat as a no-op success
            return true;
        }

        $sql = "UPDATE timetable_schedule_requests SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return true;

    } catch (\Exception $e) {
        error_log("Failed to update schedule request: " . $e->getMessage());

        return false;
    }
    }



    
    public function session_request_delete(array $data)
    {
    $pdo = Database::getConnection();

    try {
        // 1. Fetch the existing request so we can check it exists and who owns it
        $checkStmt = $pdo->prepare(
            "SELECT requester_code FROM timetable_schedule_requests WHERE id = :id"
        );
        $checkStmt->execute(['id' => $data['request_id']]);
        $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            return 'not_found';
        }

        // 2. Only the original requester may delete their own request.
        //    (If timetable_officer should be able to delete anyone's request,
        //    pass the caller's role into $data and bypass this check for that role.)
        if ($existing['requester_code'] !== $data['requester_code']) {
            return 'forbidden';
        }

        // 3. Delete in a transaction: child slots first, then the parent request
        $pdo->beginTransaction();

        $slotStmt = $pdo->prepare(
            "DELETE FROM timetable_schedule_request_slots WHERE request_id = :id"
        );
        $slotStmt->execute(['id' => $data['request_id']]);

        $reqStmt = $pdo->prepare(
            "DELETE FROM timetable_schedule_requests WHERE id = :id"
        );
        $reqStmt->execute(['id' => $data['request_id']]);

        $pdo->commit();

        return true;

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Failed to delete schedule request: " . $e->getMessage());

        return false;
    }
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
