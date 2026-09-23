<?php

// coordinator/workload_distribution.php — Workload Distribution view for Coordinator.
// Embeds the reusable components/workload_matrix.php in 'full' mode.

use app\core\Application;

$mode = 'full';
$showSummaryCards = true;
?>

<div class="coordinator-workload-view">
    <div class="page-head">
        <div>
            <h2>Course Workload Matrix</h2>
            <p class="page-head-sub">Full overview of faculty courses, lecturer-in-charge assignments, and supportive member teams</p>
        </div>
        <div class="page-head-actions">
            <a href="/workload/scheduler" class="btn-primary">
                <i class="fa-solid fa-calendar-check"></i> Open Workload Scheduler
            </a>
        </div>
    </div>

    <?php require Application::$ROOT_DIR . '/views/components/workload_matrix.php'; ?>
</div>

<script src="/js/workload_matrix.js"></script>

