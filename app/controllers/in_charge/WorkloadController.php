<?php

namespace app\controllers\in_charge;

use app\core\Controller;
use app\core\Request;

class WorkloadController extends Controller
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
        if (($_SESSION['position'] ?? '') !== 'in_charge') {
            return $this->forbidden();
        }
        return null;
    }

    public function distribution(Request $request)
    {
        $denied = $this->checkAccess();
        if ($denied !== null) {
            return $denied;
        }

        return $this->render('in_charge/workload_distribution', [
            'title' => 'Department Workload Distribution — StaffSync',
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css'],
            'active' => 'workload-dist',
            'pageTitle' => 'Workload Distribution & Faculty Equity',
            'pageSubtitle' => 'Department executive oversight of course assignments and supportive member coverage',
        ]);
    }
}

