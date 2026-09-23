<?php

namespace app\controllers\coordinator;

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
        $denied = $this->requirePosition('coordinator');
        if ($denied !== null) {
            return $denied;
        }

        return $this->render('coordinator/evaluations', [
            'title' => 'Junior Staff Evaluations — StaffSync',
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css', '/css/evaluations.css'],
            'active' => 'evaluations',
            'pageTitle' => 'Staff Performance & Evaluations',
            'pageSubtitle' => 'Review evaluations submitted by lecturers for junior staff across all course modules',
        ]);
    }
}

