<?php

namespace app\core;

// WorkloadPrototypeData — the single fixture source behind the Workload Matrix,
// the Duty Scheduler and the Evaluations dashboard.
//
// WHY THIS FILE EXISTS
// Those three screens used to carry ~600 lines of literal arrays inside their
// views, and they disagreed: the matrix claimed "18 junior staff / 14.2h target
// / 3 flagged issues" while the scheduler claimed "18 active / week 5 / 1
// overlap", all typed by hand. In the spreadsheet this replaces
// (CurrentViews/Duty_Allocator_V8_25_II) there is one roster and one running
// duty count, and assigning a duty in the scheduler is exactly what moves
// someone up the matrix's load ranking. This file restores that: one roster,
// one set of courses, one availability grid, and loads DERIVED from them by
// staffLoad() rather than typed in.
//
// HOW IT DIES
// Every method here is the shape the corresponding model will return, so the
// backend lands one method at a time with no change to the views or the JS:
//
//   staff()        -> StaffModel::allocatableStaff()
//   courses()      -> CourseWorkloadModel::forDepartment()   (needs
//                     course_staff.engagement_type — see the plan)
//   availability() -> AvailabilityModel::grid()              (needs the
//                     staff_availability table)
//   leave()        -> LeaveModel::approvedBetween()          (leave_requests
//                     ALREADY exists — this is the cheapest one to make real)
//   requests() / duties() -> DutyModel                       (needs duties +
//                     duty_assignments)
//   evaluations()  -> EvaluationModel::submitted()
//   calendar()     -> AcademicCalendarModel (semester start + length)
//   allocationHistory() -> AllocationHistoryModel            (needs
//                     duty_assignments with how/note, and dated
//                     course_staff rows instead of a current-only list)
//
// Nothing here writes anywhere. The prototype's edits live in the browser.
class WorkloadPrototypeData
{
    // The five engagement types from the spreadsheet's P/T/AM column. Keys are
    // what the future course_staff.engagement_type ENUM should hold.
    public const ENGAGEMENTS = [
        'practical'             => 'Practicals',
        'tutorial'              => 'Tutorials',
        'assignment_marking'    => 'Assignment Marking',
        'coordination_lecture'  => 'Coordination (Lecture)',
        'coordination_project'  => 'Coordination (Projects)',
    ];

    // The teaching day, as hour-range labels. Matches the timetable grid
    // (8 AM – 4 PM, last block 16:00-17:00) and the spreadsheet's Time column.
    public const SLOTS = ['8-9', '9-10', '10-11', '11-12', '12-1', '1-2', '2-3', '3-4'];

    /** Hour each slot starts, 24h. Used by the allocator to compare slots. */
    public const SLOT_HOURS = [
        '8-9' => 8, '9-10' => 9, '10-11' => 10, '11-12' => 11,
        '12-1' => 12, '1-2' => 13, '2-3' => 14, '3-4' => 15,
    ];

    public const WEEKDAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday'];

    /**
     * Junior staff eligible for allocation — the spreadsheet's NameLists sheet.
     * `active` is its column G; `paused` is column H (temporarily deactivated),
     * a different axis from staff.status, which is registration approval.
     */
    public static function staff(): array
    {
        return [
            ['code' => 'TSR', 'name' => 'T. S. Rathnayake',     'active' => true,  'paused' => false],
            ['code' => 'BMC', 'name' => 'B. M. Cooray',          'active' => true,  'paused' => false],
            ['code' => 'PRL', 'name' => 'P. R. Liyanage',        'active' => true,  'paused' => false],
            ['code' => 'NNE', 'name' => 'N. N. Ekanayake',       'active' => true,  'paused' => false],
            ['code' => 'WDI', 'name' => 'W. D. Illangasinghe',   'active' => true,  'paused' => false],
            ['code' => 'NJN', 'name' => 'N. J. Nanayakkara',     'active' => true,  'paused' => false],
            ['code' => 'DUH', 'name' => 'D. U. Hettiarachchi',   'active' => true,  'paused' => false],
            ['code' => 'TSH', 'name' => 'T. Sharnitha',          'active' => true,  'paused' => false],
            ['code' => 'WIJ', 'name' => 'W. I. Jayawardena',     'active' => true,  'paused' => false],
            ['code' => 'AMJ', 'name' => 'A. M. Jayasuriya',      'active' => true,  'paused' => false],
            ['code' => 'GLS', 'name' => 'G. L. Samaranayake',    'active' => true,  'paused' => false],
            ['code' => 'MAS', 'name' => 'M. A. Senanayake',      'active' => true,  'paused' => false],
            ['code' => 'MVT', 'name' => 'M. V. Thennakoon',      'active' => true,  'paused' => false],
            ['code' => 'ADM', 'name' => 'A. D. Meegoda',         'active' => true,  'paused' => false],
            ['code' => 'AYS', 'name' => 'A. Y. Samarasinghe',    'active' => true,  'paused' => false],
            ['code' => 'UPE', 'name' => 'U. P. Ekanayake',       'active' => true,  'paused' => false],
            ['code' => 'KST', 'name' => 'K. S. Thilakarathne',   'active' => true,  'paused' => true],
            ['code' => 'JRA', 'name' => 'J. R. Amarasekara',     'active' => false, 'paused' => false],
        ];
    }

