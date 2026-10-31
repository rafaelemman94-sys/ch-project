<?php
namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\UserController;

class App
{
    private AdminController $controller;
    private UserController $userController;

    public function __construct()
    {
        $this->controller = new AdminController();
        $this->userController = new UserController($this->controller);
    }

    public function run(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->controller->handleRequest();
            return;
        }

        $page = $_GET['page'] ?? 'dashboard';
        $this->userController->render($page);
    }
}
