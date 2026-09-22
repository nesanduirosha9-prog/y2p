<?php

namespace app\controllers\in_charge;

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

        return $this->render('in_charge/evaluations', [
            'title' => 'Staff Performance Appraisal — StaffSync',
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css', '/css/evaluations.css'],
            'active' => 'evaluations',
            'pageTitle' => 'Junior Staff Appraisals & Reviews',
            'pageSubtitle' => 'Executive review of lecturer evaluations for contract extensions, promotions, and commendations',
        ]);
    }
}

