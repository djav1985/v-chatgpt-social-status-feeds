<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: info-helper.php
 * Description: Helper functions for user profile-related operations.
 * License: MIT
 */

/**
 * Generates data attributes for the user profile form.
 *
 * @param string $username
 * @return string HTML data attributes
 */
function generateProfileDataAttributes(string $username): string
{
    $userInfo = UserHandler::getUserInfo($username);

    if ($userInfo) {
        $dataAttributes = "data-who=\"" . htmlspecialchars($userInfo->who) . "\" ";
        $dataAttributes .= "data-where=\"" . htmlspecialchars($userInfo->where) . "\" ";
        $dataAttributes .= "data-what=\"" . htmlspecialchars($userInfo->what) . "\" ";
        $dataAttributes .= "data-goal=\"" . htmlspecialchars($userInfo->goal) . "\"";
        return $dataAttributes;
    }

    return '';
}

/**
 * Builds the system message by combining the base system message with user profile information.
 *
 * @param string $username
 * @return string The complete system message
 */
function buildSystemMessage(string $username): string
{
    $userInfo = UserHandler::getUserInfo($username);

    if ($userInfo) {
        $systemMessage = "<span style=\"color: blue; font-weight: bold;\">" . SYSTEM_MSG . "</span>";

        $systemMessage .= " <span style=\"color: blue; font-weight: bold;\">You work for</span> " . $userInfo->who . " <span style=\"color: blue; font-weight: bold;\">located in</span> " . $userInfo->where . ". " . $userInfo->what . " <span style=\"color: blue; font-weight: bold;\">Your goal is</span> " . $userInfo->goal . ".";
        return $systemMessage;
    }

    return "<span style=\"color: blue; font-weight: bold;\">" . SYSTEM_MSG . "</span>";
}