    /**
     * The semester allocation — the spreadsheet's Workload sheet.
     * One row per course x engagement type, which is why the same course code
     * can appear twice with different engagements.
     */
    public static function courses(): array
    {
        return [
            ['code' => 'SCS 1308', 'name' => 'Foundations of Algorithms',              'year' => 1, 'program' => 'CS', 'lecturer' => 'DKF', 'lecturer_name' => 'Dr. K. Fernando',       'engagement' => 'tutorial',           'hours' => 2, 'target' => 5, 'instructors' => ['TSR', 'BMC', 'PRL', 'NNE', 'WDI'], 'notes' => []],
            ['code' => 'SCS 1309', 'name' => 'Database Management Systems',            'year' => 1, 'program' => 'CS', 'lecturer' => 'ENO', 'lecturer_name' => 'Dr. E. Osei',           'engagement' => 'practical',          'hours' => 3, 'target' => 5, 'instructors' => ['NJN', 'DUH', 'TSH', 'WIJ'],        'notes' => ['DUH' => 'Lab Lead', 'TSH' => 'Lab Setup Lead']],
            ['code' => 'SCS 1310', 'name' => 'Object Oriented Modelling and Programming', 'year' => 1, 'program' => 'CS', 'lecturer' => 'LNC', 'lecturer_name' => 'Dr. L. Nanayakkara', 'engagement' => 'practical',          'hours' => 3, 'target' => 5, 'instructors' => ['GLS', 'ADM', 'AYS', 'MVT', 'UPE'], 'notes' => []],
            ['code' => 'SCS 1311', 'name' => 'Internet and Web Technologies',          'year' => 1, 'program' => 'CS', 'lecturer' => 'GSR', 'lecturer_name' => 'Prof. G. Ranasinghe',   'engagement' => 'practical',          'hours' => 3, 'target' => 4, 'instructors' => ['UPE', 'MVT', 'ADM', 'PRL'],        'notes' => []],
            ['code' => 'SCS 1312', 'name' => 'Operating System Concepts',              'year' => 1, 'program' => 'CS', 'lecturer' => 'CIK', 'lecturer_name' => 'Dr. C. Iddamalgoda',    'engagement' => 'practical',          'hours' => 3, 'target' => 5, 'instructors' => ['TSH', 'MVT', 'NNE', 'BMC', 'MAS'], 'notes' => []],
            ['code' => 'EN 1203',  'name' => 'Aesthetic Studies',                      'year' => 1, 'program' => 'CS', 'lecturer' => 'SDA', 'lecturer_name' => 'Dr. S. D. Abeywardena', 'engagement' => 'coordination_lecture', 'hours' => 1, 'target' => 5, 'instructors' => ['JRA', 'NJN', 'DUH', 'KST'],      'notes' => []],

            ['code' => 'IS 1208',  'name' => 'Systems Analysis and Design',            'year' => 1, 'program' => 'IS', 'lecturer' => 'CRW', 'lecturer_name' => 'Dr. C. Wickramasinghe', 'engagement' => 'tutorial',           'hours' => 2, 'target' => 3, 'instructors' => ['AMJ', 'KST'],                      'notes' => []],
            ['code' => 'IS 1209',  'name' => 'IT Project Management',                  'year' => 1, 'program' => 'IS', 'lecturer' => 'DGS', 'lecturer_name' => 'Dr. D. G. Silva',       'engagement' => 'practical',          'hours' => 3, 'target' => 4, 'instructors' => ['ADM', 'UPE'],                      'notes' => []],
            ['code' => 'IS 1210',  'name' => 'Database Systems',                       'year' => 1, 'program' => 'IS', 'lecturer' => 'ENO', 'lecturer_name' => 'Dr. E. Osei',           'engagement' => 'practical',          'hours' => 3, 'target' => 4, 'instructors' => ['WDI', 'KST', 'JRA'],               'notes' => []],
            ['code' => 'IS 1211',  'name' => 'Computer Networks',                      'year' => 1, 'program' => 'IS', 'lecturer' => 'CIK', 'lecturer_name' => 'Dr. C. Iddamalgoda',    'engagement' => 'practical',          'hours' => 3, 'target' => 5, 'instructors' => ['NNE', 'ADM', 'WIJ', 'TSH', 'MAS'], 'notes' => []],
            ['code' => 'IS 1212',  'name' => 'Probability and Statistics',             'year' => 1, 'program' => 'IS', 'lecturer' => 'NPK', 'lecturer_name' => 'Dr. N. P. Karunaratne', 'engagement' => 'tutorial',           'hours' => 2, 'target' => 4, 'instructors' => ['GLS', 'AMJ'],                      'notes' => []],
            ['code' => 'IS 1214',  'name' => 'Data Structures and Algorithms',         'year' => 1, 'program' => 'IS', 'lecturer' => 'NAS', 'lecturer_name' => 'Dr. N. A. Silva',       'engagement' => 'practical',          'hours' => 3, 'target' => 6, 'instructors' => ['DUH', 'AMJ', 'PRL', 'WDI'],        'notes' => []],

            ['code' => 'SCS 2310', 'name' => 'Digital Signal Processing',              'year' => 2, 'program' => 'CS', 'lecturer' => 'ASA', 'lecturer_name' => 'Dr. A. S. Alahakoon',   'engagement' => 'assignment_marking', 'hours' => 2, 'target' => 4, 'instructors' => ['BMC', 'GLS'],                      'notes' => []],
            ['code' => 'SCS 2311', 'name' => 'Cryptography and Information Security',  'year' => 2, 'program' => 'CS', 'lecturer' => 'TNK', 'lecturer_name' => 'Dr. T. N. Kumara',      'engagement' => 'practical',          'hours' => 3, 'target' => 5, 'instructors' => ['NJN', 'DUH', 'GLS'],               'notes' => []],
            ['code' => 'SCS 2312', 'name' => 'Computational Models',                   'year' => 2, 'program' => 'CS', 'lecturer' => 'MIE', 'lecturer_name' => 'Dr. M. Ieshan',         'engagement' => 'tutorial',           'hours' => 2, 'target' => 5, 'instructors' => ['WDI', 'KST'],                      'notes' => []],
            ['code' => 'SCS 2313', 'name' => 'Computer System Architecture',           'year' => 2, 'program' => 'CS', 'lecturer' => 'KGG', 'lecturer_name' => 'Dr. K. G. Gunawardena', 'engagement' => 'assignment_marking', 'hours' => 2, 'target' => 3, 'instructors' => ['TSR', 'NNE'],                      'notes' => []],
            ['code' => 'SCS 2314', 'name' => 'Middleware Architecture',                'year' => 2, 'program' => 'CS', 'lecturer' => 'CRC', 'lecturer_name' => 'Dr. C. R. Chandrasiri',  'engagement' => 'assignment_marking', 'hours' => 2, 'target' => 4, 'instructors' => ['NNE', 'JRA', 'BMC'],               'notes' => []],
            ['code' => 'SCS 2315', 'name' => 'Electronics and Physical Computing',     'year' => 2, 'program' => 'CS', 'lecturer' => 'HBE', 'lecturer_name' => 'Dr. H. B. Ekanayake',    'engagement' => 'tutorial',           'hours' => 2, 'target' => 5, 'instructors' => ['MAS', 'BMC', 'NJN'],               'notes' => []],

            ['code' => 'IS 2208',  'name' => 'Information Systems Management',         'year' => 2, 'program' => 'IS', 'lecturer' => 'GSR', 'lecturer_name' => 'Prof. G. Ranasinghe',   'engagement' => 'assignment_marking', 'hours' => 2, 'target' => 3, 'instructors' => ['UPE', 'WIJ', 'NNE'],               'notes' => []],
            ['code' => 'IS 2209',  'name' => 'Data Management and Governance',         'year' => 2, 'program' => 'IS', 'lecturer' => 'YSR', 'lecturer_name' => 'Dr. Y. S. Rajapaksha',  'engagement' => 'tutorial',           'hours' => 2, 'target' => 6, 'instructors' => ['WIJ', 'AYS', 'JRA', 'GLS'],        'notes' => []],
            ['code' => 'IS 2210',  'name' => 'Applied Data Science',                   'year' => 2, 'program' => 'IS', 'lecturer' => 'KTK', 'lecturer_name' => 'Dr. K. T. Kariyawasam',  'engagement' => 'tutorial',           'hours' => 2, 'target' => 4, 'instructors' => ['WDI', 'DUH', 'TSR', 'WIJ'],        'notes' => []],
            ['code' => 'IS 2211',  'name' => 'UI/UX Design',                           'year' => 2, 'program' => 'IS', 'lecturer' => 'DGS', 'lecturer_name' => 'Dr. D. G. Silva',       'engagement' => 'practical',          'hours' => 3, 'target' => 4, 'instructors' => ['TSR', 'PRL'],                      'notes' => []],
            ['code' => 'IS 2212',  'name' => 'Cloud Infrastructure and Applications',  'year' => 2, 'program' => 'IS', 'lecturer' => 'KMT', 'lecturer_name' => 'Dr. K. M. Thilina',     'engagement' => 'assignment_marking', 'hours' => 2, 'target' => 4, 'instructors' => ['DUH', 'WDI', 'WIJ'],               'notes' => []],

            ['code' => 'SCS 3205', 'name' => 'Software Engineering',                   'year' => 3, 'program' => 'CS', 'lecturer' => 'NHP', 'lecturer_name' => 'Dr. N. H. Perera',      'engagement' => 'coordination_project', 'hours' => 2, 'target' => 5, 'instructors' => ['ADM', 'MVT'],                    'notes' => []],
            ['code' => 'IS 3202',  'name' => 'Enterprise Systems',                     'year' => 3, 'program' => 'IS', 'lecturer' => 'SMG', 'lecturer_name' => 'Dr. S. M. Gamage',      'engagement' => 'coordination_project', 'hours' => 2, 'target' => 4, 'instructors' => ['AYS', 'MAS', 'AMJ'],             'notes' => []],
        ];
    }

