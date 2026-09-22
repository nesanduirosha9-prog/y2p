<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\StaffModel;
use app\models\OtpCodeModel;
use app\services\EmailService;

// AuthController: login / signup / forgot-password.
// 1. *View methods (loginView, signupView, forgotPasswordView) — GET, render
//    the page. Login/signup bounce an already-logged-in user to their
//    dashboard instead of showing the form again.
// 2. login() — POST /login. Verifies credentials, blocks `pending` accounts,
//    regenerates the session id, then writes the session and returns a
//    redirect URL by role.
// 3. signup() — POST /signup. Creates a `pending` staff row (email+password
//    only); a Coordinator assigns the role later from the Staff screen.
// 4. sendOtp() — POST /forgot-password/send-otp. Emails a 6-digit code via
//    EmailService, rate-limited per email (60s cooldown, 5/hour cap). Always
//    returns the same generic response whether or not the email exists, so
//    the endpoint can't be used to enumerate accounts.
// 5. verifyOtp() — POST /forgot-password/verify-otp. Checks the code against
//    otp_codes, then marks the email verified in the session for 5 minutes.
// 6. resetPassword() — POST /forgot-password. Overwrites the password, but
//    only if verifyOtp() marked this email verified within the last 5 min.
// 7. logout() — destroys the session and redirects to /login.
// 8. dashboardUrlForRole() — the role->URL mapping shared by loginView(),
//    signupView(), login(), and the `/` route in index.php.
// 9. jsonResponse() — private helper every action above returns through.
class AuthController extends Controller
{
    public function __construct()
    {
        // All views rendered by this controller will use the 'auth' layout
        $this->setLayout('auth');
    }

    // --- GET handlers to render views ---

