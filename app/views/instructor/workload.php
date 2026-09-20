<?php

// Instructor Workload View
$title = "My Workload";

// Dummy data for the UI
$semesterOptions = ["Semester 1 - 2026", "Semester 2 - 2025", "Semester 1 - 2025"];
$monthOptions = ["March 2026", "February 2026", "January 2026"];
$weekOptions = ["Week 5", "Week 4", "Week 3"];

$curSem = ['assigned' => 320, 'completed' => 248];
$curMonth = ['assigned' => 42, 'completed' => 39];
$curWeek = ['assigned' => 12, 'completed' => 10];
?>

<div class="wk-container">
    <!-- Tab Bar -->
    <div class="wk-tabs">
        <a href="#" class="wk-tab active">
            <i class="fa-solid fa-chart-bar"></i> Overview
        </a>
        <a href="#" class="wk-tab">
            <i class="fa-solid fa-layer-group"></i> Assigned Work
        </a>
        <a href="#" class="wk-tab">
            <i class="fa-solid fa-list-check"></i> History
        </a>
    </div>

    <div class="wk-body">
        <!-- Filter Bar -->
        <div class="wk-filter-bar">
            <div class="wk-filter-title">
                <i class="fa-solid fa-chart-bar" style="color: #1a3a6b;"></i>
                <span>Filter Workload</span>
            </div>
            <div class="v-divider"></div>
            <button class="wk-btn-reset"><i class="fa-solid fa-xmark"></i> Reset</button>
            <div class="v-divider"></div>
            
            <div class="wk-filter-group">
                <label>SEMESTER</label>
                <div class="wk-select-wrapper">
                    <select class="wk-select">
                        <option value="">Select...</option>
                        <?php foreach($semesterOptions as $opt): ?>
                            <option><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </div>
            </div>

            <div class="wk-filter-group">
                <label>MONTH</label>
                <div class="wk-select-wrapper">
                    <select class="wk-select">
                        <option value="">Select...</option>
                        <?php foreach($monthOptions as $opt): ?>
                            <option><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </div>
            </div>

            <div class="wk-filter-group">
                <label>WEEK</label>
                <div class="wk-select-wrapper">
                    <select class="wk-select">
                        <option value="">Select...</option>
                        <?php foreach($weekOptions as $opt): ?>
                            <option><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </div>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="wk-overview-grid">
            <!-- Semester Workload -->
            <div class="wk-card">
                <div class="wk-card-header" style="background: #f4f7fc;">
                    <div class="wk-icon-box" style="background: #e8edf5; color: #1a3a6b;">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <div class="wk-card-titles">
                        <p class="wk-card-label">SEMESTER WORKLOAD</p>
                        <p class="wk-card-sub">Semester 1 - 2026</p>
                    </div>
                </div>
                <div class="wk-card-body">
                    <div class="wk-stat">
                        <p class="wk-stat-label">ASSIGNED</p>
                        <p class="wk-stat-value text-blue"><?= $curSem['assigned'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                    <div class="v-divider"></div>
                    <div class="wk-stat">
                        <p class="wk-stat-label">COMPLETED</p>
                        <p class="wk-stat-value text-green"><?= $curSem['completed'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                </div>
                <div class="wk-card-footer">
                    <div class="wk-progress-track">
                        <div class="wk-progress-fill bg-blue" style="width: <?= round(($curSem['completed']/$curSem['assigned'])*100) ?>%;"></div>
                    </div>
                    <p class="wk-progress-text"><?= round(($curSem['completed']/$curSem['assigned'])*100) ?>% completed</p>
                </div>
            </div>

            <!-- Monthly Workload -->
            <div class="wk-card">
                <div class="wk-card-header" style="background: #f7f4fd;">
                    <div class="wk-icon-box" style="background: #ede9fe; color: #7c3aed;">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <div class="wk-card-titles">
                        <p class="wk-card-label">MONTHLY WORKLOAD</p>
                        <p class="wk-card-sub">March 2026</p>
                    </div>
                </div>
                <div class="wk-card-body">
                    <div class="wk-stat">
                        <p class="wk-stat-label">ASSIGNED</p>
                        <p class="wk-stat-value text-purple"><?= $curMonth['assigned'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                    <div class="v-divider"></div>
                    <div class="wk-stat">
                        <p class="wk-stat-label">COMPLETED</p>
                        <p class="wk-stat-value text-green"><?= $curMonth['completed'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                </div>
                <div class="wk-card-footer">
                    <div class="wk-progress-track">
                        <div class="wk-progress-fill bg-purple" style="width: <?= round(($curMonth['completed']/$curMonth['assigned'])*100) ?>%;"></div>
                    </div>
                    <p class="wk-progress-text"><?= round(($curMonth['completed']/$curMonth['assigned'])*100) ?>% completed</p>
                </div>
            </div>

            <!-- Weekly Workload -->
            <div class="wk-card">
                <div class="wk-card-header" style="background: #fefce8;">
                    <div class="wk-icon-box" style="background: #fef9c3; color: #ca8a04;">
                        <i class="fa-solid fa-calendar-week"></i>
                    </div>
                    <div class="wk-card-titles">
                        <p class="wk-card-label">WEEKLY WORKLOAD</p>
                        <p class="wk-card-sub">Week 5</p>
                    </div>
                </div>
                <div class="wk-card-body">
                    <div class="wk-stat">
                        <p class="wk-stat-label">ASSIGNED</p>
                        <p class="wk-stat-value text-yellow"><?= $curWeek['assigned'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                    <div class="v-divider"></div>
                    <div class="wk-stat">
                        <p class="wk-stat-label">COMPLETED</p>
                        <p class="wk-stat-value text-green"><?= $curWeek['completed'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                </div>
                <div class="wk-card-footer">
                    <div class="wk-progress-track">
                        <div class="wk-progress-fill bg-yellow" style="width: <?= round(($curWeek['completed']/$curWeek['assigned'])*100) ?>%;"></div>
                    </div>
                    <p class="wk-progress-text"><?= round(($curWeek['completed']/$curWeek['assigned'])*100) ?>% completed</p>
                </div>
            </div>
        </div>
    </div>
</div>
