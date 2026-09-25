<!-- views/components/staff_assign_panel.php -->
<style>
    #staffAssignPanel {
        position: fixed;
        top: 0;
        right: -400px;
        width: 350px;
        height: 100vh;
        background: #fff;
        box-shadow: -4px 0 15px rgba(0,0,0,0.2);
        z-index: 9999;
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
        padding: 20px;
        box-sizing: border-box;
        min-height: 0;
    }
    #staffAssignPanel.open {
        right: 0;
    }
    #staffAssignPanel .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        flex-shrink: 0;
    }
    #staffAssignPanel .panel-close {
        cursor: pointer;
        font-size: 1.5rem;
        background: none;
        border: none;
        color: #333;
    }
    #staffAssignPanel .panel-subtitle {
        font-size: 0.85rem;
        color: #777;
        margin: -12px 0 15px;
        flex-shrink: 0;
    }
    #staffAssignPanel form#staffAssignForm {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0; /* critical: lets flex children shrink instead of overflowing */
    }
    #staffAssignPanel .staff-list {
        flex: 1;
        min-height: 0; /* critical: allows this to scroll instead of growing with content */
        overflow-y: auto;
        border: 1px solid #eee;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    #staffAssignPanel .staff-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
    }
    #staffAssignPanel .staff-item:last-child {
        border-bottom: none;
    }
    #staffAssignPanel .staff-item:hover {
        background: #f9f9f9;
    }
    #staffAssignPanel .staff-item input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #2a9d8f;
        cursor: pointer;
    }
    #staffAssignPanel .staff-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #2a9d8f;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
        flex-shrink: 0;
    }
    #staffAssignPanel .staff-name {
        font-size: 0.9rem;
        color: #333;
    }
    #staffAssignPanel .form-group {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }
    #staffAssignPanel .form-group label {
        font-weight: bold;
        margin-bottom: 5px;
    }
    #staffAssignPanel .form-group textarea {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1rem;
        resize: vertical;
    }
    #staffAssignPanel .btn-assign {
        background: #2a9d8f;
        color: white;
        padding: 10px;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        cursor: pointer;
        flex-shrink: 0;
    }
    #staffAssignPanel .btn-assign:hover {
        background: #21867a;
    }
    @media (max-width: 768px) {
        #staffAssignPanel {
            width: 300px;
        }
    }
</style>

<div id="staffAssignPanel">
    <div class="panel-header">
        <h3 style="margin: 0;">Assign Staff</h3>
        <button type="button" class="panel-close" id="closeStaffPanelBtn">&times;</button>
    </div>
    <p class="panel-subtitle" id="staffPanelSessionLabel">Session</p>

    <form id="staffAssignForm">
        <div class="staff-list" id="staffListContainer">
            <!-- Populated dynamically by timetable.js -->
        </div>

        <div class="form-group">
            <label for="staffAssignDescription">Description</label>
            <textarea id="staffAssignDescription" name="staffAssignDescription" rows="3" placeholder="Notes for this assignment..."></textarea>
        </div>

        <button type="submit" class="btn-assign">Assign</button>
    </form>
</div>