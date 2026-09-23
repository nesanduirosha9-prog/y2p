<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;
use app\models\StaffModel;

// SettingsController: the "Account Settings" page, for every role.
//
// Replaces the two byte-identical controllers this used to be — one under
// instructor/, one under timetable_officer/ — which differed only in the role
// they guarded and the 9-line view they rendered. Settings is the one screen
// open to everybody, so there was never anything role-specific to keep apart:
// what a member may edit about themselves (name, phone, office, extension,
// bio) is the same whoever they are.
//
// 1. index()  — GET /settings. Loads the real staff row. Also the page a
//    self-registered account fills in its own details on post-signup (see
//    StaffModel::create()).
// 2. update() — POST /settings. Saves the editable subset of that row.
//
// The one branch left is the Department In-Charge's role-handover panel, which
// renders as an extra tab of this page and needs the role-holder list plus two
// more stylesheets. Everyone else, including a Timetable Officer, sees the
// profile tab alone.
class SettingsController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // Open to every signed-in role, so this is a login check, not a role
        // check — see app/core/Controller.php.
        $denied = $this->requireLogin();
        if ($denied !== null) {
            return $denied;
        }

        $profile = (new StaffModel())->findByCode($_SESSION['staff_code']);
        $isInCharge = (($_SESSION['position'] ?? '') === 'in_charge');
        $roleHolders = $isInCharge ? (new StaffModel())->roleHolders() : [];

        $cssFiles = ['/css/settings.css'];
        if ($isInCharge) {
            $cssFiles[] = '/css/directory.css';
            $cssFiles[] = '/css/in_charge/accounts.css';
        }

        return $this->render('settings', [
            'title' => 'Settings',
            'css_file' => $cssFiles,
            'active' => 'settings',
            'pageTitle' => 'Settings',
            'profile' => $profile,
            'isInCharge' => $isInCharge,
            'roleHolders' => $roleHolders,
            'formAction' => '/settings',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }

    public function update(Request $request, Response $response)
    {
        // Answers in JSON (settings.js posts with fetch), and again needs only
        // a signed-in user: passing no allowed values to guardJson() means
        // "any role".
        if (!$this->guardJson($response, 'role')) {
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
