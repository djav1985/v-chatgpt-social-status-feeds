<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\UtilityHandler;
use App\Core\ErrorHandler;

class FeedController extends Controller
{
    public function index(string $user, string $account): void
    {
        try {
            UtilityHandler::outputRssFeed($account, $user);
        } catch (\Exception $e) {
            ErrorHandler::logMessage('RSS feed generation failed: ' . $e->getMessage(), 'error');
            echo 'Error: ' . $e->getMessage();
        }
    }

    public static function handleRequest(): void
    {
        $user = $_GET['user'] ?? null;
        $account = $_GET['account'] ?? null;

        if (!$user || !$account) {
            http_response_code(400);
            echo 'Bad Request: Missing user or account parameter.';
            return;
        }

        $controller = new self();
        $controller->index($user, $account);
    }
}
