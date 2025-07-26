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
use App\Core\Csrf;

class UsersController extends Controller
{
    public function handleRequest(): void
    {

        if (empty($_SESSION['is_admin'])) {
            http_response_code(403);
            exit('Forbidden');
        }


        $userList = self::generateUserList();

        $this->render('users', [
            'userList' => $userList,
        ]);
    }

    public function handleSubmission(): void
    {
        if (empty($_SESSION['is_admin'])) {
            http_response_code(403);
            exit('Forbidden');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
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

    private static function editUsers(): void
    {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $plainPassword = $password;
        $email = trim($_POST['email']);
        $totalAccounts = intval($_POST['total-accounts']);
        $maxApiCalls = intval($_POST['max-api-calls']);
        $usedApiCalls = intval($_POST['used-api-calls']);
        $expires = trim($_POST['expires']);
        $admin = intval($_POST['admin']);

        if (!preg_match('/^[a-z0-9]{5,16}$/', $username)) {
            $_SESSION['messages'][] = 'Username must be 5-16 characters long, lowercase letters and numbers only.';
        }
        if (!empty($password) && !preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,16}$/', $password)) {
            $_SESSION['messages'][] = 'Password must be 8-16 characters long, including at least one letter, one number, and one symbol.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['messages'][] = 'Please provide a valid email address.';
        }

        if (!empty($_SESSION['messages'])) {
            header('Location: /users');
            exit;
        }

        try {
            $userExists = User::userExists($username);
            if (!$userExists && empty($password)) {
                $_SESSION['messages'][] = 'Password is required for new users.';
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
                $_SESSION['messages'][] = 'User has been created or modified.';
            } else {
                $_SESSION['messages'][] = 'Failed to create or modify user.';
            }
        } catch (\Exception $e) {
            $_SESSION['messages'][] = 'Failed to create or modify user: ' . $e->getMessage();
        }
        header('Location: /users');
        exit;
    }

    private static function deleteUser(): void
    {
        $username = $_POST['username'];
        if ($username === $_SESSION['username']) {
            $_SESSION['messages'][] = "Sorry, you can't delete your own account.";
        } else {
            try {
                User::deleteUser($username);
                $_SESSION['messages'][] = 'User Deleted';
            } catch (\Exception $e) {
                $_SESSION['messages'][] = 'Failed to delete user: ' . $e->getMessage();
            }
        }
        header('Location: /users');
        exit;
    }

    private static function loginAs(): void
    {
        $username = $_POST['username'];
        try {
            $user = User::getUserInfo($username);
            if ($user) {
                if (!isset($_SESSION['isReally'])) {
                    $_SESSION['isReally'] = $_SESSION['username'];
                }
                $_SESSION['username'] = $user->username;
                $_SESSION['logged_in'] = true;
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['is_admin'] = $user->admin;
                $_SESSION['timeout'] = time();
                session_regenerate_id(true);
                header('Location: /home');
                exit;
            } else {
                $_SESSION['messages'][] = 'Failed to login as user.';
            }
        } catch (\Exception $e) {
            $_SESSION['messages'][] = 'Failed to login as user: ' . $e->getMessage();
        }
        header('Location: /users');
        exit;
    }
    private static function generateUserList(): string
    {
        $users = User::getAllUsers();
        $output = '';
        foreach ($users as $user) {
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
