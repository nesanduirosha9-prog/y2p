<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\core\WorkloadPrototypeData;

// WorkloadController: the department-wide workload screens.
//
// Replaces coordinator/WorkloadController and in_charge/WorkloadController,
// which differed only in the position they guarded, four strings of page copy,
// and which 20-line view they rendered — and both of those views set the same
// two flags and required the same components/workload_matrix.php.
//
// 1. distribution() — GET /workload/distribution. The same matrix for the
//    Coordinator and the Department In-Charge. The wording differs between
//    them on purpose: the Coordinator is allocating, the In-Charge is
//    overseeing. All of that copy lives in self::COPY below.
// 2. scheduler()    — GET /workload/scheduler. Coordinator only; the In-Charge
//    has no equivalent.
//
// Note this is NOT instructor/WorkloadController, which is the personal
// "My Workload" page at /workload — a different screen for a different reader.
class WorkloadController extends Controller
{
    // Page copy for the distribution matrix, keyed by $_SESSION['position'].
    // Kept verbatim from the two controllers and two views this replaces,
    // including the wrapper CSS class: workload_matrix.css styles
    // .coordinator-workload-view and .in-charge-workload-view separately.
    private const COPY = [
        'coordinator' => [
            'title'        => 'Workload Distribution — StaffSync',
            'pageTitle'    => 'Course Workload Distribution',
            'pageSubtitle' => 'Macro allocation matrix across Academic Years and Degree Programmes',
            'viewClass'    => 'coordinator-workload-view',
            'heading'      => 'Course Workload Matrix',
            'subheading'   => 'Full overview of faculty courses, lecturer-in-charge assignments, and supportive member teams',
        ],
        'in_charge' => [
            'title'        => 'Department Workload Distribution — StaffSync',
            'pageTitle'    => 'Workload Distribution & Faculty Equity',
            'pageSubtitle' => 'Department executive oversight of course assignments and supportive member coverage',
            'viewClass'    => 'in-charge-workload-view',
            'heading'      => 'Faculty Workload & Course Allocation',
            'subheading'   => 'Executive oversight of teaching load distribution, junior staff allocations, and department capacity',
        ],
    ];

    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function distribution(Request $request)
    {
        $denied = $this->requirePosition('coordinator', 'in_charge');
        if ($denied !== null) {
            return $denied;
        }

        // The guard above already limited this to the two keys in self::COPY,
        // so the lookup cannot miss.
        $position = $_SESSION['position'];
        $copy = self::COPY[$position];

        return $this->render('workload_distribution', [
            'title' => $copy['title'],
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css'],
            'active' => 'workload-dist',
            'pageTitle' => $copy['pageTitle'],
            'pageSubtitle' => $copy['pageSubtitle'],
            'viewClass' => $copy['viewClass'],
            'heading' => $copy['heading'],
            'subheading' => $copy['subheading'],
            // Only the Coordinator has a scheduler to open.
            'showSchedulerLink' => $position === 'coordinator',
            // The Coordinator allocates; the In-Charge oversees. Same matrix,
            // but only one of them gets the assign/unassign controls.
            'canEdit' => $position === 'coordinator',
            // One payload, rendered client-side by js/workload_matrix.js. The
            // views carry no data of their own any more — swapping
            // WorkloadPrototypeData for real models is a change to this method
            // alone. See app/core/WorkloadPrototypeData.php.
            'matrixData' => [
                'courses'     => WorkloadPrototypeData::courses(),
                'staff'       => WorkloadPrototypeData::staff(),
                'load'        => WorkloadPrototypeData::staffLoad(),
                'engagements' => WorkloadPrototypeData::ENGAGEMENTS,
                'canEdit'     => $position === 'coordinator',
            ],
        ]);
    }

    public function scheduler(Request $request)
    {
        $denied = $this->requirePosition('coordinator');
        if ($denied !== null) {
            return $denied;
        }

        return $this->render('workload_scheduler', [
            'title' => 'Duty Scheduler — StaffSync',
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css', '/css/scheduler.css'],
            'active' => 'workload-sched',
            'pageTitle' => 'Duty Scheduler',
            'pageSubtitle' => 'Triage duty requests, auto-allocate the least-loaded available staff, and send invites',
            // Same seam as distribution(). js/scheduler.js runs the real
            // allocation rules from CurrentViews/script.js against this payload,
            // so the algorithm is written and testable before the backend
            // exists — the port becomes a translation, not a design exercise.
            'schedulerData' => [
                'week'         => WorkloadPrototypeData::week(),
                'requests'     => WorkloadPrototypeData::requests(),
                'duties'       => WorkloadPrototypeData::duties(),
                'staff'        => WorkloadPrototypeData::staff(),
                'load'         => WorkloadPrototypeData::staffLoad(),
                'availability' => WorkloadPrototypeData::availability(),
                'leave'        => WorkloadPrototypeData::leave(),
                'slots'        => WorkloadPrototypeData::SLOTS,
                'slotHours'    => WorkloadPrototypeData::SLOT_HOURS,
                'weekdays'     => WorkloadPrototypeData::WEEKDAYS,
            ],
        ]);
    }
}
