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
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requirePosition('in_charge');
        if ($denied !== null) {
            return $denied;
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

