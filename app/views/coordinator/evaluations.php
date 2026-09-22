<?php

// coordinator/evaluations.php — Coordinator review view for Junior Staff Evaluations.

use app\core\Application;

$mode = 'review';
?>

<div class="coordinator-eval-view">
    <div class="page-head">
        <div>
            <h2>Junior Staff Evaluations Dashboard</h2>
            <p class="page-head-sub">Comprehensive overview of performance ratings, strengths, and recommendations across all faculty modules</p>
        </div>
    </div>

    <?php require Application::$ROOT_DIR . '/views/components/evaluations_review.php'; ?>
</div>

<script src="/js/evaluations.js"></script>

