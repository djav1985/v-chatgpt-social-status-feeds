<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: UsersController.php
 * Description: AI Social Status Generator
 */

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use App\Core\Mailer;
use App\Core\ErrorManager;
use App\Core\Response;
use App\Models\UserModel;
use App\Core\SessionManager;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;

class UsersController
{
    use RequestContext;

    /**
     * Display the user management page for administrators.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $this->captureRequest($request);

        $session = SessionManager::getInstance();
        if (!$session->get('is_admin')) {
            return Response::text('Forbidden', 403);
        }


        return Response::view('users', ['users' => array_map(fn($user) => (array)$user, UserModel::getAllUsers())]);
    }

    /**
     * Process create, delete and impersonation actions for users.
     *
     * @return Response
     */
    public function handleSubmission(ServerRequestInterface $request): Response
    {
        $this->captureRequest($request);
        $session = SessionManager::getInstance();
        if (!$session->get('is_admin')) {
            return Response::text('Forbidden', 403);
        }

        if (isset($this->requestBody['edit_users'])) {
            $this->editUsers();
            return Response::redirect('/users');
        }

        if (isset($this->requestBody['delete_user']) && isset($this->requestBody['username'])) {
            $this->deleteUser();
            return Response::redirect('/users');
        }

        if (isset($this->requestBody['login_as']) && isset($this->requestBody['username'])) {
            if ($this->loginAs()) {
                return Response::redirect('/home');
            }
            return Response::redirect('/users');
        }

        return Response::redirect('/users');
    }

    /**
     * Create or update a user based on form input.
     *
     * @return void
     */
    private function editUsers(): void
    {
        $session = SessionManager::getInstance();
        $username = ValidationHelper::sanitizeString($this->requestBody['username'] ?? '');
        $password = (string) ($this->requestBody['password'] ?? '');
        $plainPassword = $password;
        // Email addresses contain punctuation that the generic sanitizer would strip.
        $email = trim((string) ($this->requestBody['email'] ?? ''));
        // Fix: Handle null returns with default values
        $totalAccounts = ValidationHelper::validateInteger($this->requestBody['total-accounts'] ?? 0, 0) ?? 0;
        $maxApiCalls = ValidationHelper::validateInteger($this->requestBody['max-api-calls'] ?? 0, 0) ?? 0;
        $usedApiCalls = ValidationHelper::validateInteger($this->requestBody['used-api-calls'] ?? 0, 0) ?? 0;
        $expires = ValidationHelper::sanitizeString($this->requestBody['expires'] ?? '');
        $admin = ValidationHelper::validateInteger($this->requestBody['admin'] ?? 0, 0) ?? 0;

        // Centralized validation
        $userValidationErrors = ValidationHelper::validateUser([
            'username' => $username,
            'password' => $password,
            'email' => $email,
        ]);

        foreach ($userValidationErrors as $err) {
            MessageHelper::addMessage($err);
        }

        if (!empty($session->get('messages'))) {
            return;
        }

        try {
            $userExists = UserModel::userExists($username);
            if (!$userExists && empty($password)) {
                MessageHelper::addMessage('Password is required for new users.');
                return;
            }
            if (!empty($password) && (!$userExists || !password_verify($password, $userExists->password))) {
                $password = password_hash($password, PASSWORD_DEFAULT);
            } elseif ($userExists) {
                // An empty password on an existing account means keep its current hash.
                $password = $userExists->password;
            }
            $isUpdate = $userExists !== null;
            $result = UserModel::updateUser(
                $username,
                $password,
                $email,
                $totalAccounts,
                $maxApiCalls,
                $usedApiCalls,
                $expires,
                $admin,
                $isUpdate
            );
            if ($result) {
                if (!$userExists) {
                    $userImagePath = __DIR__ . '/../../public/images/' . $username;
                    if (!file_exists($userImagePath)) {
                        mkdir(
                            $userImagePath,
                            defined('DIR_MODE') ? DIR_MODE : 0755,
                            true
                        );
                        $indexFilePath = $userImagePath . '/index.php';
                        file_put_contents($indexFilePath, '<?php die(); ?>');
                    }
                    Mailer::sendTemplate(
                        $email,
                        'Login Details',
                        'new_user',
                        [
                            'username' => $username,
                            'password' => $plainPassword,
                        ]
                    );
                }
                MessageHelper::addMessage('User has been created or modified.');
            } else {
                MessageHelper::addMessage('Failed to create or modify user.');
            }
        } catch (\Exception $e) {
            ErrorManager::getInstance()->log('Failed to create or modify user: ' . $e->getMessage(), 'error');
            MessageHelper::addMessage('Failed to create or modify user. Please try again.');
        }
    }

    /**
     * Delete a user account if not deleting oneself.
     *
     * @return void
     */
    private function deleteUser(): void
    {
        $session = SessionManager::getInstance();
        $username = ValidationHelper::sanitizeString($this->requestBody['username'] ?? '');
        if ($username === $session->get('username')) {
            MessageHelper::addMessage("Sorry, you can't delete your own account.");
        } else {
            try {
                UserModel::deleteUser($username);
                MessageHelper::addMessage('User Deleted');
            } catch (\Exception $e) {
                ErrorManager::getInstance()->log('Failed to delete user: ' . $e->getMessage(), 'error');
                MessageHelper::addMessage('Failed to delete user. Please try again.');
            }
        }
    }

    /**
     * Log in as another user for administrative impersonation.
     *
     * @return bool True when impersonation succeeded, otherwise false
     */
    private function loginAs(): bool
    {
        $session = SessionManager::getInstance();
        $username = ValidationHelper::sanitizeString($this->requestBody['username'] ?? '');
        try {
            $user = UserModel::getUserInfo($username);
            if ($user) {
                if (!$session->get('isReally')) {
                    $session->set('isReally', $session->get('username'));
                }
                $session->set('username', $user->username);
                $session->set('logged_in', true);
                $session->set('user_agent', $this->requestServer['HTTP_USER_AGENT'] ?? '');
                $session->set('csrf_token', bin2hex(\App\Helpers\EncryptionHelper::bytes(32)));
                $session->set('is_admin', $user->admin);
                $session->set('timeout', time());
                $session->regenerate();
                return true;
            } else {
                MessageHelper::addMessage('Failed to login as user.');
            }
        } catch (\Exception $e) {
            ErrorManager::getInstance()->log('Failed to impersonate user: ' . $e->getMessage(), 'error');
            MessageHelper::addMessage('Failed to login as user.');
        }

        return false;
    }
}
