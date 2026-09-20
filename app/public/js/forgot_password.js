document.addEventListener('DOMContentLoaded', function() {
    
    // UI Elements
    const step1Content = document.getElementById('step-1-content');
    const step2Content = document.getElementById('step-2-content');
    const step3Content = document.getElementById('step-3-content');

    const step1Indicator = document.getElementById('step-1-indicator');
    const step2Indicator = document.getElementById('step-2-indicator');
    const step3Indicator = document.getElementById('step-3-indicator');

    const emailForm = document.getElementById('emailForm');
    const otpForm = document.getElementById('otpForm');
    const passwordForm = document.getElementById('passwordForm');

    const btnBackToStep1 = document.getElementById('btn-back-to-step1');
    const btnBackToStep2 = document.getElementById('btn-back-to-step2');

    // --- Timeline UI Updater ---
    function updateProgressUI(currentStep) {
        step1Indicator.className = 'step';
        step2Indicator.className = 'step';
        step3Indicator.className = 'step';

        if (currentStep === 1) {
            step1Indicator.classList.add('active');
        } 
        else if (currentStep === 2) {
            step1Indicator.classList.add('completed'); // Adds green styling
            step2Indicator.classList.add('active');
        } 
        else if (currentStep === 3) {
            step1Indicator.classList.add('completed');
            step2Indicator.classList.add('completed');
            step3Indicator.classList.add('active');
        }
    }

    // --- STEP 1: Real-time Email Validation ---
    const emailInput = document.getElementById('reset-email');
    const btnSendOtp = document.getElementById('btn-send-otp');

    if (emailInput && btnSendOtp) {
        emailInput.addEventListener('input', function() {
            const emailValue = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailRegex.test(emailValue) && emailValue.endsWith('@ucsc.cmb.ac.lk')) {
                btnSendOtp.classList.add('active-btn'); 
            } else {
                btnSendOtp.classList.remove('active-btn'); 
            }
        });
    }

    // --- STEP 1 to 2 Submit ---
    if (emailForm) {
        emailForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const emailValue = emailInput.value.trim();

            if (!emailValue.endsWith('@ucsc.cmb.ac.lk')) return; // Extra check

            step1Content.style.display = 'none';
            step2Content.style.display = 'block';
            updateProgressUI(2);
            document.querySelector('.otp-input').focus();
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
            step2Content.style.display = 'none';
            step3Content.style.display = 'block';
            updateProgressUI(3);
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
                    alert("Password reset successful! You can now sign in with your new password.");
                    window.location.href = data.redirect || '/login';
                } else {
                    alert("Password reset failed: " + data.message);
                    btnComplete.innerHTML = originalText;
                    btnComplete.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("An error occurred. Please try again.");
                btnComplete.innerHTML = originalText;
                btnComplete.disabled = false;
            });
        });
    }
});
