<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\AuthMiddleware;
use App\Core\ApiHandler;
use App\Models\UserHandler;
use App\Models\StatusHandler;

class HomeController extends Controller
{
    public static function handleRequest(): void
    {
        AuthMiddleware::checkSession();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
                header('Location: /home');
                exit;
            }

            if (isset($_POST['delete_status'])) {
                $accountName = trim($_POST['account']);
                $accountOwner = trim($_POST['username']);
                $statusId = (int) $_POST['id'];
                try {
                    $statusImagePath = StatusHandler::getStatusImagePath($statusId, $accountName, $accountOwner);
                    if ($statusImagePath) {
                        $imagePath = __DIR__ . '/../../public/images/' . $accountOwner . '/' . $accountName . '/' . $statusImagePath;
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                    StatusHandler::deleteStatus($statusId, $accountName, $accountOwner);
                    $_SESSION['messages'][] = 'Successfully deleted status.';
                } catch (\Exception $e) {
                    $_SESSION['messages'][] = 'Failed to delete status: ' . $e->getMessage();
                }
                header('Location: /home');
                exit;
            } elseif (isset($_POST['generate_status'])) {
                $accountName = trim($_POST['account']);
                $accountOwner = trim($_POST['username']);
                try {
                    $userInfo = UserHandler::getUserInfo($accountOwner);
                    if ($userInfo && $userInfo->used_api_calls >= $userInfo->max_api_calls) {
                        $_SESSION['messages'][] = 'Sorry, your available API calls have run out.';
                    } else {
                        $statusResult = ApiHandler::generateStatus($accountName, $accountOwner);
                        if (isset($statusResult['error'])) {
                            $_SESSION['messages'][] = 'Failed to generate status: ' . $statusResult['error'];
                        } else {
                            $userInfo->used_api_calls += 1;
                            UserHandler::updateUsedApiCalls($accountOwner, $userInfo->used_api_calls);
                            $_SESSION['messages'][] = 'Successfully generated status.';
                        }
                    }
                } catch (\Exception $e) {
                    $_SESSION['messages'][] = 'Failed to generate status: ' . $e->getMessage();
                }
                header('Location: /home');
                exit;
            }
        }

        $this->render('home');
    }
}
