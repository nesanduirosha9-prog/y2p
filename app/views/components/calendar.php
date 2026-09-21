<style>
/* ==============================
   Reusable Calendar Component
   ============================== */
.lv-calendar {
    border: 1px solid #dde3ee;
    border-radius: 10px;
    padding: 12px;
}

.lv-calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 700;
    color: #0f1c2e;
    margin-bottom: 8px;
}

.lv-calendar-header button {
    background: none;
    border: none;
    color: #6b7c96;
    cursor: pointer;
    padding: 4px 8px;
}

.lv-calendar-weekdays,
.lv-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    text-align: center;
}

.lv-calendar-weekdays span {
    font-size: 10px;
    font-weight: 700;
    color: #9ca3af;
    padding: 4px 0;
}

.lv-day {
    background: none;
    border: none;
    border-radius: 7px;
    padding: 7px 0;
    font-size: 12.5px;
    color: #374151;
    cursor: pointer;
}

.lv-day:hover { 
    background: #f4f6f9; 
}

.lv-day.lv-day-empty { 
    visibility: hidden; 
    cursor: default; 
}

.lv-day.lv-day-selected { 
    background: #1a3a6b; 
    color: #fff; 
    font-weight: 700; 
}
</style>

<div class="lv-calendar" id="lvCalendar">
    <div class="lv-calendar-header">
        <button type="button" id="lvPrevMonth"><i class="fa-solid fa-chevron-left"></i></button>
        <span id="lvCalendarLabel"></span>
        <button type="button" id="lvNextMonth"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
    <div class="lv-calendar-weekdays">
        <span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span>
    </div>
    <div class="lv-calendar-grid" id="lvCalendarGrid"></div>
</div>