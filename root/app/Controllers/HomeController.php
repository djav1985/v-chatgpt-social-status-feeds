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
use App\Core\Mailer;
use App\Core\Response;
use App\Models\AccountModel;
use App\Models\StatusJobModel;
use App\Models\UserModel;
use App\Models\StatusModel;
use App\Core\SessionManager;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;

class HomeController extends Controller
{
    /**
     * Display the dashboard showing accounts and recent statuses.
     *
     * @return Response
     */
    public function handleRequest(): Response
    {
        $accountOwner = SessionManager::getInstance()->get('username');
        $accounts = \App\Models\AccountModel::getAllUserAccts($accountOwner);
        $accountsData = [];
        foreach ($accounts as $account) {
            $account = (object)$account;
            $name = $account->account;
            $acctInfo = \App\Models\AccountModel::getAcctInfo($accountOwner, $name);
            $statuses = StatusModel::getStatusInfo($accountOwner, $name);
            $statusList = [];
            foreach ($statuses as $status) {
                $status = (object)$status;
                $statusList[] = [
                    'status' => $status->status,
                    'status_image' => $status->status_image,
                    'created_at' => $status->created_at,
                    'id' => $status->id,
                    'share_button' => self::shareButton(
                        $status->status,
                        $status->status_image,
                        $accountOwner,
                        $name,
                        $status->id
                    ),
                ];
            }
            $accountsData[] = [
                'name' => $name,
                'info' => $acctInfo,
                'feedUrl' => '/feeds/' . rawurlencode((string) $accountOwner) . '/' . rawurlencode((string) $name),
                'statuses' => $statusList,
            ];
        }

        return Response::view('home', [
            'accountOwner' => $accountOwner,
            'accountsData' => $accountsData,
        ]);
    }

    /**
     * Process requests to generate or delete statuses.
     *
     * @return Response
     */
    public function handleSubmission(): Response
    {
        if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            MessageHelper::addMessage('Invalid CSRF token. Please try again.');
            return Response::redirect('/home');
        }

        if (isset($_POST['delete_status'])) {
            self::deleteStatus();
            return Response::redirect('/home');
        }

        if (isset($_POST['generate_status'])) {
            self::generateStatus();
            return Response::redirect('/home');
        }

