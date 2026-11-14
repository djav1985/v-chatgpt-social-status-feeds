<?php

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

use Psr\Http\Message\ServerRequestInterface;
use App\Core\Mailer;
use App\Core\ErrorManager;
use App\Core\Response;
use App\Models\AccountModel;
use App\Models\StatusJobModel;
use App\Models\UserModel;
use App\Models\StatusModel;
use App\Core\SessionManager;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;

class HomeController
{
    /**
     * Display the dashboard showing accounts and recent statuses.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $accountOwner = (string) SessionManager::getInstance()->get('username', '');
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
                $imageName = (string) ($status->status_image ?? '');
                if ($imageName !== '') {
                    $imageSegments = [
                        rawurlencode($accountOwner),
                        rawurlencode((string) $name),
                        rawurlencode(basename($imageName)),
                    ];
                    $displayImage = '/images/' . implode('/', $imageSegments);
                    $imageUrl = DOMAIN . $displayImage;
                } else {
                    $displayImage = '/assets/images/default.png';
                    $imageUrl = DOMAIN . $displayImage;
                }
                $statusList[] = [
                    'status' => (string) ($status->status ?? ''),
                    'status_image' => $imageName,
                    'display_image' => $displayImage,
                    'image_url' => $imageUrl,
                    'created_at' => (string) ($status->created_at ?? ''),
                    'id' => (int) ($status->id ?? 0),
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
            'csrfToken' => (string) SessionManager::getInstance()->get('csrf_token', ''),
            'accountsData' => $accountsData,
        ]);
    }

    /**
     * Process requests to generate or delete statuses.
     *
     * @return Response
     */
    public function handleSubmission(ServerRequestInterface $request): Response
    {
        $requestBody = $request->getParsedBody();
        $requestBody = is_array($requestBody) ? $requestBody : [];
        if (isset($requestBody['delete_status'])) {
            $this->deleteStatus($requestBody);
            return Response::redirect('/home');
        }

        if (isset($requestBody['generate_status'])) {
            $this->generateStatus($requestBody);
            return Response::redirect('/home');
        }

        return Response::redirect('/home');
    }

    /**
     * Delete a status entry and its associated image.
     *
     * @param array<string, mixed> $requestBody
     * @return void
     */
    private function deleteStatus(array $requestBody): void
    {
        $session = SessionManager::getInstance();
        $accountName = ValidationHelper::sanitizeString($requestBody['account'] ?? '', 'alphanumeric-dash');
        if (empty($accountName)) {
            MessageHelper::addMessage('Invalid account name.');
            return;
        }
        $accountOwner = $session->get('username');
        if (isset($requestBody['username']) && $accountOwner !== trim((string) $requestBody['username'])) {
            MessageHelper::addMessage('Username mismatch.');
            return;
        }
        $statusId = ValidationHelper::validateInteger($requestBody['id'] ?? '');
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
            ErrorManager::getInstance()->log('Failed to delete status: ' . $e->getMessage(), 'error');
            MessageHelper::addMessage('Failed to delete status. Please try again.');
        }
    }

    /**
     * Generate a new status for an account using the StatusService.
     *
     * @param array<string, mixed> $requestBody
     * @return void
     */
    private function generateStatus(array $requestBody): void
    {
        $session = SessionManager::getInstance();
        $accountName = ValidationHelper::sanitizeString($requestBody['account'] ?? '', 'alphanumeric-dash');
        if (empty($accountName)) {
            MessageHelper::addMessage('Invalid account name.');
            return;
        }
        $accountOwner = $session->get('username');
        if (isset($requestBody['username']) && $accountOwner !== trim((string) $requestBody['username'])) {
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
                    MessageHelper::addMessage(
                        'Status generation queued. It will run on the next scheduled queue worker.'
                    );
                } else {
                    MessageHelper::addMessage(
                        'Status generation is already queued for the next scheduled queue worker.'
                    );
                }
            }
        } catch (\Exception $e) {
            ErrorManager::getInstance()->log('Failed to queue status generation: ' . $e->getMessage(), 'error');
            MessageHelper::addMessage('Failed to queue status generation. Please try again.');
        }
    }
}
