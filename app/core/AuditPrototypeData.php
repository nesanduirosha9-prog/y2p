<?php

namespace app\core;

// AuditPrototypeData — the fixture source behind the two Activity Log screens:
// the department-wide log (/audit, Coordinator + In-Charge) and every member's
// own log (/audit/me).
//
// WHY THIS FILE EXISTS
// An audit trail is the one screen whose credibility depends entirely on where
// the rows come from: it is worth nothing if a person can write, edit or delete
// a row. So this file settles the SHAPE of a record now — who acted, what they
// did, what they did it to, when, from where, and whether it succeeded — and
// the UI is built against that shape. When the backend lands, the log is
// written by the application itself (one INSERT at the end of each action that
// changes something) and never by a user, and nothing here changes except the
// method that returns the rows.
//
// HOW IT DIES
//   ACTIONS / CATEGORIES  -> stay exactly as they are. They are the presentation
//                            layer: a stored row holds the action KEY
//                            ('leave.approved'), never the English sentence, so
//                            wording can be fixed later without touching data.
//   actors()              -> StaffModel::all()            (already exists)
//   entries()             -> AuditLogModel::page()        (needs one new table,
//                            append-only: no UPDATE and no DELETE statement
//                            anywhere in the codebase, and the DB user for the
//                            app granted INSERT + SELECT on it only)
//   semesters()           -> a `semesters` table or config, whichever the
//                            department prefers; the filter code is identical.
//
// The suggested table, for when that week comes:
//
//   CREATE TABLE audit_log (
//       id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//       occurred_at DATETIME(0)  NOT NULL,
//       actor_code  VARCHAR(12)  NOT NULL,
//       action      VARCHAR(48)  NOT NULL,   -- 'leave.approved'
//       target      VARCHAR(190) NULL,       -- what it was done to
//       detail      VARCHAR(255) NULL,       -- one plain sentence
//       result      ENUM('success','failed','refused') NOT NULL DEFAULT 'success',
//       ip          VARCHAR(45)  NULL,
//       device      VARCHAR(120) NULL,
//       seal        CHAR(40)     NOT NULL,   -- SHA-1 of the row as written
//       INDEX (occurred_at), INDEX (actor_code), INDEX (action)
//   );
//
// Nothing here writes anywhere, and the screens it feeds have no control that
// edits or removes a row — deliberately, because the real ones will not either.
class AuditPrototypeData
{
    /**
     * The action groups the filter chips are built from. Deliberately few and
     * named the way a lecturer would say them out loud, not after the tables
     * they touch.
     */
    public const CATEGORIES = [
        'leave'      => ['label' => 'Leave',         'icon' => 'fa-regular fa-calendar-minus'],
        'timetable'  => ['label' => 'Timetable',     'icon' => 'fa-solid fa-calendar-days'],
        'workload'   => ['label' => 'Workload',      'icon' => 'fa-solid fa-table-cells'],
        'duty'       => ['label' => 'Duties',        'icon' => 'fa-solid fa-calendar-check'],
        'evaluation' => ['label' => 'Evaluations',   'icon' => 'fa-solid fa-clipboard-check'],
        'staff'      => ['label' => 'Staff records', 'icon' => 'fa-solid fa-user-check'],
        'auth'       => ['label' => 'Sign-in',       'icon' => 'fa-solid fa-key'],
        'message'    => ['label' => 'Messages',      'icon' => 'fa-regular fa-message'],
        'system'     => ['label' => 'System',        'icon' => 'fa-solid fa-shield-halved'],
    ];