    /**
     * Weekly availability grid — the spreadsheet's Monday..Friday sheets, where
     * each was a person x time-slot sheet of TRUE/FALSE. Here: free slots per
     * weekday. Anything not listed is unavailable.
     *
     * Future shape: SELECT weekday, start_hour FROM staff_availability
     *               WHERE staff_code = ?
     */
    public static function availability(): array
    {
        // Compact generator so the fixture stays readable. Each staff member
        // gets a deterministic pattern seeded off their code, which keeps the
        // demo stable between reloads while still looking varied.
        $all = self::SLOTS;
        $grid = [];
        foreach (self::staff() as $i => $s) {
            $row = [];
            foreach (array_keys(self::WEEKDAYS) as $d => $day) {
                $free = [];
                foreach ($all as $j => $slot) {
                    // Deterministic pseudo-pattern: ~65% free, varying by
                    // person and weekday.
                    if ((($i * 7) + ($d * 3) + ($j * 5)) % 11 > 3) {
                        $free[] = $slot;
                    }
                }
                $row[$day] = array_values($free);
            }
            $grid[$s['code']] = $row;
        }

        // Make the grid agree with the duties that ship already assigned.
        // Without this the board opens showing staff rostered onto slots the
        // availability tab says they are busy in, and the prototype looks
        // broken rather than opinionated. The ONE deliberate inconsistency is
        // TSH on Tuesday: they are on leave that day but pre-assigned to
        // duty-2, so the board has a real conflict to detect on first load.
        //
        // A leave cover counts too: they agreed to stand in for that person,
        // so they are free for the duties they inherit — which is what lets
        // the board offer "Use NJN" for TSH instead of a dead end.
        foreach (self::duties() as $duty) {
            $weekday = self::weekdayKeyFor($duty['date']);
            if ($weekday === null) {
                continue;
            }
            foreach ($duty['assigned'] as $code) {
                foreach (array_filter([$code, self::coverFor($code, $duty['date'])]) as $who) {
                    if (!isset($grid[$who])) {
                        continue;
                    }
                    $grid[$who][$weekday] = array_values(array_unique(
                        array_merge($grid[$who][$weekday], $duty['slots'])
                    ));
                }
            }
        }

        return $grid;
    }

