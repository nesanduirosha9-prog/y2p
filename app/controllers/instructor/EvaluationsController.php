<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;

class EvaluationsController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    private function checkAccess(): ?string
    {
        if (!isset($_SESSION['staff_code'])) {
            $this->redirect('/login');
            return '';
        }
        if (($_SESSION['role'] ?? '') !== 'academic_staff') {
            return $this->forbidden();
        }
        return null;
    }

    public function index(Request $request)
    {
        $denied = $this->checkAccess();
        if ($denied !== null) {
            return $denied;
        }

        // Evaluations are integrated directly per course module in My Courses
        $this->redirect('/instructor/my-courses');
        return '';
    }
}

