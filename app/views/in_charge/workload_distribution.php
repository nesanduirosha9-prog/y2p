<?php

// in_charge/workload_distribution.php — Executive Workload Distribution View for Department In-Charge.

use app\core\Application;

$mode = 'full';
$showSummaryCards = true;
?>

<div class="in-charge-workload-view">
    <div class="page-head">
        <div>
            <h2>Faculty Workload & Course Allocation</h2>
            <p class="page-head-sub">Executive oversight of teaching load distribution, junior staff allocations, and department capacity</p>
        </div>
    </div>

    <?php require Application::$ROOT_DIR . '/views/components/workload_matrix.php'; ?>
</div>

<script src="/js/workload_matrix.js"></script>

