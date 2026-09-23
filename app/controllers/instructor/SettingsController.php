<?php

namespace app\controllers\instructor;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;
use app\models\StaffModel;

// SettingsController (instructor): "Settings" page. Also the page a
// self-registered account fills in its own name/phone/office/bio on
// post-signup (see StaffModel::create()) — index() loads the real row,
// update() saves the subset of it a member may edit about themselves.
class SettingsController extends Controller
{
    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        if (!isset($_SESSION['staff_code']) || ($_SESSION['role'] ?? '') !== 'academic_staff') {
            $this->redirect('/login');
            return;
        }

        $profile = (new StaffModel())->findByCode($_SESSION['staff_code']);
        $isInCharge = (($_SESSION['position'] ?? '') === 'in_charge');
        $roleHolders = $isInCharge ? (new StaffModel())->roleHolders() : [];

        $cssFiles = ['/css/settings.css'];
        if ($isInCharge) {
            $cssFiles[] = '/css/directory.css';
            $cssFiles[] = '/css/in_charge/accounts.css';
        }

        return $this->render('instructor/settings', [
            'title' => 'Settings',
            'css_file' => $cssFiles,
            'active' => 'settings',
            'pageTitle' => 'Settings',
            'profile' => $profile,
            'isInCharge' => $isInCharge,
            'roleHolders' => $roleHolders,
            'formAction' => '/instructor/settings',
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
        ]);
    }

    public function update(Request $request, Response $response)
    {
        if (!isset($_SESSION['staff_code']) || ($_SESSION['role'] ?? '') !== 'academic_staff') {
            $response->json(['success' => false, 'message' => 'Not authenticated'], 401);
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