    /** 'mon'..'fri' for an ISO date, or null at the weekend. */
    private static function weekdayKeyFor(string $iso): ?string
    {
        $key = strtolower((new \DateTime($iso))->format('D'));
        return isset(self::WEEKDAYS[$key]) ? $key : null;
    }

    /**
     * Approved leave. The allocator must exclude anyone whose leave covers the
     * duty date — CurrentViews/script.js does this via getLeaveMap().
     *
     * `cover` is the per-day cover the instructor names when applying (the
     * "Cover Staff by Date" step in instructor/leave.php), keyed by ISO date.
     * The scheduler swaps that person in for any duty the absentee holds.
     *
     * Future shape: LeaveModel::approvedBetween($from, $to) over the EXISTING
     * leave_requests table (migration 009). This is the cheapest gap to close.
     */
    public static function leave(): array
    {
        return [
            ['code' => 'TSH', 'from' => self::day(1), 'to' => self::day(1), 'reason' => 'Medical',
             'cover' => [self::day(1) => 'NJN']],
            ['code' => 'GLS', 'from' => self::day(2), 'to' => self::day(4), 'reason' => 'Annual',
             'cover' => [self::day(2) => 'AYS', self::day(3) => 'AYS', self::day(4) => 'WIJ']],
            ['code' => 'MVT', 'from' => self::day(3), 'to' => self::day(3), 'reason' => 'Personal',
             'cover' => [self::day(3) => 'ADM']],
        ];
    }

    /** Who covers $code on $iso according to their leave, or null. */
    private static function coverFor(string $code, string $iso): ?string
    {
        foreach (self::leave() as $l) {
            if ($l['code'] === $code && $iso >= $l['from'] && $iso <= $l['to']) {
                return $l['cover'][$iso] ?? null;
            }
        }
        return null;
    }

    /** Lecturer duty requests awaiting triage — the spreadsheet's Request sheet. */
    public static function requests(): array
    {
        return [
            ['id' => 'req-1', 'requester' => 'AYS', 'requester_name' => 'W. M. A. Sanahari',     'course' => 'IS 4115',  'duty' => 'In-class Assignment',            'date' => self::day(0), 'slots' => ['10-11', '11-12'],                                  'headcount' => 3,  'note' => ''],
            ['id' => 'req-2', 'requester' => 'AMD', 'requester_name' => 'Amod Pathirana',        'course' => 'SCS 2314', 'duty' => 'Middleware In-class Assessment', 'date' => self::day(1), 'slots' => ['1-2', '2-3'],                                      'headcount' => 4,  'note' => ''],
            ['id' => 'req-3', 'requester' => 'NPK', 'requester_name' => 'Dr. N. P. Karunaratne', 'course' => 'IS 1212',  'duty' => 'Probability Lab Quiz',           'date' => self::day(2), 'slots' => ['8-9', '9-10'],                                     'headcount' => 3,  'note' => 'Two lab rooms in parallel'],
            ['id' => 'req-4', 'requester' => 'TSR', 'requester_name' => 'T. S. Rathnayake',      'course' => 'SCS 2313', 'duty' => 'Architecture Lab Test',          'date' => self::day(3), 'slots' => ['1-2', '2-3'],                                      'headcount' => 4,  'note' => ''],
            ['id' => 'req-5', 'requester' => 'PDW', 'requester_name' => 'Prof. D. Wijesekara',   'course' => 'SCS 2312', 'duty' => 'Computational Models Evaluation','date' => self::day(4), 'slots' => ['8-9', '9-10'],                                     'headcount' => 6,  'note' => ''],
            ['id' => 'req-6', 'requester' => 'MAS', 'requester_name' => 'Dr. M. A. Silva',       'course' => 'IS 4101',  'duty' => 'Final Year Project Vivas',       'date' => self::day(4), 'slots' => ['8-9', '9-10', '10-11', '11-12', '12-1', '1-2', '2-3'], 'headcount' => 10, 'note' => 'All-day panel'],
        ];
    }

