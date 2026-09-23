<?php

// workload_distribution.php — the department workload matrix, for both the
// Coordinator and the Department In-Charge.
//
// Replaces coordinator/workload_distribution.php and
// in_charge/workload_distribution.php, which were the same file apart from the
// wrapper class, two lines of copy, and the Coordinator's scheduler button.
// $viewClass / $heading / $subheading / $showSchedulerLink come from
// WorkloadController::COPY — the two wordings differ on purpose and are kept
// verbatim.

use app\core\Application;

// Read by components/workload_matrix.php below.
$mode = 'full';
$showSummaryCards = true;
?>

<div class="<?= htmlspecialchars($viewClass) ?>">
    <div class="page-head">
        <div>
            <h2><?= htmlspecialchars($heading) ?></h2>
            <p class="page-head-sub"><?= htmlspecialchars($subheading) ?></p>
        </div>
        <?php if (!empty($showSchedulerLink)): ?>
            <div class="page-head-actions">
                <a href="/workload/scheduler" class="btn-primary">
                    <i class="fa-solid fa-calendar-check"></i> Open Workload Scheduler
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php require Application::$ROOT_DIR . '/views/components/workload_matrix.php'; ?>
</div>

<script src="/js/workload_matrix.js"></script>
