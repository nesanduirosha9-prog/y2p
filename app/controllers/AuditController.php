<?php

namespace app\controllers;

use app\core\AuditPrototypeData;
use app\core\Controller;
use app\core\Request;
use app\core\ViewHelpers;

// AuditController — the activity log, at two URLs for two different readers.
//
//   GET /audit     the department-wide log. Coordinator and In-Charge only.
//   GET /audit/me  your own record. Every signed-in account, every role.
//
// ONE VIEW, TWO SCOPES. Both render views/audit_log.php with a $scope of
// 'system' or 'own', because they are the same screen answering the same
// question at two widths — "what has been done, and by whom" versus "what have
// I done". Two views would have drifted apart the way the workload screens did.
//
// WHERE AUTHORISATION LIVES. AuditPrototypeData::feed() takes the viewer and
// returns only the rows they may read, so the rows a Coordinator is not allowed
// to see are never put into the page at all. That matters more here than on any
// other screen: a log is exactly the kind of data people try to read sideways,
// and a client-side filter over a full payload would be no protection — the
// whole log would be one View Source away. The guards below decide WHICH SCREEN
// you get; feed() decides WHICH ROWS. Neither is a substitute for the other.
//
// There is no store(), no update() and no destroy() in this controller, and
// there never should be. Entries are written by whichever controller performed
// the action (that is the backend work), and nothing in the application ever
// edits or removes one.
class AuditController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    /** GET /audit — the department-wide log. */
    public function index(Request $request)
    {
        $denied = $this->requirePosition('coordinator', 'in_charge');
        if ($denied !== null) {
            return $denied;
        }

        $viewer = $this->viewer();

        return $this->render('audit_log', [
            'title'        => 'Activity Log — StaffSync',
            'css_file'     => ['/css/directory.css', '/css/audit.css'],
            'active'       => 'audit',
            'pageTitle'    => 'Activity Log',
            'scope'        => 'system',
            // See app/core/AuditPrototypeData.php — same seam as the workload
            // screens. Swapping the fixture for an AuditLogModel is a change to
            // this one line, plus passing the filters through to SQL so the
            // browser is handed a page at a time instead of the whole log.
            'auditData'    => AuditPrototypeData::feed($viewer, 'system'),
        ]);
    }

    /** GET /audit/me — your own record. Open to every signed-in account. */
    public function mine(Request $request)
    {
        $denied = $this->requireLogin();
        if ($denied !== null) {
            return $denied;
        }

        return $this->render('audit_log', [
            'title'        => 'My Activity — StaffSync',
            'css_file'     => ['/css/directory.css', '/css/audit.css'],
            // No sidebar item is highlighted: this page is reached from the
            // profile menu, not from the navigation, and pretending otherwise
            // would light up a link that is not there for most roles.
            'active'       => 'audit-me',
            'pageTitle'    => 'My Activity',
            'scope'        => 'own',
            'auditData'    => AuditPrototypeData::feed($this->viewer(), 'own'),
        ]);
    }

    /**
     * The signed-in member, in the shape feed() expects. Read from the session
     * only: the log's visibility rule must not depend on a value the browser
     * can send.
     */
    private function viewer(): array
    {
        return [
            'code'     => $_SESSION['staff_code'] ?? '',
            // currentUserName() back-fills a session created before the name
            // was cached, so the log header never shows a bare staff code.
            'name'     => ViewHelpers::currentUserName(),
            'role'     => $_SESSION['role'] ?? '',
            'rank'     => $_SESSION['academic_rank'] ?? null,
            'position' => $_SESSION['position'] ?? null,
        ];
    }
}
