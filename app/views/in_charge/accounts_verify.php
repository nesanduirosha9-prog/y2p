<?php
// Step 3 of the handover flow — OTP verification. $toStaff comes from
// AccountsController::verifyView(). The code itself was emailed to
// $toStaff's address by selectSubmit() via EmailService.
?>

<div class="accounts-view">
    <div class="page-head">
        <div>
            <h2>Enter Verification OTP</h2>
            <p class="page-head-sub">An OTP has been sent to your registered email address.</p>
        </div>
    </div>

    <div class="dir-card handover-card">
        <div class="handover-current">
            <span class="lec-avatar"><?= htmlspecialchars(strtoupper(substr($toStaff['name'], 0, 2))) ?></span>
            <div>
                <p class="handover-name"><?= htmlspecialchars($toStaff['name']) ?></p>
                <p class="page-head-sub"><?= htmlspecialchars($toStaff['email']) ?></p>
            </div>
        </div>

        <div class="form-row">
            <label for="handoverOtp">Enter 6-digit OTP</label>
            <input type="text" id="handoverOtp" maxlength="6" inputmode="numeric" placeholder="000000">
        </div>
        <p class="form-error" id="otpError" hidden></p>

        <div class="modal-foot handover-actions">
            <a class="btn-cancel" href="/settings/handover">Back</a>
            <button type="button" class="btn-block" id="btnVerifyOtp">OK</button>
        </div>
    </div>
</div>

<script src="/js/in_charge/accounts.js"></script>
