<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: HomeController.php
 * Description: AI Social Status Generator
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\AuthMiddleware;
use App\Controllers\StatusController;
use App\Models\UserHandler;
use App\Models\StatusHandler;

class HomeController extends Controller
{
    public static function handleRequest(): void
    {
        AuthMiddleware::check();

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
                        $statusResult = StatusController::generateStatus($accountName, $accountOwner);
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

        self::render('home');
    }

    public static function shareButton(string $statusText, string $imagePath, string $accountOwner, string $accountName, int $statusId): string
    {
        $filename = basename($imagePath);
        $imageUrl = DOMAIN . "/images/" . htmlspecialchars($accountOwner, ENT_QUOTES) . "/" . htmlspecialchars($accountName, ENT_QUOTES) . "/" . htmlspecialchars($filename, ENT_QUOTES);
        $encodedStatusText = htmlspecialchars($statusText, ENT_QUOTES);

        $combinedSvg = '<svg width="24" height="24" fill="currentColor" xmlns="https://www.w3.org/2000/svg"><path d="M19 3H14.82c-.42-1.16-1.52-2-2.82-2s-2.4.84-2.82 2H5c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.11-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm1 14H8v-2h5v2zm3-4H8v-2h8v2zm0-4H8V7h8v2z" /><path d="M12 16l-5.5 5.5 1.41 1.41L11 18.83V16z"/></svg>';
        $shareSvg = '<svg width="24" height="24" fill="currentColor" xmlns="https://www.w3.org/2000/svg"><path d="M18 16.08c-0.76 0-1.44 0.3-1.96 0.77L8.91 12.7c0.03-0.15 0.04-0.3 0.04-0.46s-0.01-0.31-0.04-0.46l7.13-4.11c0.52 0.48 1.2 0.78 1.96 0.78 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 0.16 0.01 0.31 0.04 0.46l-7.13 4.11c-0.52-0.48-1.2-0.78-1.96-0.78-1.66 0-3 1.34-3 3s1.34 3 3 3c0.76 0 1.44-0.3 1.96-0.77l7.13 4.11c-0.03 0.15-0.04 0.3-0.04 0.46 0 1.66 1.34 3 3 3s3-1.34 3-3-1.34-3-3-3z"/></svg>';
        $deleteSvg = '<svg width="24" height="24" fill="currentColor" xmlns="https://www.w3.org/2000/svg"><path d="M7.5 20l4.5-4.5 4.5 4.5 1.5-1.5-4.5-4.5 4.5-4.5-1.5-1.5-4.5 4.5-4.5-4.5-1.5 1.5 4.5 4.5-4.5 4.5 1.5 1.5z"/></svg>';

        $content = "<div class='status-actions'>";
        $content .= "<button class='btn btn-primary square-button copy-button' data-text='{$encodedStatusText}' data-url='{$imageUrl}' data-filename='{$filename}' title='Copy Text and Download Image'>{$combinedSvg}</button>";
        $content .= "<button class='btn btn-success square-button share-button' data-text='{$encodedStatusText}' data-url='{$imageUrl}' title='Share'>{$shareSvg}</button>";
        $content .= "<form action='/home' method='POST' class='delete-form'>";
        $content .= "<input type='hidden' name='account' value='" . htmlspecialchars($accountName, ENT_QUOTES) . "'>";
        $content .= "<input type='hidden' name='username' value='" . htmlspecialchars($accountOwner, ENT_QUOTES) . "'>";
        $content .= "<input type='hidden' name='id' value='" . htmlspecialchars($statusId, ENT_QUOTES) . "'>";
        $content .= "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) . "'>";
        $content .= "<button class='btn btn-error square-button delete-button' type='submit' name='delete_status'>{$deleteSvg}</button>";
        $content .= "</form>";
        $content .= "</div>";

        return $content;
    }
}
