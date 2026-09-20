<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\StaffModel;

// AuthController: login / signup / forgot-password.
// 1. *View methods (loginView, signupView, forgotPasswordView) — GET, render
//    the page. Login/signup bounce an already-logged-in user to their
//    dashboard instead of showing the form again.
// 2. login() — POST /login. Verifies credentials, blocks `pending` accounts,
//    then writes the session and returns a redirect URL by role.
// 3. signup() — POST /signup. Creates a `pending` staff row (email+password
//    only); a Coordinator assigns the role later from the Staff screen.
// 4. resetPassword() — POST /forgot-password. Overwrites the password for
//    an existing email (no OTP check happens server-side — see gaps below).
// 5. logout() — destroys the session and redirects to /login.
// 6. jsonResponse() — private helper every action above returns through.
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
            $redirectUrl = ($_SESSION['role'] === 'academic_staff') ? '/instructor/timetable' : '/timetable';
            $this->redirect($redirectUrl);
            return;
        }
        return $this->render('auth/login', ['title' => 'Login', 'css_file' => '/css/login.css']);
    }

    public function signupView()
    {
        if (isset($_SESSION['staff_code'])) {
            $redirectUrl = ($_SESSION['role'] === 'academic_staff') ? '/instructor/timetable' : '/timetable';
            $this->redirect($redirectUrl);
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

        // 4. Start the session — every dashboard controller reads these keys.
        $_SESSION['staff_code'] = $user['code'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['academic_rank'] = $user['academic_rank'];
        $_SESSION['position'] = $user['position'];

        // 5. Tell the client where to go next (JS does the redirect).
        $redirectUrl = ($user['role'] === 'academic_staff') ? '/instructor/timetable' : '/timetable';
        return $this->jsonResponse($response, ['success' => true, 'message' => 'Login successful', 'redirect' => $redirectUrl]);
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

    public function resetPassword(Request $request, Response $response)
    {
        // 1. Read the new password + the email it belongs to.
        //    NOTE: this trusts that the client-side OTP step (forgot_password.js)
        //    actually happened — see "gaps" note on the OTP flow.
        $body = $request->getBody();
        $email = $body['email'] ?? '';
        $newPassword = $body['password'] ?? '';

        if (empty($email) || empty($newPassword)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Email and new password are required'], 400);
        }

        $staffModel = new StaffModel();

        // 2. Ensure the account actually exists before touching it.
        if (!$staffModel->findByEmail($email)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'No account found with this email'], 404);
        }

        // 3. Overwrite the password hash and report the outcome.
        if ($staffModel->updatePassword($email, $newPassword)) {
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

    // Every action above funnels its JSON reply through here: set the
    // status code, force the content type, and encode the payload.
    private function jsonResponse(Response $response, array $data, int $statusCode = 200)
    {
        $response->setStatusCode($statusCode);
        header('Content-Type: application/json');
        return json_encode($data);
    }
}
