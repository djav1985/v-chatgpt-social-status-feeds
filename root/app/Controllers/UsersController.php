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
use App\Core\AuthMiddleware;
use App\Models\User;

class UsersController extends Controller
{
    public static function handleRequest(): void
    {
        AuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
                header('Location: /users');
                exit;
            }

            if (isset($_POST['edit_users'])) {
                $username = trim($_POST['username']);
                $password = trim($_POST['password']);
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

                if (!empty($_SESSION['messages'])) {
                    header('Location: /users');
                    exit;
                }

                try {
                    $userExists = User::userExists($username);
                    if (!empty($password) && (!$userExists || !password_verify($password, $userExists->password))) {
                        $password = password_hash($password, PASSWORD_DEFAULT);
                    } elseif ($userExists) {
                        $password = $userExists->password;
                    }
                    $isUpdate = $userExists !== null;
                    $result = User::updateUser($username, $password, $totalAccounts, $maxApiCalls, $usedApiCalls, $expires, $admin, $isUpdate);
                    if ($result) {
                        if (!$userExists) {
                            $userImagePath = __DIR__ . '/../../public/images/' . $username;
                            if (!file_exists($userImagePath)) {
                                mkdir($userImagePath, 0755, true);
                                $indexFilePath = $userImagePath . '/index.php';
                                file_put_contents($indexFilePath, '<?php
 die(); ?>');
                            }
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
            } elseif (isset($_POST['delete_user']) && isset($_POST['username'])) {
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
            } elseif (isset($_POST['login_as']) && isset($_POST['username'])) {
                $username = $_POST['username'];
                try {
                    $user = User::getUserInfo($username);
                    if ($user) {
                        if (!isset($_SESSION['isReally'])) {
                            $_SESSION['isReally'] = $_SESSION['username'];
                        }
                        $_SESSION['username'] = $user->username;
                        $_SESSION['logged_in'] = true;
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
        }

        $userList = self::generateUserList();

        (new self())->render('users', [
            'userList' => $userList,
        ]);
    }

    public static function generateUserList(): string
    {
        $users = User::getAllUsers();
        $output = '';
        foreach ($users as $user) {
            $dataAttributes  = "data-username=\"" . htmlspecialchars($user->username) . "\" ";
            $dataAttributes .= "data-admin=\"" . htmlspecialchars($user->admin) . "\" ";
            $dataAttributes .= "data-total-accounts=\"" . htmlspecialchars($user->total_accounts) . "\" ";
            $dataAttributes .= "data-max-api-calls=\"" . htmlspecialchars($user->max_api_calls) . "\" ";
            $dataAttributes .= "data-used-api-calls=\"" . htmlspecialchars($user->used_api_calls) . "\" ";
            $dataAttributes .= "data-expires=\"" . htmlspecialchars($user->expires) . "\" ";

            $output .= "<div class=\"column col-6 col-xl-12 col-md-12 col-sm-12\">";
            $output .= "<div class=\"card account-list-card\">";
            $output .= "<div class=\"card-header account-card\">";
            $output .= "<div class=\"card-title h5\">" . htmlspecialchars($user->username) . "</div>";
            $output .= "<br>";
            $output .= "<p><strong>Max API Calls:</strong> " . htmlspecialchars($user->max_api_calls) . "</p>";
            $output .= "<p><strong>Used API Calls:</strong> " . htmlspecialchars($user->used_api_calls) . "</p>";
            $output .= "<p><strong>Expires:</strong> " . htmlspecialchars($user->expires) . "</p>";
            $output .= '</div>';
            $output .= "<div class=\"card-body button-group\">";
            $output .= "<button class=\"btn btn-primary\" id=\"update-btn\" " . $dataAttributes . ">Update</button>";
            $output .= "<form class=\"delete-user-form\" action=\"/users\" method=\"POST\">";
            $output .= "<input type=\"hidden\" name=\"username\" value=\"" . htmlspecialchars($user->username) . "\">";
            $output .= "<input type=\"hidden\" name=\"csrf_token\" value=\"" . htmlspecialchars($_SESSION['csrf_token']) . "\">";
            $output .= "<button class=\"btn btn-error\" name=\"delete_user\">Delete</button>";
            $output .= "</form>";
            if ($user->username !== $_SESSION['username']) {
                $output .= "<form class=\"login-as-form\" action=\"/users\" method=\"POST\">";
                $output .= "<input type=\"hidden\" name=\"username\" value=\"" . htmlspecialchars($user->username) . "\">";
                $output .= "<input type=\"hidden\" name=\"csrf_token\" value=\"" . htmlspecialchars($_SESSION['csrf_token']) . "\">";
                $output .= "<button class=\"btn btn-primary\" name=\"login_as\">Login</button>";
                $output .= "</form>";
            }
            $output .= "</div></div></div>";
        }
        return $output;
    }
}
