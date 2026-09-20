<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\StaffModel;

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
        $body = $request->getBody();
        $email = $body['username'] ?? ''; // Using 'username' because of the HTML input name
        $password = $body['password'] ?? '';

        $staffModel = new StaffModel();
        $user = $staffModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Invalid email or password'], 401);
        }

        if (($user['status'] ?? 'active') === 'pending') {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Your account is awaiting approval from a coordinator. You will be able to sign in once it is approved.',
            ], 403);
        }

        // Login successful
        $_SESSION['staff_code'] = $user['code'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['academic_rank'] = $user['academic_rank'];
        $_SESSION['position'] = $user['position'];

        $redirectUrl = ($user['role'] === 'academic_staff') ? '/instructor/timetable' : '/timetable';

        // Respond with JSON for AJAX request, or redirect for normal form post
        return $this->jsonResponse($response, ['success' => true, 'message' => 'Login successful', 'redirect' => $redirectUrl]);
    }

    public function signup(Request $request, Response $response)
    {
        // For the multi-step signup, we expect 'email' and 'password' in
        // the final payload — name/phone are filled in later from Settings.
        $body = $request->getBody();
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Email and password are required'], 400);
        }

        $staffModel = new StaffModel();

        // Check if user already exists
        if ($staffModel->findByEmail($email)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Email is already registered'], 409);
        }

        // Create a pending account — a Coordinator/In-Charge assigns the
        // role via the Staff approval screen before this account can log in.
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
        $body = $request->getBody();
        $email = $body['email'] ?? '';
        $newPassword = $body['password'] ?? '';

        if (empty($email) || empty($newPassword)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Email and new password are required'], 400);
        }

        $staffModel = new StaffModel();
        
        // Ensure user actually exists
        if (!$staffModel->findByEmail($email)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'No account found with this email'], 404);
        }

        if ($staffModel->updatePassword($email, $newPassword)) {
            return $this->jsonResponse($response, ['success' => true, 'message' => 'Password reset successfully', 'redirect' => '/login']);
        }

        return $this->jsonResponse($response, ['success' => false, 'message' => 'Failed to reset password'], 500);
    }

    public function logout()
    {
        session_destroy();
        $this->redirect('/login');
    }

    // Helper function for JSON responses
    private function jsonResponse(Response $response, array $data, int $statusCode = 200)
    {
        $response->setStatusCode($statusCode);
        header('Content-Type: application/json');
        return json_encode($data);
    }
}
