<?php
namespace App\Helpers;

use App\Models\UserHandler;

/**
 * Helper functions for user management.
 */
class UsersHelper
{
    public static function generateUserList(): string
    {
        $users = UserHandler::getAllUsers();
        $output = "";
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
