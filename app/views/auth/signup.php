    <!-- ==================== MAIN PAGE LAYOUT ==================== -->
    <main class="login-layout-container">
        
        <!-- ==================== LEFT PANEL ==================== -->
        <aside class="left-panel">
            <div class="top-section">
                
                <div class="branding">
                    <i class="fa-solid fa-graduation-cap logo-icon"></i>
                    <div class="logo-text">
                        <h1>StaffSync</h1>
                        <p>University of Colombo</p>
                    </div>
                </div>

                <div class="feature-title">
                    <h2>Create Your Staff Account</h2>
                </div>

                <p class="feature-description">
                    Join the StaffSync portal to manage timetables, courses, and academic resources for your department.
                </p>

                <div class="progress-section">
                    <h3 class="progress-label">STEPS TO COMPLETE</h3>
                    
                    <div class="progress-tracker">
                        <div class="step active" id="step-1-indicator">
                            <div class="step-circle">1</div>
                            <span class="step-text">Enter email</span>
                        </div>
                        
                        <div class="step-line"></div>
                        
                        <div class="step" id="step-2-indicator">
                            <div class="step-circle">2</div>
                            <span class="step-text">Verify OTP</span>
                        </div>
                        
                        <div class="step-line"></div>
                        
                        <div class="step" id="step-3-indicator">
                            <div class="step-circle">3</div>
                            <span class="step-text">Set password</span>
                        </div>
                    </div>
                </div>

            </div>

            <footer class="left-footer">
                <p>&copy; <?= date('Y') ?> University of Colombo. All rights reserved.</p>
            </footer>
        </aside>

        <!-- ==================== RIGHT PANEL ==================== -->
        <section class="right-panel">
            <nav class="top-nav">
                <span>Already have an account?</span> <a href="/login">Sign in</a>
            </nav>
            
            <!-- ==================== STEP 1: EMAIL ENTRY ==================== -->
            <div class="login-main" id="step-1-content">
                
                <div class="step-badge">
                    <span class="badge-text">Step 1 of 3</span>
                    <span class="badge-divider">—</span>
                    <span class="badge-desc">Enter email</span>
                </div>
            
                <div class="welcome-text">
                    <h2>Enter your staff email</h2>
                    <p>We'll send a one-time code to verify your university email address.</p>
                </div>
            
                <div class="login-card">
                    <form id="emailForm" action="/signup/send-otp" method="POST">

                        <div class="form-group">
                            <label for="signup-email">Staff Email Address</label>
                            <input type="email" id="signup-email" name="signup-email" required placeholder="you@ucsc.cmb.ac.lk">
                        </div>

                        <button type="submit" class="btn-primary" id="btn-send-otp">Send OTP</button>
                    </form>
                </div>
            </div>
            
            <!-- ==================== STEP 2: OTP VERIFICATION ==================== -->
            <div class="login-main" id="step-2-content" style="display: none;">
    
                <div class="step-badge">
                    <span class="badge-text">Step 2 of 3</span>
                    <span class="badge-divider">—</span>
                    <span class="badge-desc">Verify OTP</span>
                </div>
            
                <div class="welcome-text">
                    <h2>Verify your email address</h2>
                    <p>Enter the 6-digit code sent to your email.</p>
                </div>
            
                <div class="login-card">
                    <form id="otpForm" action="/signup/verify-otp" method="POST">

                        <div class="form-group">
                            <label>6-digit verification code</label>
                            <div class="otp-input-group">
                                <input type="text" maxlength="1" class="otp-input" required>
                                <input type="text" maxlength="1" class="otp-input" required>
                                <input type="text" maxlength="1" class="otp-input" required>
                                <input type="text" maxlength="1" class="otp-input" required>
                                <input type="text" maxlength="1" class="otp-input" required>
                                <input type="text" maxlength="1" class="otp-input" required>
                            </div>
                        </div>
                    
                        <div class="resend-wrapper">
                            <span>Didn't receive it?</span> <a href="#" class="resend-link">Resend OTP</a>
                        </div>
                    
                        <button type="submit" class="btn-primary" id="btn-verify-otp">Verify OTP</button>

                        <button type="button" class="btn-back" id="btn-back-to-step1">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </button>
                    </form>
                </div>
            </div>

            <!-- ==================== STEP 3: PASSWORD SETUP ==================== -->
            <div class="login-main" id="step-3-content" style="display: none;">
                
                <div class="step-badge">
                    <span class="badge-text">Step 3 of 3</span>
                    <span class="badge-divider">—</span>
                    <span class="badge-desc">Set password</span>
                </div>
            
                <div class="welcome-text">
                    <h2>Create your password</h2>
                    <p>Set a strong password and optionally enable biometric sign-in.</p>
                </div>
            
                <div class="login-card">
                    <form id="passwordForm" action="/signup/complete" method="POST">
                        
                        <div class="form-group">
                            <label for="create-password">Create Password</label>
                            <input type="password" id="create-password" name="create-password" required placeholder="Min. 8 characters">
                        </div>

                        <div class="form-group">
                            <label for="confirm-password">Confirm Password</label>
                            <input type="password" id="confirm-password" name="confirm-password" required placeholder="Repeat password">
                        </div>

                        <div class="passkey-toggle-box">
                            <div class="passkey-info">
                                <i class="fa-solid fa-fingerprint"></i>
                                <div>
                                    <strong>Register Passkey</strong>
                                    <span>Face ID • Touch ID • Device PIN</span>
                                </div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="enable-passkey">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    
                        <button type="submit" class="btn-primary" id="btn-complete">Complete Registration</button>

                        <button type="button" class="btn-back" id="btn-back-to-login">
                            <i class="fa-solid fa-arrow-left"></i> Back to Login
                        </button>
                    </form>
                </div>
            </div>
            
            <footer class="right-footer">
                <p>&copy; <?= date('Y') ?> University of Colombo. All rights reserved.</p>
                <p>v1.0.0</p>
            </footer>    
        </section>

    </main>
    
    <script src="/js/signup.js"></script>
