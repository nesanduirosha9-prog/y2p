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

    public function index(Request $request)
    {
        if (!isset($_SESSION['staff_code'])) {
            $this->redirect('/login');
            return '';
        }

        // Evaluations are integrated directly per course module in My Courses
        $this->redirect('/instructor/my-courses');
        return '';
    }
}

