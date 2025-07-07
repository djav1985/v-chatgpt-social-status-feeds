<?php
namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        require __DIR__ . '/../Core/LoadHelper.php';
        $this->render('home');
    }
}
