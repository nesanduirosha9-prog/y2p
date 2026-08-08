<?php

namespace app\core;

use app\core\Application;

class Controller
{

    // Render a view file wrapped inside the main layout
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
        $layoutPath = Application::$ROOT_DIR . '/views/layouts/main.php';
        ob_start();
        require $layoutPath;
        return ob_get_clean();
    }
}
