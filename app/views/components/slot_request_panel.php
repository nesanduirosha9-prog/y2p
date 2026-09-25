<!-- views/components/slot_request_panel.php -->
<style>
    #slotRequestPanel {
        position: fixed;
        top: 0;
        right: -400px; /* Hidden by default */
        width: 350px;
        height: 100vh;
        background: #fff;
        box-shadow: -4px 0 15px rgba(0,0,0,0.2);
        z-index: 9999; /* Stay on top of everything */
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
        padding: 20px;
        box-sizing: border-box;
    }
    #slotRequestPanel.open {
        right: 0;
    }
    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }
    .panel-close {
        cursor: pointer;
        font-size: 1.5rem;
        background: none;
        border: none;
        color: #333;
    }
    .form-group {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
    }
    .form-group label {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .form-group input, .form-group textarea {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1rem;
    }
    .btn-submit {
        background: #2a9d8f;
        color: white;
        padding: 10px;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 10px;
    }
    .btn-submit:hover {
        background: #21867a;
    }
    /* Responsive for mobile landscape */
    @media (max-width: 768px) {
        #slotRequestPanel {
            width: 300px;
        }
    }
</style>

<div id="slotRequestPanel">
    <div class="panel-header">
        <h3 style="margin: 0;">Request Session</h3>
        <button type="button" class="panel-close" id="closePanelBtn">&times;</button>
    </div>
    <form id="slotRequestForm">
        <div class="form-group">
            <label for="reqWeeks">Number of Weeks</label>
            <input type="number" id="reqWeeks" name="reqWeeks" min="1" required>
            <small id="weeksHint" style="color: #666; margin-top: 4px;"></small>
        </div>
        <div class="form-group">
            <label for="reqDescription">Description</label>
            <textarea id="reqDescription" name="reqDescription" rows="5" placeholder="Enter session details..." required></textarea>
        </div>
        <button type="submit" class="btn-submit">Submit Request</button>
    </form>
</div>