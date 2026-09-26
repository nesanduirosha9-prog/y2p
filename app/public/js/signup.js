// signup.js — drives the 3-step signup wizard in app/views/auth/signup.php.
// Same shape as forgot_password.js:
// 1. Step 1 (email): format check, then POST /signup/send-otp; advances only
//    once the server confirms a code was sent. Which emails may register
//    (the staff domain, plus any AUTH_BYPASS_EMAILS) is decided server-side.
// 2. Step 2 (OTP): 6 auto-advancing digit boxes with paste support; submit
//    POSTs /signup/verify-otp and advances only on a verified match.
// 3. Step 3 (password): live length/match validation, then POST /signup with
//    {email, password} — rejected unless step 2 verified this same email.
// Feedback goes through the system toast (window.ttToast, js/toast.js).
// Wait for the HTML to fully load before running anything
document.addEventListener('DOMContentLoaded', function() {

    const notify = (message, isError) => isError ? ttToast.error(message) : ttToast(message);

    // 1. Grab the Content Containers (The 3 forms on the right)
    const step1Content = document.getElementById('step-1-content');
    const step2Content = document.getElementById('step-2-content');
    const step3Content = document.getElementById('step-3-content');

    // 2. Grab the Progress Trackers — the dark one in the brand panel and the
    //    light one above the form on tablet/phone (components/auth_stepper.php)
    const trackers = document.querySelectorAll('.progress-tracker');

    // 3. Grab the Forms (So we can stop them from refreshing the page)
    const emailForm = document.getElementById('emailForm');
    const otpForm = document.getElementById('otpForm');
    const passwordForm = document.getElementById('passwordForm');

    // 4. Grab the Back Button (step 3's "Back to Login" is a plain link)
    const btnBackToStep1 = document.getElementById('btn-back-to-step1');

    // --- NEW: Real-time Email Validation (Lighting up the button) ---
    const emailInput = document.getElementById('signup-email');
    const btnSendOtp = document.getElementById('btn-send-otp');

    if (emailInput && btnSendOtp) {
        // The 'input' event fires every single time a key is pressed or deleted
        emailInput.addEventListener('input', function() {
            const emailValue = this.value.trim();
            
            // Regex to check if it looks like a real email (text@text.text)
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (emailRegex.test(emailValue)) {
                // IT'S VALID! Add the class to trigger your CSS brightness and allow clicking
                btnSendOtp.classList.add('active-btn'); 
            } else {
                // NOT VALID YET! Remove the class to keep it grey and unclickable
                btnSendOtp.classList.remove('active-btn'); 
            }
        });
    }

    // --- Helper Function to update every progress tracker ---
    // Steps before the current one are completed, the current one is active.
    function updateProgressUI(currentStep) {
        trackers.forEach(tracker => {
            tracker.querySelectorAll('.step').forEach((step, index) => {
                step.classList.toggle('completed', index + 1 < currentStep);
                step.classList.toggle('active', index + 1 === currentStep);
            });
        });
    }

    // --- Step 1 to Step 2 (Send OTP) ---
    function requestOtp(email) {
        return fetch('/signup/send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        }).then(response => response.json());
    }

    if (emailForm) {
        emailForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Stop the page from reloading

            const emailValue = emailInput.value.trim();

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailValue)) {
                notify('Please enter a valid email address.', true);
                return;
            }

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

                    notify(data.message || 'A verification code has been sent to your email.');
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

    // --- STRICT Smart OTP Input Logic & Copy/Paste Support ---
    const otpInputs = document.querySelectorAll('.otp-input');
    const btnVerifyOtp = document.getElementById('btn-verify-otp');
    
    // Helper function: Checks if all 6 boxes have a number
    function checkOtpValidity() {
        const isComplete = Array.from(otpInputs).every(input => input.value !== '');
        
        if (isComplete) {
            btnVerifyOtp.classList.add('active-btn'); // Light up the button!
        } else {
            btnVerifyOtp.classList.remove('active-btn'); // Keep it greyed out
        }
    }

    otpInputs.forEach((input, index) => {
        
        // 1. Handle Backspace jumping
        input.addEventListener('keydown', (e) => {

            // BLOCK TAB KEY
            if (e.key === 'Tab') {
                e.preventDefault(); // Stops the browser from moving to the next box
            }

            else if (e.key === 'Backspace') {
                if (input.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
                // Check validity after a tiny delay to let the backspace clear the input
                setTimeout(checkOtpValidity, 10); 
            }
        });

        // 2. Handle standard typing and stripping non-numbers
        input.addEventListener('input', (e) => {
            input.value = input.value.replace(/[^0-9]/g, ''); // Aggressive strip
            
            if (input.value !== '') {
                // Jump to the next box
                if (index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            }
            checkOtpValidity();
        });

        // 3. Handle Copy / Paste across the 6 boxes
        input.addEventListener('paste', (e) => {
            e.preventDefault(); // Stop the browser from dumping everything into one box
            
            // Grab the pasted text, strip non-digits, and split into an array of single numbers
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').split('');
            
            if (pastedData.length > 0) {
                // Distribute the numbers across the boxes, starting from where the user clicked
                for (let i = 0; i < pastedData.length; i++) {
                    if (index + i < otpInputs.length) {
                        otpInputs[index + i].value = pastedData[i];
                    }
                }
                
                // Move the cursor to the end of the pasted string (or the last box)
                const nextEmptyIndex = Math.min(index + pastedData.length, otpInputs.length - 1);
                otpInputs[nextEmptyIndex].focus();
                
                checkOtpValidity(); // Check if the paste filled all boxes!
            }
        });
    });

    // --- Step 2 to Step 3 (Verify OTP) ---
    if (otpForm) {
        otpForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const otpValue = Array.from(otpInputs).map(input => input.value).join('');
            const emailValue = emailInput.value.trim();

            const originalText = btnVerifyOtp.innerHTML;
            btnVerifyOtp.innerHTML = 'Verifying...';
            btnVerifyOtp.disabled = true;

            fetch('/signup/verify-otp', {
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

    // --- Step 2: Resend OTP ---
    const resendLink = document.querySelector('.resend-link');
    if (resendLink) {
        resendLink.addEventListener('click', function(event) {
            event.preventDefault();
            requestOtp(emailInput.value.trim())
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

    // --- Back Button: Step 2 back to Step 1 ---
    if (btnBackToStep1) {
        btnBackToStep1.addEventListener('click', function() {
            // Hide Step 2, Show Step 1
            step2Content.style.display = 'none';
            step1Content.style.display = 'block';
            
            // Update the timeline back to Step 1
            updateProgressUI(1);
        });
    }

    // --- Real-time Password Validation (Step 3) ---
    const createPwd = document.getElementById('create-password');
    const confirmPwd = document.getElementById('confirm-password');
    const btnComplete = document.getElementById('btn-complete');

    // Helper function to check the passwords
    function checkPasswordValidity() {
        const pwd1 = createPwd.value;
        const pwd2 = confirmPwd.value;

        // Check if length is >= 8 AND they exactly match
        if (pwd1.length >= 8 && pwd1 === pwd2) {
            btnComplete.classList.add('active-btn'); // Light it up!
        } else {
            btnComplete.classList.remove('active-btn'); // Grey it out
        }
    }

    // Listen to both inputs so it checks while the user types
    if (createPwd && confirmPwd && btnComplete) {
        createPwd.addEventListener('input', checkPasswordValidity);
        confirmPwd.addEventListener('input', checkPasswordValidity);
    }

    // --- STEP 3: Complete Registration Submit ---
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Stop default form submission

            const finalEmail = emailInput.value.trim();
            const finalPassword = createPwd.value;

            // Send the registration request to the backend
            const originalText = btnComplete.innerHTML;
            btnComplete.innerHTML = 'Registering...';
            btnComplete.disabled = true;

            fetch('/signup', {
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
                    window.ttToast.flash(data.message || 'Registration submitted!', { duration: 6000 });
                    window.location.href = data.redirect || '/login';
                } else {
                    notify(data.message || 'Registration failed. Please try again.', true);
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
