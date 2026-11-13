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
     * Output all queued flash messages as JavaScript toast calls and clear the queue.
     *
     * @return void
     */
    public static function displayAndClearMessages(): void
    {
        $messages = SessionManager::getInstance()->get('messages');
        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as $message) {
                echo '<script>showToast(' . json_encode($message) . ');</script>';
            }
            SessionManager::getInstance()->set('messages', []);
        }
    }
}
