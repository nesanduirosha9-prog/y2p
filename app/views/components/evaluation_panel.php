<?php

// components/evaluation_panel.php — Reusable Course Evaluation Detail Panel.
// Used by Lecturers, In-Charge, and any senior academic staff evaluating supportive members.
// Fits inside master-detail sliding flows (e.g. instructor/courses.php).
// Features: Back button, Course metadata header, Submit button, Info notice, and dynamic instructor evaluation cards.
?>

<div class="courses-flow-pane" id="coursesEvalPane">
    <!-- Header bar with Back Button, Course Title, Badges & Submit Button -->
    <div class="eval-flow-header">
        <div class="eval-flow-left">
            <button type="button" class="btn-flow-back" id="backToCoursesBtn" aria-label="Back to courses list" title="Return to courses table">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="eval-course-avatar" id="evalCourseAvatar">CS</div>
            <div class="eval-course-info">
                <div class="eval-course-title-group">
                    <h2 id="evalCourseHeading" class="eval-course-heading">CS1102 — Discrete Mathematics</h2>
                    <span class="pill pill-year-1" id="evalCourseYearPill">Year 1</span>
                    <span class="pill pill-muted" id="evalCourseProgPill">CS</span>
                </div>
                <p class="eval-course-subtext" id="evalCourseSubText">
                    <span><i class="fa-solid fa-users"></i> <span id="evalAssignedCount">2</span> Supportive Members Assigned</span>
                    <span class="meta-dot">·</span>
                    <span><i class="fa-solid fa-award"></i> Junior Staff Evaluation</span>
                </p>
            </div>
        </div>
        <div class="eval-flow-actions">
            <button type="button" class="btn-primary btn-submit-eval" id="submitAllCourseEvaluationsBtn">
                <i class="fa-solid fa-paper-plane"></i> Submit Evaluations
            </button>
        </div>
    </div>

    <!-- Sleek Information Notice -->
    <div class="eval-flow-notice">
        <i class="fa-solid fa-circle-info"></i>
        <div>
            <strong>Junior Staff Performance Appraisal</strong>
            <p>Ratings and qualitative remarks entered here are dispatched directly to the <b>Course Coordinator</b> and <b>Department In-Charge</b> for official semester appraisal records.</p>
        </div>
    </div>

    <!-- Submission Success Banner (Shown dynamically after submission) -->
    <div id="evalSubmissionBanner" class="eval-submit-banner" style="display: none;"></div>

    <!-- Modern, Non-Cramped Instructor Evaluation Cards (Pure Vertical Scroll, Zero Horizontal Scroll) -->
    <div class="eval-instructors-list" id="evalInstructorsContainer">
        <!-- Dynamically populated via courses.js -->
    </div>
</div>

