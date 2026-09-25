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
// distribution() — GET /workload/distribution[?tab=…]. One page, two jobs:
//   courses                 the semester allocation matrix. Coordinator and
//                           In-Charge; the wording differs on purpose (the
//                           Coordinator allocates, the In-Charge oversees) and
//                           lives in self::COPY below.
//   week / requests / free  the Duty Scheduler — one dated duty at a time.
//                           Coordinator only. It used to be its own page at
//                           /workload/scheduler, which now redirects here.
//   history                 every allocation over time — duties (auto,
//                           manual, cover, replacement) and course changes.
//                           Both positions; read-only.
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
            'title'        => 'Workload — StaffSync',
            'pageTitle'    => 'Workload',
            'viewClass'    => 'coordinator-workload-view',
        ],
        'in_charge' => [
            'title'        => 'Department Workload Distribution — StaffSync',
            'pageTitle'    => 'Workload',
            'viewClass'    => 'in-charge-workload-view',
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

        $isCoordinator = $position === 'coordinator';

        // Only the Coordinator runs duties, so only they get the duty tabs.
        // History is read-only oversight, so the In-Charge sees it too.
        // An unknown or forbidden ?tab= falls back to the matrix.
        $tabs = $isCoordinator
            ? ['courses', 'week', 'requests', 'free', 'history']
            : ['courses', 'history'];
        $tab = $request->getQueryParams()['tab'] ?? 'courses';
        if (!in_array($tab, $tabs, true)) {
            $tab = 'courses';
        }

        return $this->render('workload_distribution', [
            'title' => $copy['title'],
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css', '/css/scheduler.css', '/css/workload_history.css'],
            'active' => 'workload-dist',
            'pageTitle' => $copy['pageTitle'],
            'viewClass' => $copy['viewClass'],
            'tabs' => $tabs,
            'tab' => $tab,
            // The Coordinator allocates; the In-Charge oversees. Same matrix,
            // but only one of them gets the assign/unassign controls.
            'canEdit' => $isCoordinator,
            // One payload per half, rendered client-side by
            // js/workload_matrix.js and js/scheduler.js. The views carry no
            // data of their own — swapping WorkloadPrototypeData for real
            // models is a change to this class alone. See
            // app/core/WorkloadPrototypeData.php.
            'matrixData' => [
                'courses'     => WorkloadPrototypeData::courses(),
                'staff'       => WorkloadPrototypeData::staff(),
                'load'        => WorkloadPrototypeData::staffLoad(),
                'engagements' => WorkloadPrototypeData::ENGAGEMENTS,
                'canEdit'     => $isCoordinator,
                // Dates edits made this session in the History tab.
                'today'       => WorkloadPrototypeData::week()['from'],
            ],
            'schedulerData' => $isCoordinator ? $this->schedulerData() : null,
            // Everything before this week, plus this week's duties as they
            // ship. On the Coordinator's page js/scheduler.js then replaces
            // the latter live, and js/workload_matrix.js adds course edits.
            'historyData' => [
                'records'  => WorkloadPrototypeData::allocationHistory(),
                'duties'   => WorkloadPrototypeData::duties(),
                'week'     => WorkloadPrototypeData::week(),
                'calendar' => WorkloadPrototypeData::calendar(),
                'staff'    => WorkloadPrototypeData::staff(),
            ],
        ]);
    }

    // js/scheduler.js runs the real allocation rules from CurrentViews/script.js
    // against this payload, so the algorithm is written and testable before the
    // backend exists — the port becomes a translation, not a design exercise.
    private function schedulerData(): array
    {
        return [
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
        ];
    }
}
