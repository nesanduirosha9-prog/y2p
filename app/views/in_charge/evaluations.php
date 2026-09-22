<?php

// in_charge/evaluations.php — Executive Evaluation Appraisal Dashboard for Department In-Charge.

use app\core\Application;

$mode = 'review';
?>

<div class="in-charge-eval-view">
    <div class="page-head">
        <div>
            <h2>Staff Performance & Appraisal Center</h2>
            <p class="page-head-sub">Review academic performance metrics, student feedback reports, and coordinator recommendations</p>
        </div>
    </div>

    <?php require Application::$ROOT_DIR . '/views/components/evaluations_review.php'; ?>
</div>

<script src="/js/evaluations.js"></script>

