<?php
// settings.php — "Settings" page (SettingsController), for every role.
//
// Replaces instructor/settings.php and timetable_officer/settings.php, which
// were the same 9 lines apart from the $roleLabel string. $profile and
// $formAction come from the controller (a real StaffModel row); the markup
// itself lives in the shared components/settings_form.php.
//
// $roleLabel is the badge under the user's name. It is derived from the
// session rather than hardcoded per view: position wins when there is one
// (Coordinator, In Charge), then role.
if (($_SESSION['role'] ?? '') === 'timetable_officer') {
    $roleLabel = 'Timetable Officer';
} elseif (!empty($_SESSION['position'])) {
    $roleLabel = ucwords(str_replace('_', ' ', $_SESSION['position']));
} else {
    $roleLabel = 'Academic Staff Member';
}

require \app\core\Application::$ROOT_DIR . '/views/components/settings_form.php';
?>
<!-- Show/hide buttons on the Password card's fields (same as the sign-in pages) -->
<script src="/js/password_toggle.js"></script>
<script src="/js/settings.js"></script>
