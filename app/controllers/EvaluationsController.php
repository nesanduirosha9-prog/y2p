<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;

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
    // Page copy for the review dashboard, keyed by $_SESSION['position'].
    // Kept verbatim from the controllers and views this replaces, including
    // the wrapper CSS class, which evaluations.css styles per position.
    private const COPY = [
        'coordinator' => [
            'title'        => 'Junior Staff Evaluations — StaffSync',
            'pageTitle'    => 'Staff Performance & Evaluations',
            'pageSubtitle' => 'Review evaluations submitted by lecturers for junior staff across all course modules',
            'viewClass'    => 'coordinator-eval-view',
            'heading'      => 'Junior Staff Evaluations Dashboard',
            'subheading'   => 'Comprehensive overview of performance ratings, strengths, and recommendations across all faculty modules',
        ],
        'in_charge' => [
            'title'        => 'Staff Performance Appraisal — StaffSync',
            'pageTitle'    => 'Junior Staff Appraisals & Reviews',
            'pageSubtitle' => 'Executive review of lecturer evaluations for contract extensions, promotions, and commendations',
            'viewClass'    => 'in-charge-eval-view',
            'heading'      => 'Staff Performance & Appraisal Center',
            'subheading'   => 'Review academic performance metrics, student feedback reports, and coordinator recommendations',
        ],
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
        if (!isset(self::COPY[$position])) {
            $this->redirect('/courses');
            return '';
        }

        $copy = self::COPY[$position];

        return $this->render('evaluations', [
            'title' => $copy['title'],
            'css_file' => ['/css/directory.css', '/css/workload_matrix.css', '/css/evaluations.css'],
            'active' => 'evaluations',
            'pageTitle' => $copy['pageTitle'],
            'pageSubtitle' => $copy['pageSubtitle'],
            'viewClass' => $copy['viewClass'],
            'heading' => $copy['heading'],
            'subheading' => $copy['subheading'],
        ]);
    }
}
