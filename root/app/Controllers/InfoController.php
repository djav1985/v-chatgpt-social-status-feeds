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
use App\Core\Csrf;
use Respect\Validation\Validator;

class InfoController extends Controller
{
    /**
     * Display user profile information and system message.
     *
     * @return void
     */
    public function handleRequest(): void
    {
        $profileData = self::generateProfileDataAttributes($_SESSION['username']);
        $systemMsg = self::buildSystemMessage($_SESSION['username']);

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
        if (!Csrf::validate($token)) {
            $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
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
     * Validate and update the current user's password.
     *
     * @return void
     */
    private static function processPasswordChange(): void
    {
        $username = $_SESSION['username'];
        $password = $_POST['password'];
        $password2 = $_POST['password2'];

        if ($password !== $password2) {
            $_SESSION['messages'][] = 'Passwords do not match. Please try again.';
        }

        if (!Validator::regex('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,16}$/')->validate($password)) {
            $_SESSION['messages'][] = 'Password must be 8-16 characters long, including at least one letter, one number, and one symbol.';
        }

        if (!empty($_SESSION['messages'])) {
            header('Location: /info');
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            User::updatePassword($username, $hashedPassword);
            $_SESSION['messages'][] = 'Password Updated!';
        } catch (\Exception $e) {
            $_SESSION['messages'][] = 'Password update failed: ' . $e->getMessage();
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
        $username = $_SESSION['username'];
        $who = trim($_POST['who']);
        $where = trim($_POST['where']);
        $what = trim($_POST['what']);
        $goal = trim($_POST['goal']);

        if (empty($who) || empty($where) || empty($what) || empty($goal)) {
            $_SESSION['messages'][] = 'All fields are required.';
            header('Location: /info');
            exit;
        }

        try {
            User::updateProfile($username, $who, $where, $what, $goal);
            $_SESSION['messages'][] = 'Profile Updated!';
        } catch (\Exception $e) {
            $_SESSION['messages'][] = 'Profile update failed: ' . $e->getMessage();
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
            $data = "data-who=\"" . htmlspecialchars($userInfo->who) . "\" ";
            $data .= "data-where=\"" . htmlspecialchars($userInfo->where) . "\" ";
            $data .= "data-what=\"" . htmlspecialchars($userInfo->what) . "\" ";
            $data .= "data-goal=\"" . htmlspecialchars($userInfo->goal) . "\"";
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
            $systemMessage = "<span style=\"color: blue; font-weight: bold;\">" . SYSTEM_MSG . "</span>";
            $systemMessage .= " <span style=\"color: blue; font-weight: bold;\">You work for</span> " . htmlspecialchars($userInfo->who) . " <span style=\"color: blue; font-weight: bold;\">located in</span> " . htmlspecialchars($userInfo->where) . ". " . htmlspecialchars($userInfo->what) . " <span style=\"color: blue; font-weight: bold;\">Your goal is</span> " . htmlspecialchars($userInfo->goal) . ".";
            return $systemMessage;
        }
        return "<span style=\"color: blue; font-weight: bold;\">" . SYSTEM_MSG . "</span>";
    }
}
