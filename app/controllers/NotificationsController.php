<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\core\Response;
use app\models\NotificationModel;

// NotificationsController: the two JSON writes behind the header bell panel
// (app/views/components/notifications.php + js/notifications.js).
//
// There is no index() — the feed itself is rendered inline by every dashboard
// page through the notifications component, not fetched. These endpoints exist
// only so that opening a notification actually persists, instead of the badge
// resetting on the next page load (the gap the component's header used to note).
//
// Open to every signed-in role: guardJson with no role list checks the session
// and nothing else, and both model calls are scoped to $_SESSION['staff_code'],
// so one recipient can never mark another's row read.
class NotificationsController extends Controller
{
    /** POST /notifications/{id}/read — marks one notification read. */
    public function markRead(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'role')) {
            return;
        }

        $id = $params['id'] ?? '';
        if ($id === '') {
            $response->json(['success' => false, 'message' => 'Missing notification id.'], 400);
            return;
        }

        $model = new NotificationModel();
        $staffCode = $_SESSION['staff_code'];

        if (!$model->markRead($staffCode, $id)) {
            $response->json(['success' => false, 'message' => 'Notification not found.'], 404);
            return;
        }

        $response->json(['success' => true, 'unread' => $model->unreadCount($staffCode)]);
    }

    /** POST /notifications/read-all — marks the whole feed read. */
    public function markAllRead(Request $request, Response $response)
    {
        if (!$this->guardJson($response, 'role')) {
            return;
        }

        $model = new NotificationModel();
        $staffCode = $_SESSION['staff_code'];
        $changed = $model->markAllRead($staffCode);

        $response->json([
            'success' => true,
            'changed' => $changed,
            'unread' => $model->unreadCount($staffCode),
        ]);
    }
}
