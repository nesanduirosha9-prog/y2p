<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\core\WorkloadPrototypeData;

// EvaluationsController: junior staff evaluations, for every academic role.
//
// Replaces three controllers — instructor/, coordinator/ and in_charge/
// EvaluationsController — which between them guarded on role or position,
// carried four strings of page copy each, and rendered views that both
// required the same components/evaluations_review.php.
//
// One URL, /evaluations, three readings of it:
//
//   Coordinator  — reviews what lecturers submitted about junior staff.
//   In-Charge    — appraises the same data for contract and promotion
//                  decisions. Different emphasis, different wording.
//   Everyone else (senior or junior academic staff) — evaluations are done
//                  per course module, so they are sent to /courses. This is
//                  what the instructor controller already did; the page it
//                  would otherwise have rendered was never reachable.
class EvaluationsController extends Controller
{
    // The Coordinator and the In-Charge get the SAME screen. They previously
    // had two sets of copy — "Evaluations Dashboard" versus "Appraisal Center" —
    // which implied two different tools over one dataset and one workflow. They
    // read the same submissions, use the same rating scale and reach the same
    // kinds of decision; the only real difference is the word their sidebar
    // uses, which layouts/dashboard.php still sets per position.
    private const POSITIONS = ['coordinator', 'in_charge'];

    private const COPY = [
        'title'        => 'Staff Evaluations — StaffSync',
        'pageTitle'    => 'Staff Evaluations',
        'pageSubtitle' => 'Lecturer evaluations of junior staff, across every course module',
        'viewClass'    => 'eval-view',
        'heading'      => 'Junior staff evaluations',
        'subheading'   => 'Each lecturer evaluates their junior staff once a week. See who was evaluated, and which weeks were missed.',
    ];

    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // Every reader of this screen is academic staff; a Timetable Officer
        // has no evaluations to see and is refused here.
        $denied = $this->requireRole('academic_staff');
        if ($denied !== null) {
            return $denied;
        }

        $position = $_SESSION['position'] ?? '';

        // A lecturer with no position evaluates per course module, so there is
        // no dashboard to show them — send them where the work actually is.
        if (!in_array($position, self::POSITIONS, true)) {
            $this->redirect('/courses');
            return '';
        }

        $copy = self::COPY;

        return $this->render('evaluations', [
            'title' => $copy['title'],
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css', '/css/evaluations.css'],
            'active' => 'evaluations',
            'pageTitle' => $copy['pageTitle'],
            'pageSubtitle' => $copy['pageSubtitle'],
            'viewClass' => $copy['viewClass'],
            'heading' => $copy['heading'],
            'subheading' => $copy['subheading'],
            // Same seam as the workload screens — see
            // app/core/WorkloadPrototypeData.php.
            // `assignments` is who is due an evaluation each teaching week;
            // `evaluations` is what was actually submitted. A due week with no
            // submission shows as "Not evaluated".
            'evalData' => [
                'calendar'    => WorkloadPrototypeData::calendar(),
                'assignments' => WorkloadPrototypeData::evaluationAssignments(),
                'evaluations' => WorkloadPrototypeData::evaluations(),
            ],
        ]);
    }
}
