<?php

namespace app\core;

class ViewHelpers
{
    // 12-hour label: 8 -> "8 AM", 12 -> "12 PM", 17 -> "5 PM".
    // Format is load-bearing: js/instructor/timetable.js:21 parses this
    // out of data-start with /^(\d+)\s+(AM|PM)$/.
    public static function hourLabel(int $h): string
    {
        $suffix  = $h < 12 ? 'AM' : 'PM';
        $display = $h % 12 === 0 ? 12 : $h % 12;
        return "{$display} {$suffix}";
    }

    /**
     * The one way to print a code (see .code-badge in components.css).
     * $kind: 'course' | 'lecturer' | 'staff'. Returns escaped HTML — echo it
     * directly. js/code_badge.js renders the same markup client-side.
     */
    public static function codeBadge(string $code, string $kind, string $title = '', string $extraClass = ''): string
    {
        $cls = trim("code-badge code-badge--{$kind} {$extraClass}");
        $t   = $title !== '' ? ' title="' . htmlspecialchars($title) . '"' : '';
        return '<span class="' . htmlspecialchars($cls) . '"' . $t . '>' . htmlspecialchars($code) . '</span>';
    }

    public static function staffInitials(string $name): string
    {
        $parts   = preg_split('/\s+/', trim($name));
        $letters = array_map(fn($p) => strtoupper(substr($p, 0, 1)), array_slice($parts, 0, 2));
        return implode('', $letters);
    }

    // Returns a POSITION TITLE, not a person's name.
    // Plain text by design — callers escape at output.
    public static function roleHolderLabel(array $h): string
    {
        if (($h['role'] ?? '') === 'timetable_officer') {
            return 'Timetable Officer';
        }
        return ($h['position'] ?? '') === 'in_charge' ? 'In-Charge' : 'Coordinator';
    }

    // Role and rank label for staff cards/tables (e.g. Lecturer · In-Charge).
    // The UI says "Lecturer", never "Senior Lecturer": the only other academic
    // rank is Junior Staff, so "Senior" implied a "Junior Lecturer" that does
    // not exist. The stored rank value is still 'senior'.
    public static function staffRoleLabel(array $s): string
    {
        if (($s['role'] ?? '') === 'timetable_officer') {
            return 'Timetable Officer';
        }
        $rank = ($s['academic_rank'] ?? '') === 'senior' ? 'Lecturer' : 'Junior Staff Member';
        if (($s['position'] ?? '') === 'coordinator') {
            return $rank . ' · Coordinator';
        }
        if (($s['position'] ?? '') === 'in_charge') {
            return $rank . ' · In-Charge';
        }
        return $rank;
    }

    // --- The signed-in user's own avatar ------------------------------------
    // staffInitials() above is for *other* people in directory tables, where a
    // name is all we have. For the signed-in user the badge code is better: it
    // is already the 3 letters printed on their staff card (TMF, TMO, MKA), it
    // is unique, and it never collides the way two people's initials can.

    // A photo, when one has been chosen, is held client-side only: there is no
    // avatar column on `staff` yet, so js/dashboard.js swaps the code out for
    // the stored image. Everything server-rendered shows the code.
    /** The signed-in user's 3-letter badge code, e.g. 'TMF'. '??' when logged out. */
    public static function currentAvatarCode(): string
    {
        $code = trim((string)($_SESSION['staff_code'] ?? ''));
        return $code === '' ? '??' : strtoupper($code);
    }

    /**
     * The signed-in user's own name, for the header profile chip.
     *
     * Read from the session, which AuthController::login() fills. Sessions
     * created before that key existed — and a row whose name is still blank,
     * which a self-registered account has until it saves Settings — fall back
     * to one lookup, cached straight back into the session so this stays a
     * single query per session rather than one per page render. The badge code
     * is the last resort, so the chip is never empty.
     */
    public static function currentUserName(): string
    {
        if (!isset($_SESSION['staff_code'])) {
            return self::currentAvatarCode();
        }
        if (!array_key_exists('name', $_SESSION)) {
            $row = (new \app\models\StaffModel())->findByCode($_SESSION['staff_code']);
            $_SESSION['name'] = $row['name'] ?? null;
        }
        $name = trim((string)($_SESSION['name'] ?? ''));
        return $name === '' ? self::currentAvatarCode() : $name;
    }
}


