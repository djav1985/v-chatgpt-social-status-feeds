<?php
namespace App\Controllers;

use App\Core\Controller;

class UsersController extends Controller
{
    public function index(): void
    {
        require __DIR__ . '/../Core/LoadHelper.php';
        $this->render('users');
    }
}
