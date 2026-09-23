<?php

use app\core\Application;

// Coordinator "Staff" screen — delegates to the shared staff_directory component.
// $pending and $activeStaff are passed from StaffController.
$basePath = '/coordinator/staff';
require Application::$ROOT_DIR . '/views/components/staff_directory.php';
?>

<script src="/js/coordinator/staff.js"></script>
