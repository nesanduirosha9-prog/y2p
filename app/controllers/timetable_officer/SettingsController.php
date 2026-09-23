<?php

namespace app\controllers\timetable_officer;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;
use app\models\StaffModel;

// SettingsController (timetable officer): renders the Settings page and
// saves the subset of the staff row a member may edit about themselves.
class SettingsController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requireRole('timetable_officer');
        if ($denied !== null) {
            return $denied;
        }

        $profile = (new StaffModel())->findByCode($_SESSION['staff_code']);

        return $this->render('timetable_officer/settings', [
            'title' => 'Settings',
            'css_file' => ['/css/settings.css'],
            'active' => 'settings',
            'pageTitle' => 'Settings',
            'profile' => $profile,
            'formAction' => '/settings',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }

    public function update(Request $request, Response $response)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        $body = $request->getBody();
        $name = trim($body['name'] ?? '');
        if ($name === '') {
            $response->json(['success' => false, 'message' => 'Name is required'], 422);
            return;
        }

        $ok = (new StaffModel())->updateProfile($_SESSION['staff_code'], [
            'name'      => $name,
            'phone'     => trim($body['phone'] ?? ''),
            'office'    => trim($body['office'] ?? ''),
            'extension' => trim($body['extension'] ?? ''),
            'bio'       => trim($body['bio'] ?? ''),
        ]);

        $response->json(['success' => $ok]);
    }
}
