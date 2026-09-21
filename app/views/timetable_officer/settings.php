<?php
// timetable_officer/settings.php — "Settings" page (SettingsController).
// $profile/$formAction come from the controller (real StaffModel row);
// the actual markup lives in the shared components/settings_form.php,
// same as every role's Settings page.
$roleLabel = 'Timetable Officer';
require \app\core\Application::$ROOT_DIR . '/views/components/settings_form.php';
?>
<script src="/js/settings.js"></script>
