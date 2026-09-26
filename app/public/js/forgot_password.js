// forgot_password.js — drives the 3-step reset wizard in
// app/views/auth/forgot_password.php. Same shape as signup.js:
// 1. Step 1 (email): format check, then POST /forgot-password/send-otp;
//    advances to step 2 only once the server confirms a code was sent. No
//    domain check — the server only ever emails existing accounts.
// 2. Step 2 (OTP): 6-digit boxes, paste support; submit concatenates them
//    and POSTs /forgot-password/verify-otp; advances to step 3 only on a
//    verified match (AuthController::verifyOtp() checks it server-side and
//    flags the email as verified in the session for 5 minutes).
// 3. Step 3 (new password): live match validation, then POST /forgot-password
//    with {email, password} — AuthController::resetPassword() rejects this
//    unless step 2's verification is still valid for this same email.
// Feedback goes through the system toast (window.ttToast, js/instructor/common.js).
document.addEventListener('DOMContentLoaded', function() {

    // Toast helper — long server messages stay up a little longer.
    function notify(message, isError) {
        window.ttToast(message, {
            type: isError ? 'error' : undefined,
            icon: isError ? 'fa-circle-exclamation' : 'fa-circle-check',
            duration: message.length > 80 ? 6000 : 4000
        });
    }

    // UI Elements
    const step1Content = document.getElementById('step-1-content');
    const step2Content = document.getElementById('step-2-content');
    const step3Content = document.getElementById('step-3-content');

    // Dark tracker (brand panel) + light tracker (tablet/phone) — components/auth_stepper.php
    const trackers = document.querySelectorAll('.progress-tracker');

    const emailForm = document.getElementById('emailForm');
    const otpForm = document.getElementById('otpForm');
    const passwordForm = document.getElementById('passwordForm');

    const btnBackToStep1 = document.getElementById('btn-back-to-step1');
    const btnBackToStep2 = document.getElementById('btn-back-to-step2');

    // --- Timeline UI Updater ---
    // Steps before the current one are completed (green), the current one is active.
    function updateProgressUI(currentStep) {
        trackers.forEach(tracker => {
            tracker.querySelectorAll('.step').forEach((step, index) => {
                step.classList.toggle('completed', index + 1 < currentStep);
                step.classList.toggle('active', index + 1 === currentStep);
            });
        });
    }

    // --- STEP 1: Real-time Email Validation ---
    const emailInput = document.getElementById('reset-email');
    const btnSendOtp = document.getElementById('btn-send-otp');

    if (emailInput && btnSendOtp) {
        emailInput.addEventListener('input', function() {
            const emailValue = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailRegex.test(emailValue)) {
                btnSendOtp.classList.add('active-btn'); 
            } else {
                btnSendOtp.classList.remove('active-btn'); 
            }
        });
    }

    // --- STEP 1 to 2 Submit ---
    function requestOtp(email) {
        return fetch('/forgot-password/send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        }).then(response => response.json());
    }

    if (emailForm) {
        emailForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const emailValue = emailInput.value.trim();

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) return;

            const originalText = btnSendOtp.innerHTML;
            btnSendOtp.innerHTML = 'Sending...';
            btnSendOtp.disabled = true;

            requestOtp(emailValue)
                .then(data => {
                    btnSendOtp.innerHTML = originalText;
                    btnSendOtp.disabled = false;

                    if (!data.success) {
                        notify(data.message || 'Could not send the code. Please try again.', true);
                        return;
                    }

                    notify(data.message || 'If this email is registered, a code has been sent.');
                    step1Content.style.display = 'none';
                    step2Content.style.display = 'block';
                    updateProgressUI(2);
                    document.querySelector('.otp-input').focus();
                })
                .catch(error => {
                    console.error('Error:', error);
                    btnSendOtp.innerHTML = originalText;
                    btnSendOtp.disabled = false;
                    notify('Could not reach the server. Please try again.', true);
                });
        });
    }

    // --- STEP 2: Strict OTP Input Logic & Paste ---
    const otpInputs = document.querySelectorAll('.otp-input');
    const btnVerifyOtp = document.getElementById('btn-verify-otp');
    
    function checkOtpValidity() {
        const isComplete = Array.from(otpInputs).every(input => input.value !== '');
        if (isComplete) {
            btnVerifyOtp.classList.add('active-btn'); 
        } else {
            btnVerifyOtp.classList.remove('active-btn'); 
        }
    }

    otpInputs.forEach((input, index) => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
            } else if (e.key === 'Backspace') {
                if (input.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
                setTimeout(checkOtpValidity, 10); 
            }
        });

        input.addEventListener('input', (e) => {
            input.value = input.value.replace(/[^0-9]/g, ''); 
            if (input.value !== '') {
                if (index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            }
            checkOtpValidity();
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault(); 
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').split('');
            
            if (pastedData.length > 0) {
                for (let i = 0; i < pastedData.length; i++) {
                    if (index + i < otpInputs.length) {
                        otpInputs[index + i].value = pastedData[i];
                    }
                }
                const nextEmptyIndex = Math.min(index + pastedData.length, otpInputs.length - 1);
                otpInputs[nextEmptyIndex].focus();
                checkOtpValidity(); 
            }
        });
    });

    // --- STEP 2 to 3 Submit ---
    if (otpForm) {
        otpForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const otpValue = Array.from(otpInputs).map(input => input.value).join('');
            const emailValue = emailInput.value.trim();

            const originalText = btnVerifyOtp.innerHTML;
            btnVerifyOtp.innerHTML = 'Verifying...';
            btnVerifyOtp.disabled = true;

            fetch('/forgot-password/verify-otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: emailValue, otp: otpValue })
            })
            .then(response => response.json())
            .then(data => {
                btnVerifyOtp.innerHTML = originalText;
                btnVerifyOtp.disabled = false;

                if (!data.success) {
                    notify(data.message || 'Incorrect code. Please try again.', true);
                    otpInputs.forEach(input => input.value = '');
                    otpInputs[0].focus();
                    checkOtpValidity();
                    return;
                }

                step2Content.style.display = 'none';
                step3Content.style.display = 'block';
                updateProgressUI(3);
            })
            .catch(error => {
                console.error('Error:', error);
                btnVerifyOtp.innerHTML = originalText;
                btnVerifyOtp.disabled = false;
                notify('Could not reach the server. Please try again.', true);
            });
        });
    }

    // --- STEP 2: Resend OTP ---
    const resendLink = document.querySelector('.resend-link');
    if (resendLink) {
        resendLink.addEventListener('click', function(event) {
            event.preventDefault();
            const emailValue = emailInput.value.trim();
            requestOtp(emailValue)
                .then(data => {
                    if (data.success) notify('A new code has been sent.');
                    else notify(data.message || 'Could not resend the code.', true);
                })
                .catch(error => {
                    console.error('Error:', error);
                    notify('Could not reach the server. Please try again.', true);
                });
        });
    }

    // --- STEP 3: Password Match Logic ---
    const createPwd = document.getElementById('create-password');
    const confirmPwd = document.getElementById('confirm-password');
    const btnComplete = document.getElementById('btn-complete');

    function checkPasswordValidity() {
        const pwd1 = createPwd.value;
        const pwd2 = confirmPwd.value;

        if (pwd1.length >= 8 && pwd1 === pwd2) {
            btnComplete.classList.add('active-btn'); 
        } else {
            btnComplete.classList.remove('active-btn'); 
        }
    }

    if (createPwd && confirmPwd && btnComplete) {
        createPwd.addEventListener('input', checkPasswordValidity);
        confirmPwd.addEventListener('input', checkPasswordValidity);
    }

    // --- Back Buttons ---
    if (btnBackToStep1) {
        btnBackToStep1.addEventListener('click', function() {
            step2Content.style.display = 'none';
            step1Content.style.display = 'block';
            updateProgressUI(1);
        });
    }

    if (btnBackToStep2) {
        btnBackToStep2.addEventListener('click', function() {
            step3Content.style.display = 'none';
            step2Content.style.display = 'block';
            updateProgressUI(2);
        });
    }

    // --- STEP 3: Reset Password Submit ---
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Stop default form submission

            const finalEmail = emailInput.value.trim();
            const finalPassword = createPwd.value;

            const originalText = btnComplete.innerHTML;
            btnComplete.innerHTML = 'Resetting...';
            btnComplete.disabled = true;

            fetch('/forgot-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: finalEmail,
                    password: finalPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Shown on the login page after the redirect.
                    window.ttToast.flash('Password reset successful. You can now sign in with your new password.', { duration: 5000 });
                    window.location.href = data.redirect || '/login';
                } else {
                    notify(data.message || 'Password reset failed. Please try again.', true);
                    btnComplete.innerHTML = originalText;
                    btnComplete.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notify('Could not reach the server. Please try again.', true);
                btnComplete.innerHTML = originalText;
                btnComplete.disabled = false;
            });
        });
    }
});
