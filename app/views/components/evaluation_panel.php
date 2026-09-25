<?php

// components/evaluation_panel.php — the course evaluation pane on My Courses.
// One row per junior staff member on the course: a rating and a comment.
// js/instructor/courses.js fills the heading and the rows when a course's
// Evaluate button is clicked, and reads them back on submit.
?>

<div class="courses-flow-pane" id="coursesEvalPane">
    <div class="eval-flow-header">
        <div class="eval-flow-left">
            <button type="button" class="btn-flow-back" id="backToCoursesBtn" aria-label="Back to courses" title="Back to courses">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="eval-course-info">
                <div class="eval-course-title-group">
                    <h2 id="evalCourseHeading" class="eval-course-heading">—</h2>
                    <span class="pill pill-year-1" id="evalCourseYearPill">Year 1</span>
                    <span class="pill pill-muted" id="evalCourseProgPill">CS</span>
                </div>
                <p class="eval-course-subtext" id="evalCourseSubText">—</p>
            </div>
        </div>
        <div class="eval-flow-actions">
            <button type="button" class="btn-primary btn-submit-eval" id="submitAllCourseEvaluationsBtn">
                Submit evaluations
            </button>
        </div>
    </div>

    <div class="dir-card">
        <div class="dir-scroll">
            <table class="dir-table eval-course-table">
                <thead>
                    <tr>
                        <th style="min-width: 200px;">Junior staff</th>
                        <th style="width: 220px;">Rating</th>
                        <th style="min-width: 280px;">Comments</th>
                    </tr>
                </thead>
                <tbody id="evalInstructorsContainer">
                    <!-- Filled by courses.js -->
                </tbody>
            </table>
        </div>
    </div>
</div>
