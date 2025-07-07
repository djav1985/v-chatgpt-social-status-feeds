<?php
namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require __DIR__ . '/../Core/AuthMiddleware.php';
        }
        $this->render('login');
    }

    public function logout(): void
    {
        $_POST['logout'] = true;
        require __DIR__ . '/../Core/AuthMiddleware.php';
    }
}