    public function loginView()
    {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['staff_code'])) {
            $this->redirect($this->dashboardUrlForRole($_SESSION['role']));
            return;
        }
        return $this->render('auth/login', ['title' => 'Login', 'css_file' => '/css/login.css']);
    }

    public function signupView()
    {
        if (isset($_SESSION['staff_code'])) {
            $this->redirect($this->dashboardUrlForRole($_SESSION['role']));
            return;
        }
        return $this->render('auth/signup', ['title' => 'Sign Up', 'css_file' => '/css/signup.css']);
    }

    public function forgotPasswordView()
    {
        return $this->render('auth/forgot_password', ['title' => 'Forgot Password', 'css_file' => '/css/forgot_password.css']);
    }

    // --- POST handlers for logic ---

    public function login(Request $request, Response $response)
    {
        // 1. Read credentials ('username' is the HTML input's name, not the
        //    field's actual meaning — it's always an email here).
        $body = $request->getBody();
        $email = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        // 2. Look up the staff row and verify the hashed password.
        $staffModel = new StaffModel();
        $user = $staffModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Invalid email or password'], 401);
        }

        // 3. Block sign-in until a Coordinator/In-Charge assigns a role.
        if (($user['status'] ?? 'active') === 'pending') {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Your account is awaiting approval from a coordinator. You will be able to sign in once it is approved.',
            ], 403);
        }

        // 4. Regenerate the session id before writing any session state —
        //    prevents session fixation (an id issued to an anonymous visitor
        //    being reused, now authenticated, if it was ever exposed/guessed).
        session_regenerate_id(true);

        // 5. Start the session — every dashboard controller reads these keys.
        $_SESSION['staff_code'] = $user['code'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['academic_rank'] = $user['academic_rank'];
        $_SESSION['position'] = $user['position'];

        // 6. Tell the client where to go next (JS does the redirect).
        return $this->jsonResponse($response, ['success' => true, 'message' => 'Login successful', 'redirect' => $this->dashboardUrlForRole($user['role'])]);
    }

    public function signup(Request $request, Response $response)
    {
        // 1. The signup form only collects email/password — name/phone are
        //    filled in later from Settings (see StaffModel::create()).
        $body = $request->getBody();
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Email and password are required'], 400);
        }

        $staffModel = new StaffModel();

        // 2. One email = one account; reject duplicates up front.
        if ($staffModel->findByEmail($email)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Email is already registered'], 409);
        }

        // 3. Create a pending account — a Coordinator/In-Charge assigns the
        //    role via the Staff approval screen before this account can log in.
        if ($staffModel->create($email, $password)) {
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Registration submitted! A coordinator will review your account before you can sign in.',
                'redirect' => '/login',
            ]);
        }

        return $this->jsonResponse($response, ['success' => false, 'message' => 'Registration failed due to a server error'], 500);
    }

    // POST /forgot-password/send-otp — step 1 of the reset wizard. Emails a
    // 6-digit code and stores its hash in otp_codes. Rate-limited per email:
    // one request per 60s, five per hour. Always returns the same generic
    // response whether or not the email is registered, and never creates or
    // sends a code for one that isn't — a wrong guess never gets a code, and
    // the response alone can't be used to enumerate accounts.
    public function sendOtp(Request $request, Response $response)
    {
        $body = $request->getBody();
        $email = trim($body['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Enter a valid email address'], 400);
        }

        if (defined('DEMO_AUTH') && DEMO_AUTH) {
            return $this->jsonResponse($response, ['success' => true, 'message' => 'If this email is registered, a code has been sent.']);
        }

        $otpModel = new OtpCodeModel();
        if ($otpModel->countRequestsSince($email, 'password_reset', 60) > 0) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Please wait a minute before requesting another code.'], 429);
        }
        if ($otpModel->countRequestsSince($email, 'password_reset', 3600) >= 5) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
        }

        $generic = ['success' => true, 'message' => 'If this email is registered, a code has been sent.'];

        $user = (new StaffModel())->findByEmail($email);
        if (!$user) {
            return $this->jsonResponse($response, $generic);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if (!EmailService::sendOtpEmail($email, $otp, 'password_reset')) {
            // Don't store a code that was never actually delivered.
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Could not send the code. Please try again.'], 500);
        }
        $otpModel->create($email, 'password_reset', $otp);

        return $this->jsonResponse($response, $generic);
    }

    // POST /forgot-password/verify-otp — step 2. Checks the submitted code
    // against the latest active otp_codes row for this email; on a match,
    // marks it consumed and flags the email as verified in the session for
    // 5 minutes so resetPassword() can trust it.
    public function verifyOtp(Request $request, Response $response)
    {
        $body = $request->getBody();
        $email = trim($body['email'] ?? '');
        $otp = trim($body['otp'] ?? '');

        if (!preg_match('/^\d{6}$/', $otp)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Enter the 6-digit code'], 400);
        }

        if (defined('DEMO_AUTH') && DEMO_AUTH) {
            $_SESSION['password_reset_verified'] = ['email' => $email, 'until' => time() + 300];
            return $this->jsonResponse($response, ['success' => true]);
        }

        $otpModel = new OtpCodeModel();
        $row = $otpModel->findLatestActive($email, 'password_reset');
        if (!$row) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Invalid or expired code'], 400);
        }
        if (!password_verify($otp, $row['otp_hash'])) {
            $otpModel->decrementAttempts($row['id']);
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Incorrect code'], 400);
        }

        $otpModel->markConsumed($row['id']);
        $_SESSION['password_reset_verified'] = ['email' => $email, 'until' => time() + 300];
        return $this->jsonResponse($response, ['success' => true]);
    }

    public function resetPassword(Request $request, Response $response)
    {
        // 1. Read the new password + the email it belongs to.
        $body = $request->getBody();
        $email = $body['email'] ?? '';
        $newPassword = $body['password'] ?? '';

        if (empty($email) || empty($newPassword)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Email and new password are required'], 400);
        }

        // 2. Require a recent, matching verifyOtp() success — set by step 2
        //    of the wizard, valid for 5 minutes.
        $verified = $_SESSION['password_reset_verified'] ?? null;
        if (!$verified || ($verified['email'] ?? null) !== $email || ($verified['until'] ?? 0) < time()) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Please verify your email again.'], 403);
        }

        $staffModel = new StaffModel();

        // 3. Ensure the account actually exists before touching it.
        if (!$staffModel->findByEmail($email)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'No account found with this email'], 404);
        }

        // 4. Overwrite the password hash and report the outcome.
        if ($staffModel->updatePassword($email, $newPassword)) {
            unset($_SESSION['password_reset_verified']);
            return $this->jsonResponse($response, ['success' => true, 'message' => 'Password reset successfully', 'redirect' => '/login']);
        }

        return $this->jsonResponse($response, ['success' => false, 'message' => 'Failed to reset password'], 500);
    }

    // Ends the session and sends the browser back to the login page.
    public function logout()
    {
        session_destroy();
        $this->redirect('/login');
    }

    // Where an already-authenticated user's dashboard lives, by role. Public
    // (not private) because the `/` route in index.php calls this from a
    // bare closure with no $this — see app/public/index.php.
    public function dashboardUrlForRole(string $role): string
    {
        return $role === 'academic_staff' ? '/instructor/timetable' : '/timetable';
    }

    // Every action above funnels its JSON reply through here: set the
    // status code, force the content type, and encode the payload.
    private function jsonResponse(Response $response, array $data, int $statusCode = 200)
    {
        $response->setStatusCode($statusCode);
        header('Content-Type: application/json');
        return json_encode($data);
    }
}
