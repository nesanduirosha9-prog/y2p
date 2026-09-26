<?php

namespace app\core;

// StaffEmail: which addresses may own a StaffSync login. Shared by signup
// (AuthController) and the Timetable Officer handover (in_charge/
// AccountsController), so both apply the same rule: the university domain
// (STAFF_EMAIL_DOMAIN), plus any address listed in AUTH_BYPASS_EMAILS (e.g. a
// Gmail used to demo real OTP delivery).
class StaffEmail
{
    public static function domain(): string
    {
        return strtolower(defined('STAFF_EMAIL_DOMAIN') ? STAFF_EMAIL_DOMAIN : 'ucsc.cmb.ac.lk');
    }

    public static function isAllowed(string $email): bool
    {
        $email = strtolower($email);
        if (str_ends_with($email, '@' . self::domain())) {
            return true;
        }
        $bypass = defined('AUTH_BYPASS_EMAILS') ? AUTH_BYPASS_EMAILS : '';
        $allowed = array_filter(array_map(fn($e) => strtolower(trim($e)), explode(',', $bypass)));
        return in_array($email, $allowed, true);
    }
}
