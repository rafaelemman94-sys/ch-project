<?php
namespace App\Controllers;

class UserController
{
    private AdminController $adminController;

    public function __construct(?AdminController $adminController = null)
    {
        $this->adminController = $adminController ?? new AdminController();
    }

    public function handleRequest(): void
    {
        $this->adminController->handleRequest();
    }

    public function render(string $page): void
    {
        $this->adminController->render($page);
    }
}