        return Response::redirect('/home');
    }

    /**
     * Build the HTML action buttons for a status entry.
     *
     * @param string $statusText   Text of the status
     * @param string $imagePath    Path to the status image
     * @param string $accountOwner Account owner username
     * @param string $accountName  Name of the account
     * @param int    $statusId     Database ID of the status
     * @return string HTML content for the buttons
     */
    private static function shareButton(string $statusText, string $imagePath, string $accountOwner, string $accountName, int $statusId): string
    {
        $filename = basename($imagePath);

        $encodedOwner = rawurlencode($accountOwner);
        $encodedAccount = rawurlencode($accountName);
        $encodedFilename = rawurlencode($filename);

        $imageUrl = DOMAIN . "/images/{$encodedOwner}/{$encodedAccount}/{$encodedFilename}";
        $escapedImageUrl = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
        $encodedStatusText = htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8');

        $combinedSvg = '<svg width="24" height="24" fill="currentColor" xmlns="https://www.w3.org/2000/Atom"><path d="M19 3H14.82c-.42-1.16-1.52-2-2.82-2s-2.4.84-2.82 2H5c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.11-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm1 14H8v-2h5v2zm3-4H8v-2h8v2zm0-4H8V7h8v2z" /><path d="M12 16l-5.5 5.5 1.41 1.41L11 18.83V16z"/></svg>';
        $shareSvg = '<svg width="24" height="24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M18 16.08c-0.76 0-1.44 0.3-1.96 0.77L8.91 12.7c0.03-0.15 0.04-0.3 0.04-0.46s-0.01-0.31-0.04-0.46l7.13-4.11c0.52 0.48 1.2 0.78 1.96 0.78 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 0.16 0.01 0.31 0.04 0.46l-7.13 4.11c-0.52-0.48-1.2-0.78-1.96-0.78-1.66 0-3 1.34-3 3s1.34 3 3 3c0.76 0 1.44-0.3 1.96-0.77l7.13 4.11c-0.03 0.15-0.04 0.3-0.04 0.46 0 1.66 1.34 3 3 3s3-1.34 3-3-1.34-3-3-3z"/></svg>';
        $deleteSvg = '<svg width="24" height="24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M7.5 20l4.5-4.5 4.5 4.5 1.5-1.5-4.5-4.5 4.5-4.5-1.5-1.5-4.5 4.5-4.5-4.5-1.5 1.5 4.5 4.5-4.5 4.5 1.5 1.5z"/></svg>';

        $content = "<div class='status-actions'>";
        $escapedFilename = htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
        $content .= "<button class='btn btn-primary square-button copy-button' data-text='{$encodedStatusText}' data-url='{$escapedImageUrl}' data-filename='{$escapedFilename}' title='Copy Text and Download Image'>{$combinedSvg}</button>";
        $content .= "<button class='btn btn-success square-button share-button' data-text='{$encodedStatusText}' data-url='{$escapedImageUrl}' title='Share'>{$shareSvg}</button>";
        $content .= "<form action='/home' method='POST' class='delete-form'>";
        $content .= "<input type='hidden' name='account' value='" . htmlspecialchars($accountName, ENT_QUOTES) . "'>";
        $content .= "<input type='hidden' name='username' value='" . htmlspecialchars($accountOwner, ENT_QUOTES) . "'>";
        $content .= "<input type='hidden' name='id' value='" . htmlspecialchars((string) $statusId, ENT_QUOTES) . "'>";
        $content .= "<input type='hidden' name='csrf_token' value='" . htmlspecialchars(SessionManager::getInstance()->get('csrf_token'), ENT_QUOTES) . "'>";
        $content .= "<button class='btn btn-error square-button delete-button' type='submit' name='delete_status'>{$deleteSvg}</button>";
        $content .= "</form>";
        $content .= "</div>";

        return $content;
    }

    /**
     * Delete a status entry and its associated image.
     *
     * @return void
     */
    private static function deleteStatus(): void
    {
        $session = SessionManager::getInstance();
        $accountName = ValidationHelper::sanitizeString($_POST['account'] ?? '', 'alphanumeric-dash');
        if (empty($accountName)) {
            MessageHelper::addMessage('Invalid account name.');
            return;
        }
        $accountOwner = $session->get('username');
        if (isset($_POST['username']) && $accountOwner !== trim($_POST['username'])) {
            MessageHelper::addMessage('Username mismatch.');
            return;
        }
        $statusId = ValidationHelper::validateInteger($_POST['id'] ?? '');
        if ($statusId === null) {
            MessageHelper::addMessage('Invalid status ID.');
            return;
        }
        try {
            $statusImagePath = StatusModel::getStatusImagePath($statusId, $accountName, $accountOwner);
            if ($statusImagePath) {
                $baseDir = realpath(__DIR__ . '/../../public/images');
                $safeOwner = preg_replace('/[^a-zA-Z0-9_-]/', '', $accountOwner);
                $safeAccount = preg_replace('/[^a-zA-Z0-9_-]/', '', $accountName);
                $safeImage = basename($statusImagePath);
                $imagePath = $baseDir . '/' . $safeOwner . '/' . $safeAccount . '/' . $safeImage;
                $realImagePath = realpath($imagePath);
                if ($realImagePath && str_starts_with($realImagePath, $baseDir) && file_exists($realImagePath)) {
                    unlink($realImagePath);
                }
            }
            StatusModel::deleteStatus($statusId, $accountName, $accountOwner);
            MessageHelper::addMessage('Successfully deleted status.');
        } catch (\Exception $e) {
            MessageHelper::addMessage('Failed to delete status: ' . $e->getMessage());
        }
    }

    /**
     * Generate a new status for an account using the StatusService.
     *
     * @return void
     */
    private static function generateStatus(): void
    {
        $session = SessionManager::getInstance();
        $accountName = ValidationHelper::sanitizeString($_POST['account'] ?? '', 'alphanumeric-dash');
        if (empty($accountName)) {
            MessageHelper::addMessage('Invalid account name.');
            return;
        }
        $accountOwner = $session->get('username');
        if (isset($_POST['username']) && $accountOwner !== trim($_POST['username'])) {
            MessageHelper::addMessage('Username mismatch.');
            return;
        }
        try {
            $accountInfo = AccountModel::getAcctInfo($accountOwner, $accountName);
            $userInfo = UserModel::getUserInfo($accountOwner);

            if (!$accountInfo || !$userInfo) {
                MessageHelper::addMessage(sprintf(
                    'Error: Account or user not found for owner "%s" and account "%s".',
                    $accountOwner,
                    $accountName
                ));
                return;
            }

            if ($userInfo->used_api_calls >= $userInfo->max_api_calls) {
                MessageHelper::addMessage('Sorry, your available API calls have run out.');
                if (!$userInfo->limit_email_sent) {
                    Mailer::sendTemplate(
                        $userInfo->email,
                        'API Limit Reached',
                        'api_limit_reached',
                        ['username' => $userInfo->username]
                    );
                    UserModel::setLimitEmailSent($accountOwner, true);
                }
            } else {
                $scheduledAt = time();
                $queued = StatusJobModel::queuePendingJob($accountOwner, $accountName, $scheduledAt);

                if ($queued) {
                    MessageHelper::addMessage('Status generation queued. It will complete in the background.');
                } else {
                    MessageHelper::addMessage('Status generation is already queued and will complete in the background.');
                }
            }
        } catch (\Exception $e) {
            MessageHelper::addMessage('Failed to generate status: ' . $e->getMessage());
        }
    }
}
