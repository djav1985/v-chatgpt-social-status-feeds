<?php
namespace App\Helpers;

use App\Models\UserHandler;

/**
 * Helper functions for user profile-related operations.
 */
class InfoHelper
{
    public static function generateProfileDataAttributes(string $username): string
    {
        $userInfo = UserHandler::getUserInfo($username);
        if ($userInfo) {
            $data = "data-who=\"" . htmlspecialchars($userInfo->who) . "\" ";
            $data .= "data-where=\"" . htmlspecialchars($userInfo->where) . "\" ";
            $data .= "data-what=\"" . htmlspecialchars($userInfo->what) . "\" ";
            $data .= "data-goal=\"" . htmlspecialchars($userInfo->goal) . "\"";
            return $data;
        }
        return '';
    }

    public static function buildSystemMessage(string $username): string
    {
        $userInfo = UserHandler::getUserInfo($username);
        if ($userInfo) {
            $systemMessage = "<span style=\"color: blue; font-weight: bold;\">" . SYSTEM_MSG . "</span>";
            $systemMessage .= " <span style=\"color: blue; font-weight: bold;\">You work for</span> " . htmlspecialchars($userInfo->who) . " <span style=\"color: blue; font-weight: bold;\">located in</span> " . htmlspecialchars($userInfo->where) . ". " . htmlspecialchars($userInfo->what) . " <span style=\"color: blue; font-weight: bold;\">Your goal is</span> " . htmlspecialchars($userInfo->goal) . ".";
            return $systemMessage;
        }
        return "<span style=\"color: blue; font-weight: bold;\">" . SYSTEM_MSG . "</span>";
    }
}
