<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\AuthMiddleware;
use App\Models\AccountHandler;

class AccountsController extends Controller
{
    public static function handleRequest(): void
    {
        AuthMiddleware::checkSession();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
                header('Location: /accounts');
                exit;
            }

            if (isset($_POST['edit_account'])) {
                $accountOwner = $_SESSION['username'];
                $accountName = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', trim($_POST['account']))));
                $prompt = trim($_POST['prompt']);
                $platform = trim($_POST['platform']);
                $hashtags = isset($_POST['hashtags']) ? (int) $_POST['hashtags'] : 0;
                $link = trim($_POST['link']);
                $cron = '';
                if (isset($_POST['cron']) && is_array($_POST['cron'])) {
                    $cron = (count($_POST['cron']) === 1 && $_POST['cron'][0] === 'null') ? 'null' : implode(',', $_POST['cron']);
                }
                $days = '';
                if (isset($_POST['days']) && is_array($_POST['days'])) {
                    $days = (count($_POST['days']) === 1 && $_POST['days'][0] === 'everyday') ? 'everyday' : implode(',', $_POST['days']);
                }

                if (empty($cron) || empty($days) || empty($platform) || !isset($hashtags)) {
                    $_SESSION['messages'][] = 'Error processing input.';
                }
                if (empty($prompt)) {
                    $_SESSION['messages'][] = 'Missing required field(s).';
                }
                if (!preg_match('/^[a-z0-9-]{8,18}$/', $accountName)) {
                    $_SESSION['messages'][] = 'Account name must be 8-18 characters long, alphanumeric and hyphens only.';
                }
                if (!filter_var($link, FILTER_VALIDATE_URL)) {
                    $_SESSION['messages'][] = 'Link must be a valid URL starting with https://.';
                }

                if (!empty($_SESSION['messages'])) {
                    header('Location: /accounts');
                    exit;
                }

                try {
                    if (AccountHandler::accountExists($accountOwner, $accountName)) {
                        AccountHandler::updateAccount($accountOwner, $accountName, $prompt, $platform, $hashtags, $link, $cron, $days);
                    } else {
                        AccountHandler::createAccount($accountOwner, $accountName, $prompt, $platform, $hashtags, $link, $cron, $days);
                        $acctImagePath = __DIR__ . '/../../public/images/' . $accountOwner . '/' . $accountName;
                        if (!file_exists($acctImagePath)) {
                            mkdir($acctImagePath, 0755, true);
                            $indexFilePath = $acctImagePath . '/index.php';
                            file_put_contents($indexFilePath, '<?php die(); ?>');
                        }
                    }
                    $_SESSION['messages'][] = 'Account has been created or modified.';
                } catch (\Exception $e) {
                    $_SESSION['messages'][] = 'Failed to create or modify account: ' . $e->getMessage();
                }
                header('Location: /accounts');
                exit;
            } elseif (isset($_POST['delete_account'])) {
                $accountName = trim($_POST['account']);
                $accountOwner = $_SESSION['username'];
                try {
                    AccountHandler::deleteAccount($accountOwner, $accountName);
                    $_SESSION['messages'][] = 'Account Deleted.';
                } catch (\Exception $e) {
                    $_SESSION['messages'][] = 'Failed to delete account: ' . $e->getMessage();
                }
                header('Location: /accounts');
                exit;
            }
        }

        $this->render('accounts');
    }
}