    /**
     * Duties already scheduled for the active week — the spreadsheet's Main
     * sheet. `assigned` empty means the allocator has not run for that row.
     */
    public static function duties(): array
    {
        return [
            ['id' => 'duty-1', 'requester' => 'DKF', 'requester_name' => 'Dr. K. Fernando',     'course' => 'SCS 1308', 'course_name' => 'Foundations of Algorithms',   'duty' => 'Tutorial Session',      'date' => self::day(0), 'slots' => ['10-11', '11-12'], 'headcount' => 3, 'assigned' => ['TSR', 'BMC', 'PRL']],
            ['id' => 'duty-2', 'requester' => 'CIK', 'requester_name' => 'Dr. C. Iddamalgoda',  'course' => 'SCS 1312', 'course_name' => 'Operating System Concepts',   'duty' => 'Practical Lab','date' => self::day(1), 'slots' => ['8-9', '9-10'],    'headcount' => 3, 'assigned' => ['TSH', 'MVT', 'NNE']],
            ['id' => 'duty-3', 'requester' => 'NAS', 'requester_name' => 'Dr. N. A. Silva',     'course' => 'IS 1214',  'course_name' => 'Data Structures and Algorithms', 'duty' => 'Lab Exam & Practical Evaluation', 'date' => self::day(2), 'slots' => ['1-2', '2-3'],     'headcount' => 3, 'assigned' => []],
            ['id' => 'duty-4', 'requester' => 'CRC', 'requester_name' => 'Dr. C. R. Chandrasiri','course' => 'SCS 2314','course_name' => 'Middleware Architecture',      'duty' => 'RPC Practical Supervision',         'date' => self::day(3), 'slots' => ['10-11', '11-12'], 'headcount' => 3, 'assigned' => []],
            ['id' => 'duty-5', 'requester' => 'ENO', 'requester_name' => 'Dr. E. Osei',         'course' => 'SCS 1309', 'course_name' => 'Database Management Systems', 'duty' => 'SQL Lab Assessment',                'date' => self::day(1), 'slots' => ['8-9'],            'headcount' => 2, 'assigned' => []],
        ];
    }

    /**
     * The active week: the one today falls in, Monday to Friday. Everything
     * dated "this week" across the prototype hangs off this, so the demo
     * always opens on the real current week.
     */
    public static function week(): array
    {
        $from = self::day(0);
        $to = self::day(4);
        $info = self::weekInfo($from);
        return [
            'number' => $info['number'] ?? 0,
            'from'   => $from,
            'to'     => $to,
            'label'  => (new \DateTimeImmutable($from))->format('j M') . ' – ' . (new \DateTimeImmutable($to))->format('j M Y'),
        ];
    }

    /**
     * An ISO date relative to this week's Monday: day(0) is Monday, day(4)
     * Friday, day(-7) last Monday. Fixtures use it instead of literal dates
     * so they stay "this week" whenever the demo is opened.
     */
    public static function day(int $offset): string
    {
        $today = new \DateTimeImmutable('today');
        $monday = $today->modify('-' . ((int)$today->format('N') - 1) . ' days');
        return $monday->modify(($offset >= 0 ? '+' : '') . $offset . ' days')->format('Y-m-d');
    }

    /** The semester the current week belongs to (the latest one started). */
    public static function currentSemester(): array
    {
        $cal = self::calendar();
        $found = $cal['semesters'][0];
        foreach ($cal['semesters'] as $sem) {
            if ($sem['start'] <= $cal['current']) {
                $found = $sem;
            }
        }
        return $found;
    }

    /**
     * Derived load per staff member: how many course allocations they hold,
     * their weekly hours from those, and how many duties they are booked for.
     *
     * This is the number the matrix's equity panel and the scheduler's
     * allocator must agree on — computed from courses() + duties() rather than
     * typed, so they cannot drift apart again.
     */
    public static function staffLoad(): array
    {
        $courses = self::courses();
        $duties = self::duties();
        $load = [];

        foreach (self::staff() as $s) {
            $load[$s['code']] = [
                'code' => $s['code'],
                'name' => $s['name'],
                'active' => $s['active'],
                'paused' => $s['paused'],
                'courses' => 0,
                'hours' => 0,
                'duties' => 0,
            ];
        }

        foreach ($courses as $c) {
            foreach ($c['instructors'] as $code) {
                if (!isset($load[$code])) {
                    continue;
                }
                $load[$code]['courses']++;
                $load[$code]['hours'] += $c['hours'];
            }
        }

        foreach ($duties as $d) {
            foreach ($d['assigned'] as $code) {
                if (isset($load[$code])) {
                    $load[$code]['duties']++;
                }
            }
        }

        return array_values($load);
    }

    // ------------------------------------------------------------------
    // Allocation history
    // ------------------------------------------------------------------

