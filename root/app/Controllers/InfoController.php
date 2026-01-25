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
use App\Models\User;
use Respect\Validation\Validator;
use App\Core\SessionManager;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;

class InfoController extends Controller
{
    /**
     * Display user profile information and system message.
     *
     * @return void
     */
    public function handleRequest(): void
    {
        $session = SessionManager::getInstance();
        $profileData = self::generateProfileDataAttributes($session->get('username'));
        $systemMsg = self::buildSystemMessage($session->get('username'));

        $this->render('info', [
            'profileData' => $profileData,
            'systemMsg' => $systemMsg,
        ]);
    }

    /**
     * Handle profile update and password change requests.
     *
     * @return void
     */
    public function handleSubmission(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $session = SessionManager::getInstance();
        if (!ValidationHelper::validateCsrfToken($token)) {
            MessageHelper::addMessage('Invalid CSRF token. Please try again.');
            header('Location: /info');
            exit;
        }

        if (isset($_POST['change_password'])) {
            self::processPasswordChange();
            return;
        }

        if (isset($_POST['update_profile'])) {
            self::processProfileUpdate();
            return;
        }

        header('Location: /info');
        exit;
    }

    /**
     * Build data attributes for the profile form from stored info.
     *
     * @param string $username Username to load data for
     * @return string Attribute string
     */
    private static function generateProfileDataAttributes(string $username): string
    {
        $userInfo = User::getUserInfo($username);
        if ($userInfo) {
            $data = "data-who=\"" . htmlspecialchars($userInfo->who, ENT_QUOTES) . "\" ";
            $data .= "data-where=\"" . htmlspecialchars($userInfo->where, ENT_QUOTES) . "\" ";
            $data .= "data-what=\"" . htmlspecialchars($userInfo->what, ENT_QUOTES) . "\" ";
            $data .= "data-goal=\"" . htmlspecialchars($userInfo->goal, ENT_QUOTES) . "\"";
            return $data;
        }
        return '';
    }

    /**
     * Compose the system message shown on the profile page.
     *
     * @param string $username Username to build the message for
     * @return string Formatted HTML message
     */
    private static function buildSystemMessage(string $username): string
    {
        $userInfo = User::getUserInfo($username);
        if ($userInfo) {
            $systemMessage = "<span style=\"color: blue; font-weight: bold;\">" . self::escapeSystemMessage(SYSTEM_MSG) . "</span>";
            $systemMessage .= " <span style=\"color: blue; font-weight: bold;\">You work for</span> " . htmlspecialchars($userInfo->who, ENT_QUOTES) . " <span style=\"color: blue; font-weight: bold;\">located in</span> " . htmlspecialchars($userInfo->where, ENT_QUOTES) . ". " . htmlspecialchars($userInfo->what, ENT_QUOTES) . " <span style=\"color: blue; font-weight: bold;\">Your goal is</span> " . htmlspecialchars($userInfo->goal, ENT_QUOTES) . ".";
            return $systemMessage;
        }
        return "<span style=\"color: blue; font-weight: bold;\">" . self::escapeSystemMessage(SYSTEM_MSG) . "</span>";
    }

    /**
     * Escape system message content for safe rendering.
     */
    private static function escapeSystemMessage(string $message): string
    {
        return htmlspecialchars($message, ENT_QUOTES);
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
            header('Location: /info');
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            User::updatePassword($username, $hashedPassword);
            MessageHelper::addMessage('Password Updated!');
        } catch (\Exception $e) {
            MessageHelper::addMessage('Password update failed: ' . $e->getMessage());
        }
        header('Location: /info');
        exit;
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
            header('Location: /info');
            exit;
        }

        try {
            User::updateProfile($username, $who, $where, $what, $goal);
            MessageHelper::addMessage('Profile Updated!');
        } catch (\Exception $e) {
            MessageHelper::addMessage('Profile update failed: ' . $e->getMessage());
        }
        header('Location: /info');
        exit;
    }
}
