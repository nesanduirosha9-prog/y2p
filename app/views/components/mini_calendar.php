<!-- views/components/mini_calendar.php -->
<style>
    #miniCalendarPopup {
        position: absolute;
        top: 45px;
        left: 0;
        width: 260px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 9998;
        padding: 12px;
        box-sizing: border-box;
        display: none;
        font-family: inherit;
    }
    #miniCalendarPopup.open {
        display: block;
    }
    .mc-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .mc-header button {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        padding: 4px 8px;
        border-radius: 4px;
        color: #333;
    }
    .mc-header button:hover {
        background: #f0f0f0;
    }
    .mc-title {
        font-weight: bold;
        font-size: 0.95rem;
    }
    .mc-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        text-align: center;
    }
    .mc-weekday {
        font-size: 0.7rem;
        font-weight: bold;
        color: #888;
        padding: 4px 0;
    }
    .mc-day {
        padding: 6px 0;
        font-size: 0.85rem;
        cursor: pointer;
        border-radius: 4px;
        color: #333;
    }
    .mc-day:hover {
        background: #f0f0f0;
    }
    .mc-day.mc-other-month {
        color: #ccc;
    }
    .mc-day.mc-today {
        border: 1px solid #2a9d8f;
    }
    .mc-day.mc-selected {
        background: #2a9d8f;
        color: #fff;
    }
    .mc-day.mc-weekend {
        color: #bbb;
    }
</style>

<div id="miniCalendarPopup">
    <div class="mc-header">
        <button type="button" id="mcPrevMonth">&#8249;</button>
        <span class="mc-title" id="mcMonthLabel"></span>
        <button type="button" id="mcNextMonth">&#8250;</button>
    </div>
    <div class="mc-grid" id="mcWeekdayRow">
        <span class="mc-weekday">Mo</span>
        <span class="mc-weekday">Tu</span>
        <span class="mc-weekday">We</span>
        <span class="mc-weekday">Th</span>
        <span class="mc-weekday">Fr</span>
        <span class="mc-weekday">Sa</span>
        <span class="mc-weekday">Su</span>
    </div>
    <div class="mc-grid" id="mcDaysGrid"></div>
</div>