    /**
     * Every action the system records, keyed the way the stored row would key
     * it. Three fields per action beyond the label:
     *
     *   category — which filter chip it answers to
     *   by       — which kinds of member can perform it, so the generated log
     *              is credible: only the Timetable Officer moves a session,
     *              only the Coordinator or the In-Charge decides a leave
     *              request. Groups are 'officer', 'junior', 'senior',
     *              'coordinator', 'in_charge'.
     *   weight   — relative frequency. Sign-ins happen constantly; a role
     *              handover happens twice a year.
     */
    public const ACTIONS = [
        // --- Leave -----------------------------------------------------------
        'leave.requested'   => ['label' => 'Requested leave',                'category' => 'leave',      'by' => ['junior', 'senior', 'officer', 'coordinator'], 'weight' => 7],
        'leave.approved'    => ['label' => 'Approved a leave request',       'category' => 'leave',      'by' => ['coordinator', 'in_charge'],                  'weight' => 5],
        'leave.rejected'    => ['label' => 'Declined a leave request',       'category' => 'leave',      'by' => ['coordinator', 'in_charge'],                  'weight' => 2],
        'leave.cancelled'   => ['label' => 'Cancelled their leave request',  'category' => 'leave',      'by' => ['junior', 'senior'],                          'weight' => 2],

        // --- Timetable -------------------------------------------------------
        'timetable.session_added'   => ['label' => 'Added a timetable session',     'category' => 'timetable', 'by' => ['officer'],            'weight' => 6],
        'timetable.session_moved'   => ['label' => 'Moved a timetable session',     'category' => 'timetable', 'by' => ['officer'],            'weight' => 6],
        'timetable.session_removed' => ['label' => 'Removed a timetable session',   'category' => 'timetable', 'by' => ['officer'],            'weight' => 3],
        'timetable.room_changed'    => ['label' => 'Changed a session room',        'category' => 'timetable', 'by' => ['officer'],            'weight' => 4],
        'timetable.published'       => ['label' => 'Published the weekly timetable', 'category' => 'timetable', 'by' => ['officer'],           'weight' => 1],
        'timetable.reschedule_requested' => ['label' => 'Asked for a session to be rescheduled', 'category' => 'timetable', 'by' => ['senior', 'junior'], 'weight' => 4],
        'timetable.reschedule_decided'   => ['label' => 'Decided a reschedule request',          'category' => 'timetable', 'by' => ['officer'],          'weight' => 3],

        // --- Workload matrix -------------------------------------------------
        'workload.assigned'   => ['label' => 'Assigned a staff member to a course', 'category' => 'workload', 'by' => ['coordinator'],                 'weight' => 7],
        'workload.removed'    => ['label' => 'Removed a staff member from a course', 'category' => 'workload', 'by' => ['coordinator'],                'weight' => 3],
        'workload.rebalanced' => ['label' => 'Applied a workload rebalance',        'category' => 'workload', 'by' => ['coordinator'],                 'weight' => 2],
        'workload.reviewed'   => ['label' => 'Reviewed the workload matrix',        'category' => 'workload', 'by' => ['in_charge'],                   'weight' => 2],

        // --- Duty scheduler --------------------------------------------------
        'duty.requested'    => ['label' => 'Requested duty cover',            'category' => 'duty', 'by' => ['senior', 'junior'],  'weight' => 5],
        'duty.allocated'    => ['label' => 'Allocated staff to a duty',       'category' => 'duty', 'by' => ['coordinator'],       'weight' => 6],
        'duty.swapped'      => ['label' => 'Swapped a duty assignment',       'category' => 'duty', 'by' => ['coordinator'],       'weight' => 3],
        'duty.invites_sent' => ['label' => 'Sent duty invitations',           'category' => 'duty', 'by' => ['coordinator'],       'weight' => 3],
        'duty.cancelled'    => ['label' => 'Cancelled a duty',               'category' => 'duty', 'by' => ['coordinator'],        'weight' => 1],

        // --- Evaluations -----------------------------------------------------
        'evaluation.submitted' => ['label' => 'Submitted a staff evaluation', 'category' => 'evaluation', 'by' => ['senior'],                    'weight' => 5],
        'evaluation.reviewed'  => ['label' => 'Marked an evaluation reviewed', 'category' => 'evaluation', 'by' => ['coordinator', 'in_charge'], 'weight' => 4],
        'evaluation.flagged'   => ['label' => 'Flagged an evaluation',        'category' => 'evaluation', 'by' => ['coordinator', 'in_charge'],  'weight' => 2],

        // --- Staff records ---------------------------------------------------
        'staff.registration_approved' => ['label' => 'Approved a registration',        'category' => 'staff', 'by' => ['coordinator', 'in_charge'], 'weight' => 3],
        'staff.registration_rejected' => ['label' => 'Rejected a registration',        'category' => 'staff', 'by' => ['coordinator', 'in_charge'], 'weight' => 1],
        'staff.profile_updated'       => ['label' => 'Updated their own profile',      'category' => 'staff', 'by' => ['junior', 'senior', 'officer', 'coordinator', 'in_charge'], 'weight' => 5],
        'staff.availability_updated'  => ['label' => 'Updated their weekly availability', 'category' => 'staff', 'by' => ['junior'],               'weight' => 6],
        'staff.paused'                => ['label' => 'Paused a staff member for allocation', 'category' => 'staff', 'by' => ['coordinator'],       'weight' => 1],
        'staff.role_handover'         => ['label' => 'Handed a key role to another member', 'category' => 'staff', 'by' => ['in_charge'],          'weight' => 1],

        // --- Sign-in ---------------------------------------------------------
        'auth.login'          => ['label' => 'Signed in',                  'category' => 'auth', 'by' => ['junior', 'senior', 'officer', 'coordinator', 'in_charge'], 'weight' => 26],
        'auth.logout'         => ['label' => 'Signed out',                 'category' => 'auth', 'by' => ['junior', 'senior', 'officer', 'coordinator', 'in_charge'], 'weight' => 14],
        'auth.login_failed'   => ['label' => 'Tried to sign in and failed',     'category' => 'auth', 'by' => ['junior', 'senior', 'officer', 'coordinator', 'in_charge'], 'weight' => 3],
        'auth.password_reset' => ['label' => 'Reset their password',        'category' => 'auth', 'by' => ['junior', 'senior', 'officer'],                            'weight' => 1],

        // --- Messages --------------------------------------------------------
        // The trail records that a message was sent and to which conversation.
        // It does NOT record the text: an audit log is a record of actions, and
        // storing message bodies here would turn it into surveillance.
        'message.sent'         => ['label' => 'Sent a message',            'category' => 'message', 'by' => ['junior', 'senior', 'officer', 'coordinator', 'in_charge'], 'weight' => 6],
        'message.group_created' => ['label' => 'Created a group conversation', 'category' => 'message', 'by' => ['coordinator', 'in_charge', 'officer'],                 'weight' => 1],

        // --- System ----------------------------------------------------------
        'audit.exported'   => ['label' => 'Exported the activity log',      'category' => 'system', 'by' => ['coordinator', 'in_charge'],                              'weight' => 2],
        'audit.viewed'     => ['label' => 'Opened the activity log',        'category' => 'system', 'by' => ['coordinator', 'in_charge'],                              'weight' => 3],
        'access.denied'    => ['label' => 'Was refused access to a page',   'category' => 'system', 'by' => ['junior', 'senior', 'officer', 'coordinator'],            'weight' => 2],
        'settings.changed' => ['label' => 'Changed a system setting',       'category' => 'system', 'by' => ['in_charge'],                                             'weight' => 1],
    ];

