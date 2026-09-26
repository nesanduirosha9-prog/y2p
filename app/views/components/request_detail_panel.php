<!-- views/components/request_detail_panel.php -->
<style>
    #requestDetailPanel {
        position: fixed;
        top: 0;
        right: -400px; /* Hidden by default */
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
    }
    #requestDetailPanel.open {
        right: 0;
    }
    #requestDetailPanel .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }
    #requestDetailPanel .panel-close {
        cursor: pointer;
        font-size: 1.5rem;
        background: none;
        border: none;
        color: #333;
    }
    #requestDetailPanel .form-group {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
    }
    #requestDetailPanel .form-group label {
        font-weight: bold;
        margin-bottom: 5px;
    }
    #requestDetailPanel .form-group input,
    #requestDetailPanel .form-group textarea {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1rem;
    }
    #requestDetailPanel .panel-status {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 15px;
        color: #fff;
        width: fit-content;
    }
    #requestDetailPanel .panel-status.status-pending  { background: #f4a261; }
    #requestDetailPanel .panel-status.status-approved { background: #2a9d8f; }
    #requestDetailPanel .panel-status.status-rejected { background: #e63946; }

    #requestDetailPanel .btn-row {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    #requestDetailPanel .btn-update,
    #requestDetailPanel .btn-cancel {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        cursor: pointer;
    }
    #requestDetailPanel .btn-update {
        background: #000000ff;
        color: white;
    }
    #requestDetailPanel .btn-update:hover {
        background: #000000ff;
    }
    #requestDetailPanel .btn-cancel {
        background: #f00418ff;
        color: white;
    }
    #requestDetailPanel .btn-cancel:hover {
        background: #f00418ff;
    }

    @media (max-width: 768px) {
        #requestDetailPanel {
            width: 300px;
        }
    }
</style>

<div id="requestDetailPanel">
    <div class="panel-header">
        <h3 style="margin: 0;">Manage Request</h3>
        <button type="button" class="panel-close" id="closeDetailPanelBtn">&times;</button>
    </div>

    <span id="detailStatusBadge" class="panel-status"></span>

    <form id="requestDetailForm">
        <input type="hidden" id="detailRequestId" name="request_id">

        <div class="form-group">
            <label for="detailWeeks">Number of Weeks</label>
            <input type="number" id="detailWeeks" name="detailWeeks" min="1" required>
            <small id="detailWeeksHint" style="color: #666; margin-top: 4px;"></small>
        </div>
    

        <div class="form-group">
            <label for="detailDescription">Description</label>
            <textarea id="detailDescription" name="detailDescription" rows="5" required></textarea>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn-update" id="updateRequestBtn">Update</button>
            <button type="button" class="btn-cancel" id="deleteRequestBtn">Cancel Request</button>
        </div>
    </form>
</div>