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
    use RequestContext;

    /**
     * Display user profile information and system message.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $this->captureRequest($request);
        $session = SessionManager::getInstance();
        $profileData = self::generateProfileDataAttributes($session->get('username'));
        $systemMsg = self::buildSystemMessage($session->get('username'));

        return Response::view('info', [
            'profileData' => $profileData,
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
        $this->captureRequest($request);
        $session = SessionManager::getInstance();
        if (isset($this->requestBody['change_password'])) {
            $this->processPasswordChange();
            return Response::redirect('/info');
        }

        if (isset($this->requestBody['update_profile'])) {
            $this->processProfileUpdate();
            return Response::redirect('/info');
        }

        return Response::redirect('/info');
    }

    /**
     * Build data attributes for the profile form from stored info.
     *
     * @param string $username Username to load data for
     * @return string Attribute string
     */
    private static function generateProfileDataAttributes(string $username): string
    {
        $userInfo = UserModel::getUserInfo($username);
        if ($userInfo) {
            $data = "data-who=\"" . ValidationHelper::escapeOutput($userInfo->who) . "\" ";
            $data .= "data-where=\"" . ValidationHelper::escapeOutput($userInfo->where) . "\" ";
            $data .= "data-what=\"" . ValidationHelper::escapeOutput($userInfo->what) . "\" ";
            $data .= "data-goal=\"" . ValidationHelper::escapeOutput($userInfo->goal) . "\"";
            return $data;
        }
        return '';
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
     * @return void
     */
    private function processPasswordChange(): void
    {
        $session = SessionManager::getInstance();
        $username = $session->get('username');
        // Fix: Don't sanitize passwords - only cast to string
        $password = (string) ($this->requestBody['password'] ?? '');
        $password2 = (string) ($this->requestBody['password2'] ?? '');

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
     * @return void
     */
    private function processProfileUpdate(): void
    {
        $session = SessionManager::getInstance();
        $username = $session->get('username');
        $who = ValidationHelper::sanitizeString($this->requestBody['who'] ?? '', 'text');
        $where = ValidationHelper::sanitizeString($this->requestBody['where'] ?? '', 'text');
        $what = ValidationHelper::sanitizeString($this->requestBody['what'] ?? '', 'text');
        $goal = ValidationHelper::sanitizeString($this->requestBody['goal'] ?? '', 'text');

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