    /**
     * Academic terms, newest first, for the "this semester" filter and the
     * per-semester presets. `current` is whichever one contains today.
     */
    public static function semesters(): array
    {
        $today = date('Y-m-d');
        $terms = [
            ['key' => '2026-s2', 'label' => 'Semester 2, 2026', 'start' => '2026-06-15', 'end' => '2026-10-31'],
            ['key' => '2026-s1', 'label' => 'Semester 1, 2026', 'start' => '2026-01-12', 'end' => '2026-05-29'],
            ['key' => '2025-s2', 'label' => 'Semester 2, 2025', 'start' => '2025-06-16', 'end' => '2025-10-31'],
        ];
        foreach ($terms as &$t) {
            $t['current'] = $today >= $t['start'] && $today <= $t['end'];
        }
        return $terms;
    }

    /**
     * Who appears in the log. Codes, names and ranks are the seeded roster
     * (database/seeds/001_staff.sql), so a row's actor matches a real account.
     *
     * One deliberate addition: the seed has exactly ONE coordinator (MKA), and
     * the Coordinator's visibility rule — "everything except other
     * coordinators and the In-Charge" — is invisible with nobody to hide. DLW
     * is therefore listed here as a second programme coordinator. In the real
     * system this whole list is `staff` and the positions come from
     * staff.position; nothing else depends on it.
     */
    public static function actors(): array
    {
        // Cached: entry generation asks for the roster once per row, and
        // staffRoleLabel() would otherwise run a few thousand times per render.
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $roster = [
            ['code' => 'TMO', 'name' => 'T. M. Officer',        'role' => 'timetable_officer', 'rank' => null,     'position' => null],
            ['code' => 'DSC', 'name' => 'Dr. Sarah Chen',       'role' => 'academic_staff',    'rank' => 'senior', 'position' => 'in_charge'],
            ['code' => 'MKA', 'name' => 'Mr. Kwame Addo',       'role' => 'academic_staff',    'rank' => 'junior', 'position' => 'coordinator'],
            ['code' => 'DLW', 'name' => 'Dr. Liu Wei',          'role' => 'academic_staff',    'rank' => 'senior', 'position' => 'coordinator'],
            ['code' => 'PJO', 'name' => 'Prof. James Osei',     'role' => 'academic_staff',    'rank' => 'senior', 'position' => null],
            ['code' => 'DAD', 'name' => 'Dr. Amara Diallo',     'role' => 'academic_staff',    'rank' => 'senior', 'position' => null],
            ['code' => 'PRM', 'name' => 'Prof. Richard Mensah', 'role' => 'academic_staff',    'rank' => 'senior', 'position' => null],
            ['code' => 'DEP', 'name' => 'Dr. Elena Petrov',     'role' => 'academic_staff',    'rank' => 'senior', 'position' => null],
            ['code' => 'PKA', 'name' => 'Prof. Kweku Asante',   'role' => 'academic_staff',    'rank' => 'senior', 'position' => null],
            ['code' => 'DFA', 'name' => 'Dr. Fatima Ahmed',     'role' => 'academic_staff',    'rank' => 'senior', 'position' => null],
            ['code' => 'PDN', 'name' => 'Prof. David Nkrumah',  'role' => 'academic_staff',    'rank' => 'senior', 'position' => null],
            ['code' => 'DKA', 'name' => 'Dr. Kofi Anning',      'role' => 'academic_staff',    'rank' => 'senior', 'position' => null],
            ['code' => 'DLO', 'name' => 'Dr. Linda Osei',       'role' => 'academic_staff',    'rank' => 'senior', 'position' => null],
            ['code' => 'TMF', 'name' => 'Ms. Thilini Fernando', 'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
            ['code' => 'MEM', 'name' => 'Ms. Efua Mensah',      'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
            ['code' => 'MAB', 'name' => 'Mr. Ato Baidoo',       'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
            ['code' => 'MYD', 'name' => 'Ms. Yaa Darko',        'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
            ['code' => 'MKO', 'name' => 'Mr. Kojo Amoah',       'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
            ['code' => 'MNA', 'name' => 'Ms. Nana Ama',         'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
            ['code' => 'MAT', 'name' => 'Mr. Atta Tetteh',      'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
            ['code' => 'MEQ', 'name' => 'Ms. Esi Quaye',        'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
            ['code' => 'MAD', 'name' => 'Mr. Adom Boateng',     'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
            ['code' => 'MYB', 'name' => 'Ms. Yaw Bediako',      'role' => 'academic_staff',    'rank' => 'junior', 'position' => null],
        ];

        $out = [];
        foreach ($roster as $a) {
            $out[$a['code']] = [
                'name'     => $a['name'],
                'label'    => ViewHelpers::staffRoleLabel([
                    'role' => $a['role'], 'academic_rank' => $a['rank'], 'position' => $a['position'],
                ]),
                'role'     => $a['role'],
                'position' => $a['position'],
                'group'    => self::groupOf($a['role'], $a['rank'], $a['position']),
            ];
        }
        $cached = $out;
        return $cached;
    }

    /**
     * Which `by` bucket a member falls into. Position wins over rank, because
     * a Coordinator who is also a junior instructor (MKA, in the seed) acts as
     * the Coordinator when they allocate work.
     */
    public static function groupOf(string $role, ?string $rank, ?string $position): string
    {
        if ($position === 'coordinator' || $position === 'in_charge') {
            return $position;
        }
        if ($role === 'timetable_officer') {
            return 'officer';
        }
        return $rank === 'senior' ? 'senior' : 'junior';
    }

    // =======================================================================
    // The payload
    // =======================================================================

    /**
     * Everything one of the two screens needs, already filtered to what this
     * viewer is allowed to see.
     *
     * FILTERING HAPPENS HERE, ON THE SERVER, and that is the point: the rows a
     * Coordinator may not read are never sent to their browser, so they are not
     * sitting in the page source behind a JS filter. The client-side filters in
     * js/audit.js are a convenience on top of an already-authorised set.
     *
     * @param array  $viewer ['code','name','role','rank','position']
     * @param string $scope  'system' (department-wide) or 'own'
     */
    public static function feed(array $viewer, string $scope): array
    {
        $viewerGroup = self::groupOf($viewer['role'] ?? '', $viewer['rank'] ?? null, $viewer['position'] ?? null);
        $actors      = self::actors();
        $entries     = self::entries();

        // A brand-new account has no history, which makes the prototype's own-log
        // page look broken rather than empty-by-design. So an unknown code is
        // shown the strand of a roster member of the same kind, relabelled. Only
        // the own-log page can hit this: the system log is limited to the
        // Coordinator and the In-Charge, both of whom are in the roster.
        $ownCode = $viewer['code'] ?? '';
        $standIn = null;
        if ($scope === 'own' && !isset($actors[$ownCode])) {
            foreach ($actors as $code => $a) {
                if ($a['group'] === $viewerGroup) {
                    $standIn = $code;
                    break;
                }
            }
        }

        $visible = [];
        foreach ($entries as $entry) {
            if ($standIn !== null && $entry['actor'] === $standIn) {
                $entry['actor'] = $ownCode;
            }
            if (!self::mayRead($entry, $ownCode, $viewerGroup, $scope)) {
                continue;
            }
            $visible[] = $entry;
        }

        // The roster the "who" filter offers: only people this viewer can
        // actually read, so the dropdown never lists a name whose rows would
        // all come back empty.
        $offered = [];
        foreach ($visible as $entry) {
            $offered[$entry['actor']] = true;
        }
        $filterActors = [];
        foreach ($actors as $code => $a) {
            if (isset($offered[$code])) {
                $filterActors[$code] = $a;
            }
        }
        if ($standIn !== null) {
            $filterActors[$ownCode] = [
                'name'     => $viewer['name'] ?? $ownCode,
                'label'    => ViewHelpers::staffRoleLabel([
                    'role' => $viewer['role'] ?? '', 'academic_rank' => $viewer['rank'] ?? null, 'position' => $viewer['position'] ?? null,
                ]),
                'role'     => $viewer['role'] ?? '',
                'position' => $viewer['position'] ?? null,
                'group'    => $viewerGroup,
            ];
        }

        // ACTIONS minus `by`/`weight`: the browser needs the sentence and the
        // category, and nothing about who is allowed to do what.
        $actions = [];
        foreach (self::ACTIONS as $key => $a) {
            $actions[$key] = ['label' => $a['label'], 'category' => $a['category']];
        }

        return [
            'scope'      => $scope,
            'viewer'     => [
                'code'  => $ownCode,
                'name'  => $viewer['name'] ?? $ownCode,
                'group' => $viewerGroup,
            ],
            // What this viewer is NOT shown, stated plainly on the page rather
            // than left for them to wonder about.
            'restriction' => self::restrictionNote($viewerGroup, $scope),
            'actors'      => $filterActors,
            'categories'  => self::CATEGORIES,
            'actions'     => $actions,
            'semesters'   => self::semesters(),
            'entries'     => $visible,
            'generatedAt' => date('c'),
            // The clock the ENTRIES are stamped with, handed over explicitly so
            // the browser groups and filters them in the same frame it reads
            // them. It must not use its own clock for this: PHP here runs on UTC
            // while the machine is on +0530, so "today" in the browser and
            // "today" in the record disagree for five and a half hours of every
            // day — long enough that a reader checking what just happened would
            // find an empty "Today". A reader whose own laptop clock is wrong
            // would see the same nonsense, which for an audit trail is worse
            // than inconvenient.
            'today'       => date('Y-m-d'),
            'now'         => date('Y-m-d\TH:i:s'),
        ];
    }

    /**
     * The visibility rule, in one place.
     *
     *   In-Charge   — the whole department log.
     *   Coordinator — the whole log EXCEPT rows written by the In-Charge and by
     *                 any other coordinator. Their own rows stay visible.
     *   Anyone else — their own rows only, and no system-wide screen at all.
     */
    private static function mayRead(array $entry, string $viewerCode, string $viewerGroup, string $scope): bool
    {
        if ($scope === 'own') {
            return $entry['actor'] === $viewerCode;
        }
        if ($viewerGroup === 'in_charge') {
            return true;
        }
        if ($viewerGroup === 'coordinator') {
            if ($entry['actor'] === $viewerCode) {
                return true;
            }
            $actorGroup = self::actors()[$entry['actor']]['group'] ?? 'junior';
            return $actorGroup !== 'in_charge' && $actorGroup !== 'coordinator';
        }
        // No other group has a system-wide screen; the controller guards this
        // too, so reaching here means a bug, and the safe answer is "no".
        return false;
    }

    private static function restrictionNote(string $viewerGroup, string $scope): string
    {
        if ($scope === 'own') {
            return 'You are seeing your own record only. It is the same record your Coordinator and the Department In-Charge can see.';
        }
        if ($viewerGroup === 'coordinator') {
            return 'You are seeing the whole department except entries written by the Department In-Charge and by other coordinators.';
        }
        return 'You are seeing every entry recorded by every account.';
    }

    // =======================================================================
    // Generation
    //
    // Everything below exists only until there is a table to read, and it is
    // written to be boringly deterministic: the same seed produces the same log
    // on every page load, so a row you clicked ten minutes ago is still there,
    // and so screenshots do not change between refreshes. Real rows arrive in
    // the order they happened and need none of this.
    // =======================================================================

    /** @var array<int,array>|null */
    private static ?array $cache = null;
    private static int $seed = 20260923;

    /** Returns 0..$max-1 from a plain LCG — repeatable, and good enough. */
    private static function rnd(int $max): int
    {
        self::$seed = (self::$seed * 1103515245 + 12345) & 0x7FFFFFFF;
        return $max > 0 ? intdiv(self::$seed, 65536) % $max : 0;
    }

    /** @param array<int,mixed> $list */
    private static function pick(array $list)
    {
        return $list[self::rnd(count($list))];
    }

    /**
     * The whole log, newest first, before any visibility filtering.
     *
     * Density decays with age, which is what a real log looks like and what
     * makes the period filters worth having: today and this week are busy, six
     * months ago is a handful of rows a day.
     */
    private static function entries(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        self::$seed = 20260923;

        $actors  = self::actors();
        $byGroup = [];
        foreach ($actors as $code => $a) {
            $byGroup[$a['group']][] = $code;
        }

        // One flat list of action keys, each repeated by its weight, so picking
        // an action is a single rnd() and the mix matches the weights.
        $bag = [];
        foreach (self::ACTIONS as $key => $a) {
            for ($i = 0; $i < $a['weight']; $i++) {
                $bag[] = $key;
            }
        }

        $today = new \DateTimeImmutable(date('Y-m-d'));
        $start = new \DateTimeImmutable('2025-08-01');
        $now   = new \DateTimeImmutable();   // nothing may be recorded after this
        $rows  = [];

        for ($day = $start; $day <= $today; $day = $day->modify('+1 day')) {
            $age     = (int)$today->diff($day)->days;
            $weekend = in_array((int)$day->format('N'), [6, 7], true);

            if ($age <= 2)        { $count = 14 + self::rnd(10); }
            elseif ($age <= 9)    { $count = 9 + self::rnd(8); }
            elseif ($age <= 35)   { $count = 5 + self::rnd(7); }
            elseif ($age <= 130)  { $count = 2 + self::rnd(6); }
            else                  { $count = self::rnd(4); }

            if ($weekend) {
                $count = intdiv($count, 4);
            }

            for ($i = 0; $i < $count; $i++) {
                $action = self::pick($bag);
                $spec   = self::ACTIONS[$action];

                // Only somebody allowed to perform it gets to perform it.
                $pool = [];
                foreach ($spec['by'] as $group) {
                    foreach ($byGroup[$group] ?? [] as $code) {
                        $pool[] = $code;
                    }
                }
                if ($pool === []) {
                    continue;
                }
                $actor = self::pick($pool);

                $hour = self::rnd(24) === 0 ? 19 + self::rnd(3) : 8 + self::rnd(10);
                $ts   = $day->setTime($hour, self::rnd(60), self::rnd(60));

                // A log cannot contain something that has not happened yet. On
                // the current day the loop happily invents an afternoon entry at
                // nine in the morning, and a row stamped in the future is the
                // fastest way to make a reader distrust the whole record.
                if ($ts > $now) {
                    continue;
                }

                [$target, $detail] = self::describe($action, $actor, $ts);

                $rows[] = [
                    'ts'     => $ts->format('Y-m-d\TH:i:s'),
                    'actor'  => $actor,
                    'action' => $action,
                    'target' => $target,
                    'detail' => $detail,
                    'result' => self::resultFor($action),
                    'ip'     => self::pick(['192.248.16.', '192.248.17.', '10.22.4.', '10.22.8.']) . (2 + self::rnd(250)),
                    'device' => self::pick([
                        'Chrome on Windows', 'Chrome on Windows', 'Edge on Windows',
                        'Safari on macOS', 'Firefox on Ubuntu', 'Chrome on Android', 'Safari on iPhone',
                    ]),
                ];
            }
        }

        // Ascending order first, so the sequence number matches the order things
        // happened — an append-only log's ids are part of how you can tell
        // nothing was taken out of the middle. The seal is over the row as
        // written, id included.
        usort($rows, fn($a, $b) => strcmp($a['ts'], $b['ts']));
        foreach ($rows as $i => &$row) {
            $row['id']   = 'LOG-' . str_pad((string)($i + 1), 6, '0', STR_PAD_LEFT);
            $row['seal'] = strtoupper(substr(sha1($row['id'] . '|' . $row['ts'] . '|' . $row['actor'] . '|' . $row['action'] . '|' . $row['target']), 0, 16));
        }
        unset($row);

        self::$cache = array_reverse($rows);
        return self::$cache;
    }

    /** Failed sign-ins and refusals are recorded as such — those are the rows an audit exists for. */
    private static function resultFor(string $action): string
    {
        if ($action === 'auth.login_failed') {
            return 'failed';
        }
        if ($action === 'access.denied') {
            return 'refused';
        }
        return 'success';
    }

    /**
     * The "what it was done to" and the one-line plain-English detail.
     *
     * A real row carries these as stored strings, written by whichever
     * controller performed the action, so the log stays readable years later
     * even after the course or the person is gone.
     *
     * @return array{0:string,1:string}
     */
    private static function describe(string $action, string $actor, \DateTimeImmutable $ts): array
    {
        $actors = self::actors();
        $names  = array_keys($actors);

        $other     = self::pick($names);
        $otherName = $actors[$other]['name'];
        $person    = $otherName . ' (' . $other . ')';

        $course     = self::pick(self::COURSES);
        $courseCode = $course[0];
        $courseName = $course[1];
        $room       = self::pick(['Lab 1', 'Lab 2', 'Lab 3', 'Hall A', 'Hall B', 'Seminar Room 2', 'Computer Lab 4']);
        $slot       = self::pick(['8–9 AM', '9–10 AM', '10–11 AM', '11–12 noon', '1–2 PM', '2–3 PM', '3–4 PM']);
        $slot2      = self::pick(['8–9 AM', '10–11 AM', '1–2 PM', '2–3 PM', '3–4 PM']);
        $weekday    = self::pick(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
        $weekday2   = self::pick(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
        $engagement = self::pick(array_values(WorkloadPrototypeData::ENGAGEMENTS));
        $leaveKind  = self::pick(['casual', 'medical', 'duty', 'annual']);
        $days       = 1 + self::rnd(4);
        $from       = $ts->modify('+' . (2 + self::rnd(20)) . ' days');
        $range      = $from->format('j M') . ($days > 1 ? '–' . $from->modify('+' . ($days - 1) . ' days')->format('j M') : '');
        $ref        = fn(string $prefix, int $span) => $prefix . '-' . str_pad((string)(1 + self::rnd($span)), 4, '0', STR_PAD_LEFT);
        $score      = (30 + self::rnd(20)) / 10;

        return match ($action) {
            'leave.requested'  => ['Leave request ' . $ref('LR', 900), $days . ' days of ' . $leaveKind . ' leave, ' . $range],
            'leave.approved'   => ['Leave request ' . $ref('LR', 900) . ' · ' . $person, 'Approved ' . $days . ' days of ' . $leaveKind . ' leave, ' . $range],
            'leave.rejected'   => ['Leave request ' . $ref('LR', 900) . ' · ' . $person, 'Declined — clashes with ' . $courseCode . ' ' . $engagement],
            'leave.cancelled'  => ['Leave request ' . $ref('LR', 900), 'Withdrawn before a decision was made'],

            'timetable.session_added'   => [$courseCode . ' ' . $courseName, 'Added ' . $weekday . ' ' . $slot . ' in ' . $room],
            'timetable.session_moved'   => [$courseCode . ' ' . $courseName, 'Moved from ' . $weekday . ' ' . $slot . ' to ' . $weekday2 . ' ' . $slot2],
            'timetable.session_removed' => [$courseCode . ' ' . $courseName, 'Removed the ' . $weekday . ' ' . $slot . ' session'],
            'timetable.room_changed'    => [$courseCode . ' ' . $courseName, $weekday . ' ' . $slot . ' moved to ' . $room],
            'timetable.published'       => ['Weekly timetable', 'Published — visible to all staff from now on'],
            'timetable.reschedule_requested' => [$courseCode . ' ' . $courseName, 'Asked to move ' . $weekday . ' ' . $slot . ' — reason: clash with ' . $courseCode],
            'timetable.reschedule_decided'   => ['Reschedule request ' . $ref('RR', 400) . ' · ' . $person, self::pick(['Approved', 'Declined']) . ' — ' . $courseCode . ' ' . $weekday . ' ' . $slot],

            'workload.assigned'   => [$courseCode . ' ' . $engagement, 'Added ' . $person . ' to the team'],
            'workload.removed'    => [$courseCode . ' ' . $engagement, 'Removed ' . $person . ' from the team'],
            'workload.rebalanced' => ['Workload matrix', 'Applied ' . (2 + self::rnd(4)) . ' suggested moves to even out hours'],
            'workload.reviewed'   => ['Workload matrix', 'Opened the department view for Semester ' . (1 + self::rnd(2))],

            'duty.requested'    => [$ref('DT', 300) . ' · ' . $courseCode . ' ' . self::pick(['invigilation', 'assignment marking', 'lab supervision', 'viva panel']), 'Needs ' . (1 + self::rnd(3)) . ' staff on ' . $from->format('j M') . ', ' . $slot],
            'duty.allocated'    => [$ref('DT', 300) . ' · ' . $courseCode . ' ' . self::pick(['invigilation', 'assignment marking', 'lab supervision']), 'Allocated ' . (1 + self::rnd(3)) . ' staff, least loaded first — ' . $from->format('j M')],
            'duty.swapped'      => [$ref('DT', 300) . ' · ' . $courseCode, 'Replaced ' . $person . ' by hand (they were already booked)'],
            'duty.invites_sent' => [$ref('DT', 300) . ' · ' . $courseCode, 'Invitations sent to ' . (1 + self::rnd(3)) . ' staff and the requesting lecturer'],
            'duty.cancelled'    => [$ref('DT', 300) . ' · ' . $courseCode, 'Cancelled — session postponed'],

            'evaluation.submitted' => ['Evaluation ' . $ref('EV', 200) . ' · ' . $person, $courseCode . ' — overall ' . number_format($score, 1) . ' out of 5'],
            'evaluation.reviewed'  => ['Evaluation ' . $ref('EV', 200) . ' · ' . $person, 'Marked reviewed — no further action'],
            'evaluation.flagged'   => ['Evaluation ' . $ref('EV', 200) . ' · ' . $person, 'Flagged for a conversation — overall ' . number_format($score, 1)],

            'staff.registration_approved' => ['Registration · ' . $person, 'Approved as ' . self::pick(['Junior Staff Member', 'Senior Lecturer'])],
            'staff.registration_rejected' => ['Registration · ' . $person, 'Rejected — staff code not on the department list'],
            'staff.profile_updated'       => ['Own profile', self::pick(['Changed office and extension', 'Updated contact number', 'Changed profile photo', 'Updated bio'])],
            'staff.availability_updated'  => ['Own weekly availability', 'Marked ' . (2 + self::rnd(8)) . ' slots free, ' . (1 + self::rnd(4)) . ' busy'],
            'staff.paused'                => ['Allocation status · ' . $person, 'Paused from duty allocation until further notice'],
            'staff.role_handover'         => [self::pick(['Coordinator', 'Timetable Officer', 'Department In-Charge']) . ' role', 'Handed over to ' . $person . ' after e-mail verification'],

            'auth.login'          => ['Sign-in', 'Signed in successfully'],
            'auth.logout'         => ['Sign-out', 'Session ended' . (self::rnd(3) === 0 ? ' automatically after 30 minutes idle' : '')],
            'auth.login_failed'   => ['Sign-in', 'Wrong password — attempt ' . (1 + self::rnd(3)) . ' of 5'],
            'auth.password_reset' => ['Password', 'Reset using the e-mail code'],

            'message.sent'          => [self::pick(['Conversation with ' . $person, 'Group · Year ' . (1 + self::rnd(4)) . ' Coordination', 'Group · ' . $courseCode . ' team']), 'Message sent — the text itself is not recorded'],
            'message.group_created' => ['Group · ' . $courseCode . ' team', 'Created with ' . (2 + self::rnd(6)) . ' members'],

            'audit.exported'   => ['Activity log', 'Exported ' . (40 + self::rnd(300)) . ' entries as a CSV file'],
            'audit.viewed'     => ['Activity log', self::pick(['Department-wide view', 'Filtered to ' . $person, 'Filtered to this week'])],
            'access.denied'    => [self::pick(['/workload/scheduler', '/staff', '/evaluations', '/audit']), 'Not permitted for this account — nothing was shown'],
            'settings.changed' => ['System settings', self::pick(['Changed the semester date range', 'Changed notification defaults', 'Updated the department name'])],

            default => ['—', ''],
        };
    }

    /**
     * A few real course codes to name things after, so a row reads like the
     * department's own log rather than lorem ipsum. Same catalogue the workload
     * screens use — see WorkloadPrototypeData::courses().
     */
    private const COURSES = [
        ['SCS 1308', 'Foundations of Algorithms'],
        ['SCS 1309', 'Database Management Systems'],
        ['SCS 1310', 'Object Oriented Programming'],
        ['SCS 1311', 'Internet and Web Technologies'],
        ['SCS 1312', 'Operating System Concepts'],
        ['SCS 2311', 'Cryptography and Information Security'],
        ['SCS 2312', 'Computational Models'],
        ['SCS 2313', 'Computer System Architecture'],
        ['IS 1208', 'Systems Analysis and Design'],
        ['IS 1210', 'Database Systems'],
        ['IS 1211', 'Computer Networks'],
        ['IS 2210', 'Applied Data Science'],
        ['IS 2211', 'UI/UX Design'],
        ['EN 1203', 'Aesthetic Studies'],
    ];
}
