    <!-- auth/forgot_password.php — 3-step password-reset wizard. Rendered by
         AuthController::forgotPasswordView() inside the 'auth' layout.
         Self-contained styling (see css/forgot_password.css) — does not
         reuse global.css's .left-panel/.right-panel like login/signup do.
         Steps: #step-1-content (email) -> #step-2-content (OTP) ->
         #step-3-content (new password). JS: /js/forgot_password.js. -->
    <main class="split-layout-container">

        <aside class="left-panel dark-theme">
            <div class="top-section">
                <div class="branding">
                    <i class="fa-solid fa-graduation-cap logo-icon"></i>
                    <div class="logo-text">
                        <h1>StaffSync</h1>
                        <p>University of Colombo</p>
                    </div>
                </div>

                <div class="feature-content">
                    <h2>Reset Your Password</h2>
                    <p>Securely recover access to your StaffSync account in three quick steps.</p>
                </div>

                <div class="progress-section">
                    <h3 class="progress-label">STEPS TO COMPLETE</h3>
                    
                    <div class="progress-tracker horizontal">
                        <div class="step active" id="step-1-indicator">
                            <div class="step-circle">
                                <span class="step-num">1</span>
                                <i class="fa-solid fa-check step-check" style="display: none;"></i>
                            </div>
                            <span class="step-text">Enter email</span>
                        </div>

                        <div class="step" id="step-2-indicator">
                            <div class="step-circle">
                                <span class="step-num">2</span>
                                <i class="fa-solid fa-check step-check" style="display: none;"></i>
                            </div>
                            <span class="step-text">Verify OTP</span>
                        </div>

                        <div class="step" id="step-3-indicator">
                            <div class="step-circle">
                                <span class="step-num">3</span>
                                <i class="fa-solid fa-check step-check" style="display: none;"></i>
                            </div>
                            <span class="step-text">New password</span>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="left-footer">
                <p>&copy; <?= date('Y') ?> University of Colombo. All rights reserved.</p>
            </footer>
        </aside>

        <section class="right-panel">
            <nav class="top-nav">
                <span>Remember your password?</span> <a href="/login">Sign in</a>
            </nav>
            
            <div class="form-container">
                
                <div class="form-step" id="step-1-content">
                    <div class="step-badge">
                        <span class="badge-text">Step 1 of 3</span>
                        <span class="badge-divider">—</span>
                        <span class="badge-desc">Enter email</span>
                    </div>
                
                    <div class="welcome-text">
                        <h2>Forgot your password?</h2>
                        <p>Enter your staff email and we'll send a 6-digit reset code.</p>
                    </div>
                
                    <div class="login-card">
                        <form id="emailForm" action="/forgot-password/send-otp" method="POST">
                            <div class="form-group">
                                <label for="reset-email">Staff Email Address</label>
                                <input type="email" id="reset-email" name="reset-email" required placeholder="you@ucsc.cmb.ac.lk">
                            </div>
                            <button type="submit" class="btn-primary" id="btn-send-otp">Send OTP</button>
                            
                            <a href="/login" class="btn-back-link">
                                <i class="fa-solid fa-arrow-left"></i> Back to Login
                            </a>
                        </form>
                    </div>
                </div>
                
                <div class="form-step" id="step-2-content" style="display: none;">
                    <div class="step-badge">
                        <span class="badge-text">Step 2 of 3</span>
                        <span class="badge-divider">—</span>
                        <span class="badge-desc">Verify OTP</span>
                    </div>
                
                    <div class="welcome-text">
                        <h2>Check your email inbox</h2>
                        <p>Enter the 6-digit code sent to your email.</p>
                    </div>
                
                    <div class="login-card">
                        <form id="otpForm" action="/forgot-password/verify-otp" method="POST">
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

                <div class="form-step" id="step-3-content" style="display: none;">
                    <div class="step-badge">
                        <span class="badge-text">Step 3 of 3</span>
                        <span class="badge-divider">—</span>
                        <span class="badge-desc">New password</span>
                    </div>
                
                    <div class="welcome-text">
                        <h2>Set a new password</h2>
                        <p>Choose a strong password. You can also update your passkey.</p>
                    </div>
                
                    <div class="login-card">
                        <form id="passwordForm" action="/forgot-password" method="POST">
                            <div class="form-group">
                                <label for="create-password">New Password</label>
                                <input type="password" id="create-password" name="create-password" required placeholder="Min. 8 characters">
                            </div>

                            <div class="form-group">
                                <label for="confirm-password">Confirm New Password</label>
                                <input type="password" id="confirm-password" name="confirm-password" required placeholder="Repeat password">
                            </div>

                            <div class="passkey-update-box">
                                <div class="passkey-info">
                                    <i class="fa-solid fa-fingerprint"></i>
                                    <div>
                                        <strong>Update Passkey</strong>
                                        <span>Sync passkey with new password</span>
                                    </div>
                                </div>
                                <button type="button" class="btn-secondary">Update</button>
                            </div>
                        
                            <button type="submit" class="btn-primary" id="btn-complete">Reset Password</button>

                            <button type="button" class="btn-back" id="btn-back-to-step2">
                                <i class="fa-solid fa-arrow-left"></i> Back
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <footer class="right-footer">
                <p>&copy; <?= date('Y') ?> University of Colombo. All rights reserved.</p>
                <p>v2.4.1</p>
            </footer>    
        </section>
    </main>

    <script src="/js/forgot_password.js"></script>
