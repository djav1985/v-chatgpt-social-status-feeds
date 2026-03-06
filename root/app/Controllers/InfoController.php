<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

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

use App\Core\Controller;
use App\Core\Response;
use App\Models\UserModel;
use App\Core\SessionManager;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;

class InfoController extends Controller
{
    /**
     * Display user profile information and system message.
     *
     * @return Response
     */
    public function handleRequest(): Response
    {
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
    public function handleSubmission(): Response
    {
        $token = $_POST['csrf_token'] ?? '';
        $session = SessionManager::getInstance();
        if (!ValidationHelper::validateCsrfToken($token)) {
            MessageHelper::addMessage('Invalid CSRF token. Please try again.');
            return Response::redirect('/info');
        }

        if (isset($_POST['change_password'])) {
            self::processPasswordChange();
            return Response::redirect('/info');
        }

        if (isset($_POST['update_profile'])) {
            self::processProfileUpdate();
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
    private static function processPasswordChange(): void
    {
        $session = SessionManager::getInstance();
        $username = $session->get('username');
        // Fix: Don't sanitize passwords - only cast to string
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');

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
            MessageHelper::addMessage('Password update failed: ' . $e->getMessage());
        }
    }

    /**
     * Update the user's profile information from form submission.
     *
     * @return void
     */
    private static function processProfileUpdate(): void
    {
        $session = SessionManager::getInstance();
        $username = $session->get('username');
        $who = ValidationHelper::sanitizeString($_POST['who'] ?? '', 'text');
        $where = ValidationHelper::sanitizeString($_POST['where'] ?? '', 'text');
        $what = ValidationHelper::sanitizeString($_POST['what'] ?? '', 'text');
        $goal = ValidationHelper::sanitizeString($_POST['goal'] ?? '', 'text');

        if (empty($who) || empty($where) || empty($what) || empty($goal)) {
            MessageHelper::addMessage('All fields are required.');
            return;
        }

        try {
            UserModel::updateProfile($username, $who, $where, $what, $goal);
            MessageHelper::addMessage('Profile Updated!');
        } catch (\Exception $e) {
            MessageHelper::addMessage('Profile update failed: ' . $e->getMessage());
        }
    }
}
