<main class="login-layout-container">
    
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
                <h2>Staff Management<br>Made Effortless</h2>
            </div>
        
            <p class="feature-description">
                The central hub for timetable officers, coordinators, department heads, and academic staff to manage schedules, resources, and institutional activities efficiently.
            </p>
        
            <ul class="feature-list">
                <li><i class="fa-solid fa-calendar-days"></i> Automated timetable scheduling</li>
                <li><i class="fa-solid fa-book"></i> Centralised course catalogue</li>
                <li><i class="fa-solid fa-users"></i> Lecturer & resource management</li>
                <li><i class="fa-solid fa-bell"></i> Real-time conflict notifications</li>
            </ul>
        </div>
    </aside>

    <section class="right-panel">
        <nav class="top-nav">
            <span>New to UCSC?</span> <a href="/signup">Create account</a>
        </nav>

        <div class="login-main">
            <div class="welcome-text">
                <h2>Welcome back</h2>
                <p>Sign in with your staff credentials to continue.</p>
            </div>
        
            <div class="login-card">
                <form id="loginForm" action="/login" method="POST">

                    <div class="form-group">
                        <label for="username">Staff Email Address</label>
                        <input type="email" id="username" name="username" required placeholder="you@ucsc.cmb.ac.lk">
                    </div>
                
                    <div class="form-group">
                        <div class="label-wrapper">
                            <label for="password">Password</label>
                            <a href="/forgot-password" class="forgot-password">Forgot password?</a>
                        </div>
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                    </div>
                
                    <button type="submit" class="btn-primary">Sign In</button>
                </form>
            
                <div class="divider-wrapper">
                    <span class="divider-text">or</span>
                </div>
            
                <button class="btn-passkey">
                    <i class="fa-solid fa-key"></i>
                    Sign in with Passkey
                </button>
            </div>
        
            <p class="agreement-text">
                By signing in you agree to the University's <a href="#">Terms of Use</a> and <a href="#">Privacy Policy</a>.
            </p>
        </div>

        <footer class="right-footer">
            <p>&copy; <?= date('Y') ?> University of Colombo School of Computing.</p>
        </footer>
    </section>

</main>
<script src="/js/login.js"></script>
