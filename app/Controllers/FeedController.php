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
}
