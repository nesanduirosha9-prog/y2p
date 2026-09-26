<?php

use app\core\Application;
use app\core\ViewHelpers;

// Coordinator "Staff" screen — delegates to the shared staff_directory component.
// $pending and $activeStaff are passed from StaffController.
$basePath = '/staff';
require Application::$ROOT_DIR . '/views/components/staff_directory.php';
?>

<script src="<?= ViewHelpers::asset('/js/coordinator/staff.js') ?>"></script>
