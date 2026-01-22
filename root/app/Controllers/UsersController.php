<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

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

use App\Core\Controller;
use App\Core\Mailer;
use App\Models\User;
use function random_bytes;
use App\Core\Csrf;
use App\Core\SessionManager;
use Respect\Validation\Validator;
use App\Helpers\MessageHelper;
use App\Helpers\Validation;

class UsersController extends Controller
{
    /**
     * Display the user management page for administrators.
     *
     * @return void
     */
    public function handleRequest(): void
    {

        $session = SessionManager::getInstance();
        if (!$session->get('is_admin')) {
            http_response_code(403);
            exit('Forbidden');
        }


        $userList = self::generateUserList();

        $this->render('users', [
            'userList' => $userList,
        ]);
    }

    /**
     * Process create, delete and impersonation actions for users.
     *
     * @return void
     */
    public function handleSubmission(): void
    {
        $session = SessionManager::getInstance();
        if (!$session->get('is_admin')) {
            http_response_code(403);
            exit('Forbidden');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            MessageHelper::addMessage('Invalid CSRF token. Please try again.');
            header('Location: /users');
            exit;
        }

        if (isset($_POST['edit_users'])) {
            self::editUsers();
            return;
        }

        if (isset($_POST['delete_user']) && isset($_POST['username'])) {
            self::deleteUser();
            return;
        }

        if (isset($_POST['login_as']) && isset($_POST['username'])) {
            self::loginAs();
            return;
        }

        header('Location: /users');
        exit;
    }

    /**
     * Create or update a user based on form input.
     *
     * @return void
     */
    private static function editUsers(): void
    {
        $session = SessionManager::getInstance();
        $username = Validation::sanitizeString($_POST['username'] ?? '');
        $password = Validation::sanitizeString($_POST['password'] ?? '');
        $plainPassword = $password;
        $email = Validation::sanitizeString($_POST['email'] ?? '');
        $totalAccounts = Validation::validateInteger($_POST['total-accounts'] ?? 0, 0);
        $maxApiCalls = Validation::validateInteger($_POST['max-api-calls'] ?? 0, 0);
        $usedApiCalls = Validation::validateInteger($_POST['used-api-calls'] ?? 0, 0);
        $expires = Validation::sanitizeString($_POST['expires'] ?? '');
        $admin = Validation::validateInteger($_POST['admin'] ?? 0, 0);

        // Centralized validation
        $userValidationErrors = Validation::validateUser([
            'username' => $username,
            'password' => $password,
            'email' => $email,
        ]);

        foreach ($userValidationErrors as $err) {
            MessageHelper::addMessage($err);
        }

        if (!empty($session->get('messages'))) {
            header('Location: /users');
            exit;
        }

        try {
            $userExists = User::userExists($username);
            if (!$userExists && empty($password)) {
                MessageHelper::addMessage('Password is required for new users.');
                header('Location: /users');
                exit;
            }
            if (!empty($password) && (!$userExists || !password_verify($password, $userExists->password))) {
                $password = password_hash($password, PASSWORD_DEFAULT);
            } elseif ($userExists) {
                $password = $userExists->password;
            }
            $isUpdate = $userExists !== null;
            $result = User::updateUser(
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
            MessageHelper::addMessage('Failed to create or modify user: ' . $e->getMessage());
        }
        header('Location: /users');
        exit;
    }

    /**
     * Delete a user account if not deleting oneself.
     *
     * @return void
     */
    private static function deleteUser(): void
    {
        $session = SessionManager::getInstance();
        $username = Validation::sanitizeString($_POST['username'] ?? '');
        if ($username === $session->get('username')) {
            MessageHelper::addMessage("Sorry, you can't delete your own account.");
        } else {
            try {
                User::deleteUser($username);
                MessageHelper::addMessage('User Deleted');
            } catch (\Exception $e) {
                MessageHelper::addMessage('Failed to delete user: ' . $e->getMessage());
            }
        }
        header('Location: /users');
        exit;
    }

    /**
     * Log in as another user for administrative impersonation.
     *
     * @return void
     */
    private static function loginAs(): void
    {
        $session = SessionManager::getInstance();
        $username = Validation::sanitizeString($_POST['username'] ?? '');
        try {
            $user = User::getUserInfo($username);
            if ($user) {
                if (!$session->get('isReally')) {
                    $session->set('isReally', $session->get('username'));
                }
                $session->set('username', $user->username);
                $session->set('logged_in', true);
                $session->set('user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
                $session->set('csrf_token', bin2hex(\random_bytes(32)));
                $session->set('is_admin', $user->admin);
                $session->set('timeout', time());
                $session->regenerate();
                header('Location: /home');
                exit;
            } else {
                MessageHelper::addMessage('Failed to login as user.');
            }
        } catch (\Exception $e) {
            MessageHelper::addMessage('Failed to login as user: ' . $e->getMessage());
        }
        header('Location: /users');
        exit;
    }
    /**
     * Generate the HTML list of all users for the admin panel.
     *
     * @return string Rendered list items
     */
    private static function generateUserList(): string
    {
        $users = User::getAllUsers();
        $output = '';
        foreach ($users as $user) {
            $user = (object)$user;
            $dataAttributes  = "data-username=\"" . htmlspecialchars($user->username) . "\" ";
            $dataAttributes .= "data-email=\"" . htmlspecialchars($user->email) . "\" ";
            $dataAttributes .= "data-admin=\"" . htmlspecialchars($user->admin) . "\" ";
            $dataAttributes .= "data-total-accounts=\"" . htmlspecialchars($user->total_accounts) . "\" ";
            $dataAttributes .= "data-max-api-calls=\"" . htmlspecialchars($user->max_api_calls) . "\" ";
            $dataAttributes .= "data-used-api-calls=\"" . htmlspecialchars($user->used_api_calls) . "\" ";
            $dataAttributes .= "data-expires=\"" . htmlspecialchars($user->expires) . "\" ";

            ob_start();
            $viewData = [
                'user' => $user,
                'dataAttributes' => $dataAttributes,
            ];
            extract($viewData);
            include __DIR__ . '/../Views/partials/user-list-item.php';
            $output .= ob_get_clean();
        }
        return $output;
    }
}
