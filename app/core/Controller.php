<?php

namespace app\core;

use app\core\Application;

// Controller: base controller with view helpers used by concrete controllers.
// - `render` wraps a view inside the main layout
// - `renderPartial` renders a view fragment
// - `redirect` is a convenience that delegates to the global Response
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
}
