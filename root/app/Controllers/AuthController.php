<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\AuthMiddleware;

class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::handle();
        }
        $this->render('login');
    }

    public function logout(): void
    {
        $_POST['logout'] = true;
        AuthMiddleware::handle();
    }
}
