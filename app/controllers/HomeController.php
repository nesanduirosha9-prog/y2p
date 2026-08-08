<?php

namespace app\controllers;

use app\core\Controller;
use app\models\UserModel;
use app\core\Response;


class HomeController extends Controller
{

    public function index(): string
    {
        $model = new UserModel();
        $users = $model->getAllUsers();

        return $this->render('home/index', [
            'title' => 'Home Page',
            'users' => $users,
        ]);
    }

    public function about(): string
    {
        return $this->render('home/about', [
            'title' => 'About'
        ]);
    }
    // Example: JSON API endpoint using Response
    public function usersJson(Response $response): void
    {
        $model = new UserModel();
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
    public function notFound()
    {
        return $this->render('errors/notfound', [
            'title' => 'Not Found'
        ]);
    }
}
