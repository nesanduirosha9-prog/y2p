<?php

namespace app\controllers\coordinator;

use app\core\Controller;
use app\core\Request;

class WorkloadController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function distribution(Request $request)
    {
        if (!isset($_SESSION['staff_code'])) {
            $this->redirect('/login');
            return '';
        }

        return $this->render('coordinator/workload_distribution', [
            'title' => 'Workload Distribution — StaffSync',
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css'],
            'active' => 'workload-dist',
            'pageTitle' => 'Course Workload Distribution',
            'pageSubtitle' => 'Macro allocation matrix across Academic Years and Degree Programmes',
        ]);
    }

    public function scheduler(Request $request)
    {
        if (!isset($_SESSION['staff_code'])) {
            $this->redirect('/login');
            return '';
        }

        return $this->render('coordinator/workload_scheduler', [
            'title' => 'Workload Scheduler — StaffSync',
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css', '/css/scheduler.css'],
            'active' => 'workload-sched',
            'pageTitle' => 'Workload Scheduler & Allocator',
            'pageSubtitle' => 'Duty allocation, lowest-workload assignment, and invitation dispatch',
        ]);
    }
}

