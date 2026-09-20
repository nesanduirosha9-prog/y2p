// Wait for the HTML to fully load before running anything
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Grab the Content Containers (The 3 forms on the right)
    const step1Content = document.getElementById('step-1-content');
    const step2Content = document.getElementById('step-2-content');
    const step3Content = document.getElementById('step-3-content');

    // 2. Grab the Progress Indicators (The 1-2-3 circles on the left)
    const step1Indicator = document.getElementById('step-1-indicator');
    const step2Indicator = document.getElementById('step-2-indicator');
    const step3Indicator = document.getElementById('step-3-indicator');

    // 3. Grab the Forms (So we can stop them from refreshing the page)
    const emailForm = document.getElementById('emailForm');
    const otpForm = document.getElementById('otpForm');
    const passwordForm = document.getElementById('passwordForm');

    // 4. Grab the Back Buttons
    const btnBackToStep1 = document.getElementById('btn-back-to-step1');
    const btnBackToLogin = document.getElementById('btn-back-to-login');

    // Just a quick check to make sure our script is connected
    console.log("Signup JS is successfully connected!");

    // --- NEW: Real-time Email Validation (Lighting up the button) ---
    const emailInput = document.getElementById('signup-email');
    const btnSendOtp = document.getElementById('btn-send-otp');

    if (emailInput && btnSendOtp) {
        // The 'input' event fires every single time a key is pressed or deleted
        emailInput.addEventListener('input', function() {
            const emailValue = this.value.trim();
            
            // Regex to check if it looks like a real email (text@text.text)
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            // Check if it is a valid email AND ends specifically with the UCSC domain
            if (emailRegex.test(emailValue) && emailValue.endsWith('@ucsc.cmb.ac.lk')) {
                // IT'S VALID! Add the class to trigger your CSS brightness and allow clicking
                btnSendOtp.classList.add('active-btn'); 
            } else {
                // NOT VALID YET! Remove the class to keep it grey and unclickable
                btnSendOtp.classList.remove('active-btn'); 
            }
        });
    }

    // --- Helper Function to update the Left Panel Progress ---
    function updateProgressUI(currentStep) {
        // First, reset all steps to their default inactive state
        step1Indicator.className = 'step';
        step2Indicator.className = 'step';
        step3Indicator.className = 'step';

        // Now, apply the correct classes based on what step we are on
        if (currentStep === 1) {
            step1Indicator.classList.add('active');
        } 
        else if (currentStep === 2) {
            step1Indicator.classList.add('completed');
            step2Indicator.classList.add('active');
        } 
        else if (currentStep === 3) {
            step1Indicator.classList.add('completed');
            step2Indicator.classList.add('completed');
            step3Indicator.classList.add('active');
        }
    }

    // --- Step 1 to Step 2 (Send OTP) ---
    if (emailForm) {
        emailForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Stop the page from reloading

            const emailValue = emailInput.value.trim();

            // 1. Basic Format Validation using Regex
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(emailValue)) {
                alert('Please enter a valid email address format.');
                return; // Stop the function completely
            }

            // 2. Domain Specific Validation (Highly recommended for this project)
            if (!emailValue.endsWith('@ucsc.cmb.ac.lk')) {
                alert('Registration restricted: Please use your official @ucsc.cmb.ac.lk staff email.');
                return; // Stop the function completely
            }

            // 3. If it passes both checks, proceed to Step 2
            step1Content.style.display = 'none';
            step2Content.style.display = 'block';
            updateProgressUI(2);
            
            // Automatically focus the first OTP input box!
            document.querySelector('.otp-input').focus();
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

            // 1. Hide Step 2 right panel
            step2Content.style.display = 'none';
            
            // 2. Show Step 3 right panel
            step3Content.style.display = 'block';
            
            // 3. Update left panel timeline to Step 3
            updateProgressUI(3);
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
                    alert("Registration complete! You can now sign in.");
                    window.location.href = data.redirect || '/login';
                } else {
                    alert("Registration failed: " + data.message);
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
