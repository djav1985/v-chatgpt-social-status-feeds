<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\AuthMiddleware;
use App\Models\UserHandler;

class UsersController extends Controller
{
    public static function handleRequest(): void
    {
        AuthMiddleware::checkSession();

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
                    $userExists = UserHandler::userExists($username);
                    if (!empty($password) && (!$userExists || !password_verify($password, $userExists->password))) {
                        $password = password_hash($password, PASSWORD_DEFAULT);
                    } elseif ($userExists) {
                        $password = $userExists->password;
                    }
                    $isUpdate = $userExists !== null;
                    $result = UserHandler::updateUser($username, $password, $totalAccounts, $maxApiCalls, $usedApiCalls, $expires, $admin, $isUpdate);
                    if ($result) {
                        if (!$userExists) {
                            $userImagePath = __DIR__ . '/../../public/images/' . $username;
                            if (!file_exists($userImagePath)) {
                                mkdir($userImagePath, 0755, true);
                                $indexFilePath = $userImagePath . '/index.php';
                                file_put_contents($indexFilePath, '<?php die(); ?>');
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
                        UserHandler::deleteUser($username);
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
                    $user = UserHandler::getUserInfo($username);
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

        $this->render('users');
    }
}
