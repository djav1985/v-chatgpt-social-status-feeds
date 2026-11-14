<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: InfoController.php
 * Description: AI Social Status Generator
 */

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use App\Core\Response;
use App\Core\ErrorManager;
use App\Models\UserModel;
use App\Core\SessionManager;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;

class InfoController
{
    /**
     * Display user profile information and system message.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $session = SessionManager::getInstance();
        $systemMsg = self::buildSystemMessage($session->get('username'));

        return Response::view('info', [
            'systemMsg' => $systemMsg,
        ]);
    }

    /**
     * Handle profile update and password change requests.
     *
     * @return Response
     */
    public function handleSubmission(ServerRequestInterface $request): Response
    {
        $requestBody = $request->getParsedBody();
        $requestBody = is_array($requestBody) ? $requestBody : [];
        if (isset($requestBody['change_password'])) {
            $this->processPasswordChange($requestBody);
            return Response::redirect('/info');
        }

        if (isset($requestBody['update_profile'])) {
            $this->processProfileUpdate($requestBody);
            return Response::redirect('/info');
        }

        return Response::redirect('/info');
    }

    /**
     * Compose the system message shown on the profile page.
     *
     * @param string $username Username to build the message for
     * @return array{
     *     systemMsg: string,
     *     who: string,
     *     where: string,
     *     what: string,
     *     goal: string,
     *     hasProfile: bool
     * }
     */
    private static function buildSystemMessage(string $username): array
    {
        $userInfo = UserModel::getUserInfo($username);
        return [
            'systemMsg' => SYSTEM_MSG,
            'who' => $userInfo ? (string) $userInfo->who : '',
            'where' => $userInfo ? (string) $userInfo->where : '',
            'what' => $userInfo ? (string) $userInfo->what : '',
            'goal' => $userInfo ? (string) $userInfo->goal : '',
            'hasProfile' => (bool) $userInfo,
        ];
    }

    /**
     * Validate and update the current user's password.
     *
     * @param array<string, mixed> $requestBody
     * @return void
     */
    private function processPasswordChange(array $requestBody): void
    {
        $session = SessionManager::getInstance();
        $username = $session->get('username');
        // Fix: Don't sanitize passwords - only cast to string
        $password = (string) ($requestBody['password'] ?? '');
        $password2 = (string) ($requestBody['password2'] ?? '');

        if ($password !== $password2) {
            MessageHelper::addMessage('Passwords do not match. Please try again.');
        }

        $errors = ValidationHelper::validateUser(['username' => $username, 'password' => $password, 'email' => '']);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                if (str_contains($error, 'Password')) {
                    MessageHelper::addMessage($error);
                }
            }
        }

        if (!empty($session->get('messages'))) {
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            UserModel::updatePassword($username, $hashedPassword);
            MessageHelper::addMessage('Password Updated!');
        } catch (\Exception $e) {
            ErrorManager::getInstance()->log('Password update failed: ' . $e->getMessage(), 'error');
            MessageHelper::addMessage('Password update failed. Please try again.');
        }
    }

    /**
     * Update the user's profile information from form submission.
     *
     * @param array<string, mixed> $requestBody
     * @return void
     */
    private function processProfileUpdate(array $requestBody): void
    {
        $session = SessionManager::getInstance();
        $username = $session->get('username');
        $who = ValidationHelper::sanitizeString($requestBody['who'] ?? '', 'text');
        $where = ValidationHelper::sanitizeString($requestBody['where'] ?? '', 'text');
        $what = ValidationHelper::sanitizeString($requestBody['what'] ?? '', 'text');
        $goal = ValidationHelper::sanitizeString($requestBody['goal'] ?? '', 'text');

        if (empty($who) || empty($where) || empty($what) || empty($goal)) {
            MessageHelper::addMessage('All fields are required.');
            return;
        }

        try {
            UserModel::updateProfile($username, $who, $where, $what, $goal);
            MessageHelper::addMessage('Profile Updated!');
        } catch (\Exception $e) {
            ErrorManager::getInstance()->log('Profile update failed: ' . $e->getMessage(), 'error');
            MessageHelper::addMessage('Profile update failed. Please try again.');
        }
    }
}
