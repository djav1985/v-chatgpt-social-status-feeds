<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: MessageHelper.php
 * Description: Helper for managing session-based messages.
 */

namespace App\Helpers;

use App\Core\SessionManager;

class MessageHelper
{
    /**
     * Append a flash message to the current session's message queue.
     *
     * @param string $message The message text to store.
     * @return void
     */
    public static function addMessage(string $message): void
    {
        $messages = SessionManager::getInstance()->get('messages');
        if (!is_array($messages)) {
            $messages = [];
        }
        $messages[] = $message;
        SessionManager::getInstance()->set('messages', $messages);
    }

    /**
     * Retrieve queued flash messages as plain data and clear the queue.
     *
     * @return list<string>
     */
    public static function getAndClearMessages(): array
    {
        $messages = SessionManager::getInstance()->get('messages');
        SessionManager::getInstance()->set('messages', []);

        if (!is_array($messages)) {
            return [];
        }

        return array_values(array_filter($messages, 'is_string'));
    }
}
