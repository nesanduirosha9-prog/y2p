<?php

namespace app\controllers;

use app\core\Controller;
use app\models\StaffModel;
use app\core\Response;

// HomeController: example controller that demonstrates typical actions.
// - `index` renders the home page
// - `about` renders a static about page
// - `usersJson` shows how to return JSON via Response
// - `oldAbout` shows a redirect
class HomeController extends Controller
{

    // Show the home page with a list of users from the model
    public function index(): string
    {
        $model = new StaffModel();
        $users = $model->getAllUsers();

        return $this->render('home/index', [
            'title' => 'Home Page',
            'users' => $users,
        ]);
    }

    // Show the about page
    public function about(): string
    {
        return $this->render('home/about', [
            'title' => 'About'
        ]);
    }

    // Example: JSON API endpoint using Response
    public function usersJson(Response $response): void
    {
        $model = new StaffModel();
        // Response::json sets headers and echoes body then exits
        $response->json([
            'success' => true,
            'users' => $model->getAllUsers(),
        ]);
    }

    // Example: redirect using Response
    public function oldAbout(Response $response): void
    {
        $response->redirect('/about');
    }

    // Render the 404 page
    public function notFound()
    {
        return $this->renderPartial('errors/notfound', [
            'title' => 'Not Found'
        ]);
    }
}