    /**
     * Every allocation before the current week, one record per staff member
     * per event — what the Workload page's History tab reads. The current
     * week is left out on purpose: the page supplies it live from the duty
     * board and the matrix, so edits made there show up immediately.
     *
     * Record shape:
     *   kind     'duty' | 'course'
     *   date     ISO date; week = Monday of its teaching week
     *   staff, course, course_name, lecturer, lecturer_name
     *   title    duty name, or engagement label for course rows
     *   slots    duty rows only
     *   how      auto | manual | cover | replacement | removed   (duties)
     *            allocated | added | removed                      (courses)
     *   note     free text, e.g. 'covering NNE (leave)'
     */
    public static function allocationHistory(): array
    {
        return array_merge(self::courseHistory(), self::pastDuties());
    }

    /**
     * Staff moved on or off a course during the current semester (dated from
     * its start). Everything else about
     * course allocation is derived from these plus courses(), so the history
     * cannot disagree with the matrix.
     */
    private static function courseChanges(): array
    {
        $at = fn(int $days) => (new \DateTimeImmutable(self::currentSemester()['start']))->modify("+$days days")->format('Y-m-d');
        return [
            ['date' => $at(7), 'course' => 'SCS 1308', 'staff' => 'KST', 'how' => 'removed', 'note' => 'temporarily paused'],
            ['date' => $at(7), 'course' => 'SCS 1308', 'staff' => 'WDI', 'how' => 'added',   'note' => 'replacing KST'],
            ['date' => $at(14), 'course' => 'SCS 1309', 'staff' => 'GLS', 'how' => 'removed', 'note' => 'moved to SCS 2311'],
            ['date' => $at(14), 'course' => 'SCS 2311', 'staff' => 'GLS', 'how' => 'added',   'note' => 'moved from SCS 1309'],
            ['date' => $at(21), 'course' => 'IS 1211',  'staff' => 'JRA', 'how' => 'removed', 'note' => 'account deactivated'],
            ['date' => $at(21), 'course' => 'IS 1214',  'staff' => 'AMJ', 'how' => 'added',   'note' => 'extra lab group'],
        ];
    }

    /**
     * The semester-start allocation plus the changes after it. The start is
     * worked backwards from today's matrix: current instructors, minus anyone
     * added later, plus anyone removed later.
     */
    private static function courseHistory(): array
    {
        $changes = self::courseChanges();
        $semStart = self::currentSemester()['start'];

        $out = [];
        foreach (self::courses() as $c) {
            $base = [
                'kind'          => 'course',
                'course'        => $c['code'],
                'course_name'   => $c['name'],
                'lecturer'      => $c['lecturer'],
                'lecturer_name' => $c['lecturer_name'],
                'title'         => self::ENGAGEMENTS[$c['engagement']] ?? $c['engagement'],
                'slots'         => [],
            ];

            $mine = array_filter($changes, fn($ch) => $ch['course'] === $c['code']);
            $added = array_column(array_filter($mine, fn($ch) => $ch['how'] === 'added'), 'staff');
            $removed = array_column(array_filter($mine, fn($ch) => $ch['how'] === 'removed'), 'staff');
            $initial = array_merge(array_diff($c['instructors'], $added), $removed);

            foreach ($initial as $code) {
                $out[] = $base + ['date' => $semStart, 'week' => $semStart, 'staff' => $code, 'how' => 'allocated', 'note' => 'semester start'];
            }
            foreach ($mine as $ch) {
                $out[] = $base + [
                    'date'  => $ch['date'],
                    'week'  => self::weekInfo($ch['date'])['start'] ?? $ch['date'],
                    'staff' => $ch['staff'],
                    'how'   => $ch['how'],
                    'note'  => $ch['note'],
                ];
            }
        }
        return $out;
    }

