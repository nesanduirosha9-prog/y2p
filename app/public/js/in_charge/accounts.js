// In-Charge "Accounts" handover flow — candidate search/select (Step 2) and
// OTP verification (Step 3). Both steps live on separate pages, so this file
// just no-ops on whichever section isn't present on the current page.
document.addEventListener('DOMContentLoaded', function () {

    // --- Step 2: search + pick a replacement ---
    const view = document.querySelector('.accounts-view[data-position]');
    if (view) {
        const searchInput = document.getElementById('candidateSearch');
        const list = document.getElementById('candidateList');
        const emptyMsg = document.getElementById('candidateEmpty');
        const confirmBtn = document.getElementById('btnConfirmCandidate');

        function applySearch() {
            const q = (searchInput.value || '').trim().toLowerCase();
            let visible = 0;
            list.querySelectorAll('.candidate-row').forEach(function (row) {
                const show = !q || row.dataset.search.includes(q);
                row.hidden = !show;
                if (show) visible++;
            });
            if (emptyMsg) emptyMsg.hidden = visible !== 0;
        }
        if (searchInput) searchInput.addEventListener('input', applySearch);

        function updateConfirmState() {
            const picked = list.querySelector('input[name="candidate"]:checked');
            confirmBtn.disabled = !picked;
        }
        if (list) {
            list.addEventListener('change', updateConfirmState);
        }

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                const picked = list.querySelector('input[name="candidate"]:checked');
                if (!picked) return;

                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Sending code...';

                fetch('/settings/handover/select', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        position: view.dataset.position,
                        fromCode: view.dataset.fromCode,
                        toCode: picked.value
                    })
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            window.location.href = data.redirect || '/settings/handover/verify';
                        } else {
                            ttToast.error(data.message || 'Could not start the role change.');
                            confirmBtn.disabled = false;
                            confirmBtn.textContent = 'Confirm';
                        }
                    })
                    .catch(function () {
                        ttToast.error('Something went wrong. Please try again.');
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Confirm';
                    });
            });
        }
    }

    // --- Step 3: OTP verification ---
    const otpInput = document.getElementById('handoverOtp');
    const btnVerifyOtp = document.getElementById('btnVerifyOtp');
    const otpError = document.getElementById('otpError');

    if (otpInput && btnVerifyOtp) {
        otpInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });

        btnVerifyOtp.addEventListener('click', function () {
            const otp = otpInput.value.trim();
            if (otp.length !== 6) {
                otpError.textContent = 'Enter the full 6-digit code.';
                otpError.hidden = false;
                return;
            }

            btnVerifyOtp.disabled = true;
            fetch('/settings/handover/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp: otp })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        window.location.href = data.redirect || '/settings/handover/updated';
                    } else {
                        otpError.textContent = data.message || 'Verification failed.';
                        otpError.hidden = false;
                        btnVerifyOtp.disabled = false;
                    }
                })
                .catch(function () {
                    otpError.textContent = 'Something went wrong. Please try again.';
                    otpError.hidden = false;
                    btnVerifyOtp.disabled = false;
                });
        });
    }
});
