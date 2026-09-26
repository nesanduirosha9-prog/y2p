<!-- auth/signup.php — 3-step signup wizard. Rendered by
     AuthController::signupView() inside the 'auth' layout.
     1. Left panel  — branding + 3-step progress tracker (email/OTP/password).
     2. Right panel — three step-content blocks, only one visible at a time:
        #step-1-content (email) -> #step-2-content (OTP) -> #step-3-content (password).
     Layout skeleton shared with login/forgot password — see css/global.css.
     JS: /js/signup.js switches steps and calls POST /signup/send-otp,
     /signup/verify-otp, then /signup. -->
<main class="login-layout-container">

    <!-- ==================== LEFT PANEL ==================== -->
    <aside class="left-panel">
        <div class="branding">
            <i class="fa-solid fa-graduation-cap logo-icon"></i>
            <div class="logo-text">
                <h1>StaffSync</h1>
                <p>University of Colombo</p>
            </div>
        </div>

        <div class="left-content">
            <div class="feature-title">
                <h2>Create Your Staff Account</h2>
            </div>

            <p class="feature-description">
                Join the StaffSync portal to manage timetables, courses, and academic resources for your department.
            </p>

            <div class="progress-section">
                <h3 class="progress-label">Steps to complete</h3>
                <?php
                $stepLabels = ['Enter email', 'Verify OTP', 'Set password'];
                $stepperClass = '';
                require \app\core\Application::$ROOT_DIR . '/views/components/auth_stepper.php';
                ?>
            </div>
        </div>

        <footer class="left-footer">
            <p>New accounts are reviewed by a coordinator before first sign-in.</p>
        </footer>
    </aside>

    <!-- ==================== RIGHT PANEL ==================== -->
    <section class="right-panel">
        <header class="auth-header">
            <a href="/login" class="branding branding--compact" aria-label="StaffSync home">
                <i class="fa-solid fa-graduation-cap logo-icon"></i>
                <span class="logo-text">
                    <span class="logo-name">StaffSync</span>
                    <span class="logo-sub">University of Colombo</span>
                </span>
            </a>
            <nav class="top-nav">
                <span>Already have an account?</span> <a href="/login">Sign in</a>
            </nav>
        </header>

        <div class="auth-main">
            <div class="auth-flow">

                <!-- Light copy of the tracker; only shown when the brand panel is hidden -->
                <div class="mobile-stepper">
                    <?php
                    $stepperClass = 'progress-tracker--light';
                    require \app\core\Application::$ROOT_DIR . '/views/components/auth_stepper.php';
                    ?>
                </div>

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
                        <form id="emailForm" action="/signup/send-otp" method="POST" novalidate>
                            <div class="form-group">
                                <label for="signup-email">Staff Email Address</label>
                                <input type="email" id="signup-email" name="signup-email" required autocomplete="email" placeholder="you@ucsc.cmb.ac.lk">
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
                        <form id="otpForm" action="/signup/verify-otp" method="POST" novalidate>
                            <div class="form-group">
                                <label>6-digit verification code</label>
                                <div class="otp-input-group">
                                    <input type="text" maxlength="1" inputmode="numeric" class="otp-input" required>
                                    <input type="text" maxlength="1" inputmode="numeric" class="otp-input" required>
                                    <input type="text" maxlength="1" inputmode="numeric" class="otp-input" required>
                                    <input type="text" maxlength="1" inputmode="numeric" class="otp-input" required>
                                    <input type="text" maxlength="1" inputmode="numeric" class="otp-input" required>
                                    <input type="text" maxlength="1" inputmode="numeric" class="otp-input" required>
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
                        <form id="passwordForm" action="/signup/complete" method="POST" novalidate>
                            <div class="form-group">
                                <label for="create-password">Create Password</label>
                                <input type="password" id="create-password" name="create-password" required autocomplete="new-password" placeholder="Min. 8 characters">
                            </div>

                            <div class="form-group">
                                <label for="confirm-password">Confirm Password</label>
                                <input type="password" id="confirm-password" name="confirm-password" required autocomplete="new-password" placeholder="Repeat password">
                            </div>

                            <div class="passkey-box">
                                <div class="passkey-info">
                                    <i class="fa-solid fa-fingerprint"></i>
                                    <div>
                                        <strong>Register Passkey</strong>
                                        <span>Face ID • Touch ID • Device PIN</span>
                                    </div>
                                </div>
                                <label class="switch" aria-label="Register a passkey">
                                    <input type="checkbox" id="enable-passkey">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <button type="submit" class="btn-primary" id="btn-complete">Complete Registration</button>

                            <a href="/login" class="btn-back-link" id="btn-back-to-login">
                                <i class="fa-solid fa-arrow-left"></i> Back to Login
                            </a>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        <footer class="right-footer">
            <p>&copy; <?= date('Y') ?> University of Colombo. All rights reserved.</p>
            <p>v2.4.1</p>
        </footer>
    </section>

</main>

<script src="/js/signup.js"></script>
