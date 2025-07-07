<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config.php';
session_start();

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\AccountsController;
use App\Controllers\UsersController;
use App\Controllers\InfoController;
use App\Controllers\FeedController;

$router = new Router();

$router->get('/login', [new AuthController(), 'login']);
$router->post('/login', [new AuthController(), 'login']);
$router->post('/logout', [new AuthController(), 'logout']);
$router->get('/', [new HomeController(), 'index']);
$router->get('/home', [new HomeController(), 'index']);
$router->get('/accounts', [new AccountsController(), 'index']);
$router->post('/accounts', [new AccountsController(), 'index']);
$router->get('/users', [new UsersController(), 'index']);
$router->post('/users', [new UsersController(), 'index']);
$router->get('/info', [new InfoController(), 'index']);
$router->post('/info', [new InfoController(), 'index']);
$router->get('/feeds/{user}/{account}', [new FeedController(), 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
