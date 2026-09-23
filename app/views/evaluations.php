<?php

// evaluations.php — the junior staff evaluation review dashboard, for both the
// Coordinator and the Department In-Charge.
//
// Replaces coordinator/evaluations.php and in_charge/evaluations.php, which
// were the same file apart from the wrapper class and two lines of copy.
// $viewClass / $heading / $subheading come from EvaluationsController::COPY;
// the two wordings differ on purpose and are kept verbatim.

use app\core\Application;

// Read by components/evaluations_review.php below.
$mode = 'review';
?>

<div class="<?= htmlspecialchars($viewClass) ?>">
    <div class="page-head">
        <div>
            <h2><?= htmlspecialchars($heading) ?></h2>
            <p class="page-head-sub"><?= htmlspecialchars($subheading) ?></p>
        </div>
    </div>

    <?php require Application::$ROOT_DIR . '/views/components/evaluations_review.php'; ?>
</div>

<script src="/js/evaluations.js"></script>
