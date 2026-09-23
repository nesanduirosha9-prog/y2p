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

    // Role and rank label for staff cards/tables (e.g. Senior Lecturer · Coordinator)
    public static function staffRoleLabel(array $s): string
    {
        if (($s['role'] ?? '') === 'timetable_officer') {
            return 'Timetable Officer';
        }
        $rank = ($s['academic_rank'] ?? '') === 'senior' ? 'Senior Lecturer' : 'Junior Staff Member';
        if (($s['position'] ?? '') === 'coordinator') {
            return $rank . ' · Coordinator';
        }
        if (($s['position'] ?? '') === 'in_charge') {
            return $rank . ' · In-Charge';
        }
        return $rank;
    }
}


