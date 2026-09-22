<?php

// components/sm_request_modal.php — Reusable modal for Lecturers to submit SM (Supportive Member) requests.
// Modeled after spreadsheet sheets "Request" (Screenshots 1 & 4).

$coursesList = $coursesList ?? [
    ['code' => 'IS 4115', 'name' => 'In-class Assignment - IS4115'],
    ['code' => 'SCS 2314', 'name' => 'Middleware Architecture Practical'],
    ['code' => 'IS 1212', 'name' => 'Probability and Statistics Lab Evaluation'],
    ['code' => 'SCS 2313', 'name' => 'Computer Architecture In-class Quiz'],
    ['code' => 'SCS 2312', 'name' => 'Computational Models Practical Assessment'],
    ['code' => 'SCS 4223', 'name' => 'SE Final Year Projects Evaluation'],
    ['code' => 'IS 4101', 'name' => 'IS Final Year Projects Evaluation'],
    ['code' => 'SCS 2308', 'name' => 'Numerical Methods Practical Examination'],
];

$timeSlotOptions = [
    '8-9' => '08:00 - 09:00',
    '9-10' => '09:00 - 10:00',
    '10-11' => '10:00 - 11:00',
    '11-12' => '11:00 - 12:00',
    '12-1' => '12:00 - 13:00',
    '1-2' => '13:00 - 14:00',
    '2-3' => '14:00 - 15:00',
    '3-4' => '15:00 - 16:00',
    '4-5' => '16:00 - 17:00',
    '5-6' => '17:00 - 18:00',
];
?>

<!-- Request SM Modal -->
<div class="modal-backdrop" id="smRequestModal" style="display: none;">
    <div class="modal-card sm-request-card">
        <div class="modal-head">
            <div>
                <h3><i class="fa-solid fa-user-plus text-primary"></i> Request Supportive Members (SMs)</h3>
                <p class="modal-sub">Submit duty requirements for demonstrators and instructors for upcoming course sessions</p>
            </div>
            <button type="button" class="modal-close" id="closeSmModalBtn" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="smRequestForm" onsubmit="event.preventDefault(); window.handleSmRequestSubmit && window.handleSmRequestSubmit();">
            <div class="modal-body sm-request-body">
                <div class="form-group full-width">
                    <label for="smCourseSelect">Course / Module <span class="req-star">*</span></label>
                    <select id="smCourseSelect" class="form-select" required>
                        <option value="">Select course module...</option>
                        <?php foreach ($coursesList as $c): ?>
                            <option value="<?= htmlspecialchars($c['code']) ?>"><?= htmlspecialchars($c['code'] . ' — ' . $c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="smDutyTitle">Duty / Task Title <span class="req-star">*</span></label>
                    <input type="text" id="smDutyTitle" class="form-input" placeholder="e.g. In-class Assignment, Practical Exam, Lab Supervision, Project Evaluation" required>
                </div>

                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="smDutyDate">Duty Date <span class="req-star">*</span></label>
                        <input type="date" id="smDutyDate" class="form-input" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                    </div>

                    <div class="form-group half-width">
                        <label for="smStaffCount">Supportive Members Needed <span class="req-star">*</span></label>
                        <div class="number-counter">
                            <button type="button" class="counter-btn" id="decrementStaffCount"><i class="fa-solid fa-minus"></i></button>
                            <input type="number" id="smStaffCount" class="counter-input" value="4" min="1" max="15" required readonly>
                            <button type="button" class="counter-btn" id="incrementStaffCount"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Required Time Slots <span class="req-star">*</span></label>
                    <p class="field-hint">Select all hours the supportive members must be present:</p>
                    <div class="time-slots-chips" id="timeSlotsContainer">
                        <?php foreach ($timeSlotOptions as $slotKey => $slotLabel): ?>
                            <label class="time-slot-chip">
                                <input type="checkbox" name="sm_time_slots[]" value="<?= htmlspecialchars($slotKey) ?>">
                                <span class="chip-text"><?= htmlspecialchars($slotKey) ?></span>
                                <span class="chip-hover-label"><?= htmlspecialchars($slotLabel) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="smNotes">Special Instructions / Requirements <span class="font-normal">(optional)</span></label>
                    <textarea id="smNotes" class="form-textarea" rows="2" placeholder="Specify requirements e.g. familiarity with Docker, Java GUI testing, assignment rubrics..."></textarea>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn-outline" id="cancelSmModalBtn">Cancel</button>
                <button type="submit" class="btn-primary" id="submitSmRequestBtn">
                    <i class="fa-solid fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

