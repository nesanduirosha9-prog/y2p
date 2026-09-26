// login.js — wires up app/views/auth/login.php's single-step sign-in form.
// 1. On submit: validate email/password aren't empty. No domain check here —
//    only accounts that got past signup's server-side check exist to log in
//    to, and that check also admits config.php's AUTH_BYPASS_EMAILS.
// 2. POST the credentials to /login as JSON (AuthController::login()).
// 3. On success, redirect to the URL the server returns; on failure, show
//    the error and re-enable the button.
document.addEventListener('DOMContentLoaded', function() {

    // Grab the login form element using its ID
    const loginForm = document.getElementById('loginForm');

    // Listen for the user clicking the submit button
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            
            // Prevent the form from refreshing the page (which is the default action)
            // We want to validate it ourselves first!
            event.preventDefault();

            // Grab the values the user typed into the inputs
            const emailInput = document.getElementById('username').value.trim();
            const passwordInput = document.getElementById('password').value.trim();

            // 1. Basic Validation: Check if fields are empty
            if (emailInput === '' || passwordInput === '') {
                alert('Please fill in both your email and password.');
                return; // Stop the function here
            }

            // 2. Send the login request to the backend
            const btnSubmit = loginForm.querySelector('button[type="submit"]');
            const originalText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = 'Logging in...';
            btnSubmit.disabled = true;

            fetch('/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: emailInput,
                    password: passwordInput
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Login successful! Redirecting to dashboard...');
                    window.location.href = data.redirect || '/dashboard';
                } else {
                    alert('Login failed: ' + data.message);
                    btnSubmit.innerHTML = originalText;
                    btnSubmit.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
            });
        });
    }
});
