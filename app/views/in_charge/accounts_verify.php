<?php
// Step 3 of the handover flow — OTP verification. $toStaff / $devOtp come
// from AccountsController::verifyView(). $devOtp is only shown because no
// mail server exists yet (see the in-page notice below) — swap this for a
// real emailed code once one does.
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

        <?php if (!empty($devOtp)): ?>
            <p class="handover-dev-otp">
                <i class="fa-solid fa-circle-info"></i>
                No mail server is configured yet — for now, your code is <strong><?= htmlspecialchars($devOtp) ?></strong>.
            </p>
        <?php endif; ?>

        <div class="form-row">
            <label for="handoverOtp">Enter 6-digit OTP</label>
            <input type="text" id="handoverOtp" maxlength="6" inputmode="numeric" placeholder="000000">
        </div>
        <p class="form-error" id="otpError" hidden></p>

        <div class="modal-foot handover-actions">
            <a class="btn-cancel" href="/in-charge/accounts">Back</a>
            <button type="button" class="btn-block" id="btnVerifyOtp">OK</button>
        </div>
    </div>
</div>

<script src="/js/in_charge/accounts.js"></script>
