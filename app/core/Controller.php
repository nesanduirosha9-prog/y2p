<?php

namespace app\core;

use app\core\Application;
use app\models\StaffModel;

// Controller: base controller with view helpers used by concrete controllers.
// - `render` wraps a view inside the main layout
// - `renderPartial` renders a view fragment
// - `redirect` is a convenience that delegates to the global Response
// - `requireLogin` / `requireRole` / `requirePosition` / `guardJson` are the
//   shared route guards. Before these existed every controller carried its own
//   copy-pasted `checkAccess()` or inline `if (!isset($_SESSION[...]))` block,
//   and they disagreed with each other: some sent a signed-in user with the
//   wrong role to /login, others rendered a 403. They now all behave the same.
class Controller
{
    /**
     * Default layout to use for rendering
     */
    public string $layout = 'main';

    /**
     * Set the layout
     */
    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    // Render a view file wrapped inside the layout
    protected function render(string $view, array $params = []): string
    {
        // Make $params array keys available as variables inside the view
        extract($params);

        // Capture the view's own output first
        $viewPath = Application::$ROOT_DIR . "/views/" . $view . '.php';
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Now render the layout, injecting $content into it
        $layoutPath = Application::$ROOT_DIR . '/views/layouts/' . $this->layout . '.php';
        ob_start();
        require $layoutPath;
        return ob_get_clean();
    }

    // Render just the view without the layout
    protected function renderPartial(string $view, array $params = []): string
    {
        extract($params);
        $viewPath = Application::$ROOT_DIR . "/views/" . $view . '.php';
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }
        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    // Shortcut redirect using the global application response
    protected function redirect(string $url): void
    {
        Application::$app->getResponse()->redirect($url);
    }

    protected function forbidden(): string
    {
        Application::$app->getResponse()->setStatusCode(403);
        return $this->renderPartial('errors/forbidden');
    }

    // --- Route guards -------------------------------------------------------
    //
    // All four answer the same question — "may this session run this action?" —
    // and they all follow the same contract:
    //
    //     null  => allowed, carry on
    //     ''    => denied, and a redirect has already been sent (Response::
    //              redirect() calls exit, so this return is only for the
    //              reader's benefit — control never actually gets here)
    //     other => denied, and the string is the rendered 403 page to return
    //
    // Which makes every call site a uniform two lines:
    //
    //     $denied = $this->requireRole('academic_staff');
    //     if ($denied !== null) return $denied;

    /**
     * No session at all -> bounce to the login page.
     * This is the only guard that redirects; a *wrong* role is a 403, not a
     * trip back to login, because the user is already correctly signed in.
     */
    protected function requireLogin(): ?string
    {
        if (!isset($_SESSION['staff_code']) || !$this->sessionStillValid()) {
            $this->redirect('/login');
            return '';
        }
        return null;
    }

    /**
     * The session belongs to whoever signed in with the account's email at
     * the time. If that email has since changed — the Timetable Officer
     * account handed to a new person — the previous holder's session ends
     * here, even though the account's code is the same.
     */
    private function sessionStillValid(): bool
    {
        $me = (new StaffModel())->findByCode($_SESSION['staff_code']);
        if ($me && $me['email'] === ($_SESSION['user_email'] ?? null)) {
            return true;
        }
        session_unset();
        return false;
    }

    /**
     * Requires $_SESSION['role'] to be one of $roles (e.g. 'academic_staff',
     * 'timetable_officer'). Checks login first.
     */
    protected function requireRole(string ...$roles): ?string
    {
        return $this->requireSessionValue('role', $roles);
    }

    /**
     * Requires $_SESSION['position'] to be one of $positions ('coordinator',
     * 'in_charge'). `position` is additive on top of `role` — an academic
     * staff member may also hold a position — so this is a separate check.
     */
    protected function requirePosition(string ...$positions): ?string
    {
        return $this->requireSessionValue('position', $positions);
    }

    /**
     * The shared body of requireRole()/requirePosition(): log in first, then
     * compare one session key against a whitelist.
     */
    private function requireSessionValue(string $key, array $allowed): ?string
    {
        $denied = $this->requireLogin();
        if ($denied !== null) {
            return $denied;
        }
        if (!in_array($_SESSION[$key] ?? '', $allowed, true)) {
            return $this->forbidden();
        }
        return null;
    }

    /**
     * The same check for endpoints that answer in JSON (POST/PUT handlers the
     * front-end calls with fetch()), where a redirect or an HTML 403 page
     * would be useless. Sends a 401 JSON body and exits when denied.
     *
     * $key is 'role' or 'position'. Passing no $allowed values checks only
     * that somebody is signed in — which is what a screen open to every role
     * (Settings) needs.
     *
     * Response::json() exits, so the `false` return is never actually read;
     * it exists so the call site reads as `if (!$this->guardJson(...)) return;`
     * and so the guard can be unit-tested with a fake Response later.
     */
    protected function guardJson(Response $response, string $key, string ...$allowed): bool
    {
        if (!isset($_SESSION['staff_code']) || !$this->sessionStillValid()) {
            $response->json(['success' => false, 'message' => 'Not authenticated'], 401);
            return false;
        }
        if ($allowed !== [] && !in_array($_SESSION[$key] ?? '', $allowed, true)) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return false;
        }
        return true;
    }
}