    /**
     * Generated duties for every teaching week before the current one,
     * allocated the way the Duty board does it: active, unpaused staff with
     * the fewest duties so far. Deterministic (crc32-seeded, like
     * simulatedEvaluation()), so every reload shows the same past. Uses this
     * semester's course list for Semester 1 too — a fixture shortcut.
     */
    private static function pastDuties(): array
    {
        $titles = [
            'practical'            => 'Lab Assessment',
            'tutorial'             => 'Tutorial Quiz',
            'assignment_marking'   => 'Assignment Marking Session',
            'coordination_lecture' => 'Guest Lecture Support',
            'coordination_project' => 'Project Progress Review',
        ];
        $courses = self::courses();
        $codes = array_column(array_filter(self::staff(), fn($s) => $s['active'] && !$s['paused']), 'code');
        $count = array_fill_keys($codes, 0);
        $current = self::calendar()['current'];
        $lastSlot = count(self::SLOTS) - 2;

        $out = [];
        foreach (self::teachingWeeks() as $tw) {
            if ($tw['start'] === $current) {
                continue;
            }
            $perWeek = 3 + crc32('week|' . $tw['start']) % 3;

            for ($i = 0; $i < $perWeek; $i++) {
                $h = crc32($tw['start'] . '|' . $i);
                $c = $courses[$h % count($courses)];
                $date = (new \DateTimeImmutable($tw['start']))->modify('+' . ($h % 5) . ' days')->format('Y-m-d');
                $first = ($h >> 3) % ($lastSlot + 1);
                $slots = [self::SLOTS[$first], self::SLOTS[$first + 1]];
                $headcount = 2 + ($h >> 6) % 3;

                // Fewest duties first; a seeded tiebreak so it isn't alphabetical.
                $pool = $codes;
                usort($pool, fn($a, $b) => ($count[$a] <=> $count[$b]) ?: (crc32($a . $date) <=> crc32($b . $date)));

                foreach (array_slice($pool, 0, $headcount) as $k => $code) {
                    $count[$code]++;
                    $roll = crc32($code . '|' . $date . '|' . $i) % 12;
                    $how = $roll === 0 ? 'cover' : ($roll === 1 ? 'manual' : 'auto');
                    // The person covered is someone not picked for this duty.
                    $note = $how === 'cover' ? 'covering ' . $pool[count($pool) - 1 - $k] . ' (leave)' : '';

                    $out[] = [
                        'kind'          => 'duty',
                        'date'          => $date,
                        'week'          => $tw['start'],
                        'staff'         => $code,
                        'course'        => $c['code'],
                        'course_name'   => $c['name'],
                        'lecturer'      => $c['lecturer'],
                        'lecturer_name' => $c['lecturer_name'],
                        'title'         => $titles[$c['engagement']] ?? 'Duty',
                        'slots'         => $slots,
                        'how'           => $how,
                        'note'          => $note,
                    ];
                }
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------
    // Academic calendar and evaluations
    // ------------------------------------------------------------------

    /**
     * Teaching weeks, as the Evaluations page and the lecturers' Evaluation
     * History navigate them (week / month / semester / academic year). A
     * lecturer evaluates each of their junior staff once per teaching week.
     *
     * `current` is the Monday of the active week — the same week the Duty
     * Scheduler shows (see week()), so "this week" means one thing everywhere.
     *
     * Future shape: an academic_calendar table (semester start + length).
     */
    public static function calendar(): array
    {
        return [
            // Not week()['from']: week() asks weekInfo(), which asks this.
            'current'   => self::day(0),
            // Semester dates are fixture values. If today falls between
            // semesters, "this week" has no teaching week and the pages show
            // their empty states — add the next semester here when it is set.
            'semesters' => [
                ['id' => '2025-26-S1', 'name' => 'Semester 1', 'year' => '2025/26', 'start' => '2025-09-22', 'weeks' => 15],
                ['id' => '2025-26-S2', 'name' => 'Semester 2', 'year' => '2025/26', 'start' => '2026-02-23', 'weeks' => 15],
                ['id' => '2026-27-S1', 'name' => 'Semester 1', 'year' => '2026/27', 'start' => '2026-08-24', 'weeks' => 15],
                ['id' => '2026-27-S2', 'name' => 'Semester 2', 'year' => '2026/27', 'start' => '2027-02-22', 'weeks' => 15],
            ],
        ];
    }

    /**
     * The teaching week an ISO date falls in, or null outside term:
     * ['start' => Monday ISO, 'number' => 1..15, 'semester' => 'Semester 2',
     *  'year' => '2025/26', 'label' => 'Week 5 · Semester 2'].
     */
    public static function weekInfo(string $iso): ?array
    {
        $day = new \DateTimeImmutable($iso);
        $monday = $day->modify('-' . ((int)$day->format('N') - 1) . ' days');

        foreach (self::calendar()['semesters'] as $sem) {
            $start = new \DateTimeImmutable($sem['start']);
            $n = intdiv((int)$start->diff($monday)->format('%r%a'), 7) + 1;
            if ($monday >= $start && $n <= $sem['weeks']) {
                return [
                    'start'    => $monday->format('Y-m-d'),
                    'number'   => $n,
                    'semester' => $sem['name'],
                    'year'     => $sem['year'],
                    'label'    => 'Week ' . $n . ' · ' . $sem['name'],
                ];
            }
        }
        return null;
    }

    /**
     * Who must be evaluated, and by whom: every junior staff member on a
     * course, by that course's lecturer in charge, once per teaching week.
     * Inactive and paused staff are not working, so nobody is due to
     * evaluate them.
     */
    public static function evaluationAssignments(): array
    {
        $staff = [];
        foreach (self::staff() as $s) {
            $staff[$s['code']] = $s;
        }

        $out = [];
        foreach (self::courses() as $c) {
            foreach ($c['instructors'] as $code) {
                $s = $staff[$code] ?? null;
                if (!$s || !$s['active'] || $s['paused']) {
                    continue;
                }
                $out[] = [
                    'staff_code'    => $code,
                    'staff_name'    => $s['name'],
                    'course_code'   => $c['code'],
                    'course_name'   => $c['name'],
                    'lecturer'      => $c['lecturer'],
                    'lecturer_name' => $c['lecturer_name'],
                ];
            }
        }
        return $out;
    }

    /**
     * Submitted evaluations, one per (staff, course, week). An evaluation is
     * exactly what the lecturer's form collects: a 1–5 star `rating` and an
     * optional `comment`. Names live on evaluationAssignments(), so each
     * record carries only its keys, its week and date, and those two fields —
     * this list covers a whole academic year.
     *
     * Most come from simulatedEvaluation() — the same rule the lecturers'
     * Evaluation History uses, so both pages agree on which weeks were missed.
     * The hand-written ones below carry comments and replace the generated
     * record for their week.
     */
    public static function evaluations(): array
    {
        $records = [];

        foreach (self::teachingWeeks() as $tw) {
            $week = $tw['start'];
            foreach (self::evaluationAssignments() as $a) {
                $sim = self::simulatedEvaluation($a['course_code'], $a['staff_code'], $week);
                if ($sim === null) {
                    continue;   // not evaluated this week
                }
                $records[$a['staff_code'] . '|' . $a['course_code'] . '|' . $week] = [
                    'staff_code'  => $a['staff_code'],
                    'course_code' => $a['course_code'],
                    'week'        => $week,
                    'date'        => $sim['date'],
                    'rating'      => $sim['rating'],
                ];
            }
        }

        foreach (self::writtenEvaluations() as $e) {
            $info = self::weekInfo($e['date']);
            if ($info === null) {
                continue;
            }
            unset($e['id']);
            $e['week'] = $info['start'];
            $records[$e['staff_code'] . '|' . $e['course_code'] . '|' . $e['week']] = $e;
        }

        // Every past teaching week × every assignment is a couple of thousand records; the real endpoint should return one period
        // at a time instead.
        return array_values($records);
    }

    /**
     * Every teaching week from the first semester up to and including the
     * current one, oldest first: [['start', 'number', 'semester', 'year',
     * 'label'], ...]. Future weeks are left out — nothing can be due yet.
     */
    public static function teachingWeeks(): array
    {
        $cal = self::calendar();
        $out = [];
        foreach ($cal['semesters'] as $sem) {
            $monday = new \DateTimeImmutable($sem['start']);
            for ($n = 1; $n <= $sem['weeks']; $n++, $monday = $monday->modify('+7 days')) {
                $start = $monday->format('Y-m-d');
                if ($start > $cal['current']) {
                    break 2;
                }
                $out[] = [
                    'start'    => $start,
                    'number'   => $n,
                    'semester' => $sem['name'],
                    'year'     => $sem['year'],
                    'label'    => 'Week ' . $n . ' · ' . $sem['name'],
                ];
            }
        }
        return $out;
    }

    /**
     * The prototype's stand-in for "did this lecturer evaluate this person on
     * this course that week?": deterministic, so the same week always gives
     * the same answer on every page. About one in ten past weeks comes back
     * null (a week the lecturer forgot); the current week, still in progress,
     * is about half done. Otherwise ['date' => ISO, 'rating' => 3..5]. No
     * comment — most weekly evaluations in practice are a rating alone.
     */
    public static function simulatedEvaluation(string $courseCode, string $staffCode, string $weekStart): ?array
    {
        $rate = $weekStart === self::calendar()['current'] ? 50 : 90;
        $h = crc32($courseCode . '|' . $staffCode . '|' . $weekStart);
        if ($h % 100 >= $rate) {
            return null;
        }

        $date = (new \DateTimeImmutable($weekStart))->modify('+' . ($h % 5) . ' days')->format('Y-m-d');
        return ['date' => $date, 'rating' => 3 + (($h >> 7) % 3)];
    }

    /** Evaluations that came with a comment — shown in full in the detail panel. */
    private static function writtenEvaluations(): array
    {
        return [
            ['id' => 'eval-001', 'staff_code' => 'TSR', 'course_code' => 'SCS 1308', 'date' => self::day(-8), 'rating' => 5, 'comment' => 'Exceptional algorithm demonstration; students consistently praise his step-by-step trace explanations. Could encourage quieter students to join in more during group tutorials.'],
            ['id' => 'eval-002', 'staff_code' => 'BMC', 'course_code' => 'SCS 2310', 'date' => self::day(-11), 'rating' => 4, 'comment' => 'Strong command of MATLAB and the Fourier transform practicals, and proactive in lab setup. Should finish grading a day or two earlier when batches are large.'],
            ['id' => 'eval-003', 'staff_code' => 'DUH', 'course_code' => 'IS 1214',  'date' => self::day(-13), 'rating' => 5, 'comment' => 'Very approachable and patient with first-year students struggling with C pointers. Needs to follow the automated grading rubric more strictly.'],
            ['id' => 'eval-004', 'staff_code' => 'AMJ', 'course_code' => 'IS 1208',  'date' => self::day(-18), 'rating' => 4, 'comment' => 'Well-versed in UML modelling and agile case studies. Arrived late to two practical sessions because of a timetable clash, which should be resolved before next semester.'],
            ['id' => 'eval-005', 'staff_code' => 'WIJ', 'course_code' => 'IS 2209',  'date' => self::day(-21), 'rating' => 5, 'comment' => 'Took over two tutorial groups at short notice without any drop in quality. Carrying one of the heaviest loads in the department — watch for burnout.'],
            ['id' => 'eval-006', 'staff_code' => 'NJN', 'course_code' => 'SCS 2311', 'date' => self::day(-24), 'rating' => 4, 'comment' => 'Deep practical knowledge of the OpenSSL toolchain. Some students found the explanations terse.'],
            ['id' => 'eval-007', 'staff_code' => 'PRL', 'course_code' => 'IS 2211',  'date' => self::day(-27), 'rating' => 5, 'comment' => 'Ran the Figma critique sessions entirely unaided. Ready for a heavier allocation next semester.'],
            ['id' => 'eval-008', 'staff_code' => 'TSH', 'course_code' => 'SCS 1309', 'date' => self::day(-25), 'rating' => 3, 'comment' => 'Lab setup is always ready ahead of the session, but missed two marking deadlines. Needs a clearer handover when on leave.'],
        ];
    }
}
