<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\core\StaffEmail;
use app\models\StaffModel;
use app\services\OtpService;

// AuthController: login / signup / forgot-password.
// 1. *View methods (loginView, signupView, forgotPasswordView) — GET, render
//    the page. Login/signup bounce an already-logged-in user to their
//    dashboard instead of showing the form again.
// 2. login() — POST /login. Verifies credentials, blocks `pending` accounts,
//    regenerates the session id, then writes the session and returns a
//    redirect URL by role.
// 3. sendSignupOtp() — POST /signup/send-otp. Emails a code to an address
//    that's allowed to register (STAFF_EMAIL_DOMAIN or AUTH_BYPASS_EMAILS)
//    and isn't already taken. Same rate limits as sendOtp().
// 4. verifySignupOtp() — POST /signup/verify-otp. Marks the email verified
//    in the session for 5 minutes.
// 5. signup() — POST /signup. Creates a `pending` staff row (email+password
//    only), but only for an email verifySignupOtp() just verified; a
//    Coordinator assigns the role later from the Staff screen.
// 6. sendOtp() — POST /forgot-password/send-otp. Emails a 6-digit code via
//    OtpService, rate-limited per email (60s cooldown, 5/hour cap). Always
//    returns the same generic response whether or not the email exists, so
//    the endpoint can't be used to enumerate accounts.
// 7. verifyOtp() — POST /forgot-password/verify-otp. Checks the code against
//    otp_codes, then marks the email verified in the session for 5 minutes.
// 8. resetPassword() — POST /forgot-password. Overwrites the password, but
//    only if verifyOtp() marked this email verified within the last 5 min.
// 9. logout() — destroys the session and redirects to /login.
// 10. dashboardUrlForRole() — the role->URL mapping shared by loginView(),
//    signupView(), login(), and the `/` route in index.php.
// 11. jsonResponse() — private helper every action above returns through.
//
// DEMO_AUTH (config.php) simulates every OTP step (see app/services/OtpService.php):
// nothing is emailed and any 6-digit code verifies. Who may sign up is
// enforced in both modes.
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
        // ...and refuse a member who has been deactivated (left the university).
        if (($user['status'] ?? 'active') !== 'active') {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'This account has been deactivated. Contact the department coordinator if you think this is a mistake.',
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
        // Cached for the header profile chip, which shows the member's own name
        // rather than their role title. ViewHelpers::currentUserName() back-fills
        // sessions older than this key, so nobody has to sign in again for it.
        $_SESSION['name'] = $user['name'] ?? null;

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

        // 2. Re-check who may register — verifySignupOtp() in demo mode
        //    accepts any email, so this is the gate that can't be skipped.
        if (!$this->isAllowedSignupEmail($email)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => $this->signupDomainMessage()], 403);
        }

        // 3. Require a recent, matching verifySignupOtp() success.
        $verified = $_SESSION['signup_verified'] ?? null;
        if (!$verified || ($verified['email'] ?? null) !== $email || ($verified['until'] ?? 0) < time()) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Please verify your email again.'], 403);
        }

        $staffModel = new StaffModel();

        // 4. One email = one account; reject duplicates up front.
        if ($staffModel->findByEmail($email)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Email is already registered'], 409);
        }

        // 5. Create a pending account — a Coordinator/In-Charge assigns the
        //    role via the Staff approval screen before this account can log in.
        if ($staffModel->create($email, $password)) {
            unset($_SESSION['signup_verified']);
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Registration submitted! A coordinator will review your account before you can sign in.',
                'redirect' => '/login',
            ]);
        }

        return $this->jsonResponse($response, ['success' => false, 'message' => 'Registration failed due to a server error'], 500);
    }

    // POST /signup/send-otp — step 1 of the signup wizard. Unlike sendOtp()
    // this says plainly when an email can't be used: signup() already reveals
    // "already registered", so there's nothing extra to hide here.
    public function sendSignupOtp(Request $request, Response $response)
    {
        $body = $request->getBody();
        $email = trim($body['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Enter a valid email address'], 400);
        }
        if (!$this->isAllowedSignupEmail($email)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => $this->signupDomainMessage()], 403);
        }
        if ((new StaffModel())->findByEmail($email)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Email is already registered. Sign in instead.'], 409);
        }

        $refused = OtpService::send($email, 'signup');
        if ($refused !== null) {
            return $this->jsonResponse($response, ['success' => false, 'message' => $refused[0]], $refused[1]);
        }

        return $this->jsonResponse($response, ['success' => true, 'message' => 'A verification code has been sent to your email.']);
    }

    // POST /signup/verify-otp — step 2; on success signup() will accept this
    // email for the next 5 minutes.
    public function verifySignupOtp(Request $request, Response $response)
    {
        return $this->verifyOtpFor($request, $response, 'signup', 'signup_verified');
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

        $generic = ['success' => true, 'message' => 'If this email is registered, a code has been sent.'];

        // An unknown email gets the same answer and no code. It also never
        // hits a rate limit (it has no otp_codes rows), so a 429 can't
        // reveal which emails are registered either.
        if (!(new StaffModel())->findByEmail($email)) {
            return $this->jsonResponse($response, $generic);
        }

        $refused = OtpService::send($email, 'password_reset');
        if ($refused !== null) {
            return $this->jsonResponse($response, ['success' => false, 'message' => $refused[0]], $refused[1]);
        }

        return $this->jsonResponse($response, $generic);
    }

    // POST /forgot-password/verify-otp — step 2. Checks the submitted code
    // against the latest active otp_codes row for this email; on a match,
    // marks it consumed and flags the email as verified in the session for
    // 5 minutes so resetPassword() can trust it.
    public function verifyOtp(Request $request, Response $response)
    {
        return $this->verifyOtpFor($request, $response, 'password_reset', 'password_reset_verified');
    }

    // Shared by verifyOtp() and verifySignupOtp(): checks {email, otp} against
    // the latest active otp_codes row for $purpose, then sets
    // $_SESSION[$sessionKey] = {email, until: +5 min} for the next step.
    private function verifyOtpFor(Request $request, Response $response, string $purpose, string $sessionKey)
    {
        $body = $request->getBody();
        $email = trim($body['email'] ?? '');
        $otp = trim($body['otp'] ?? '');

        $error = OtpService::verify($email, $purpose, $otp);
        if ($error !== null) {
            return $this->jsonResponse($response, ['success' => false, 'message' => $error], 400);
        }

        $_SESSION[$sessionKey] = ['email' => $email, 'until' => time() + 300];
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

    // Where an already-authenticated user's dashboard lives. Public (not
    // private) because the `/` and `/dashboard` routes in index.php call this
    // from bare closures with no $this — see app/public/index.php.
    //
    // Every role now lands on the same URL: /timetable is canonical and renders
    // a different view per role, so there is nothing left to branch on. The
    // method survives its own body because six call sites use it, and because
    // it stays the single place to change if a role ever needs a different
    // landing page again — better that than six literals to hunt down.
    public function dashboardUrlForRole(string $role): string
    {
        return '/timetable';
    }

    // Staff sign up with their university address — see app/core/StaffEmail.php.
    private function isAllowedSignupEmail(string $email): bool
    {
        return StaffEmail::isAllowed($email);
    }

    private function signupDomainMessage(): string
    {
        return 'Registration restricted: please use your official @' . StaffEmail::domain() . ' staff email.';
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
