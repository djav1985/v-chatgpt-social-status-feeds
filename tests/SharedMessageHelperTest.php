<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: SharedMessageHelperTest.php
 * Description: Shared PHPUnit tests for MessageHelper.
 */

declare(strict_types=1);

namespace Tests;

use App\Helpers\MessageHelper;
use App\Core\SessionManager;
use PHPUnit\Framework\TestCase;

final class SharedMessageHelperTest extends TestCase
{
    private SessionManager $session;

    public function testAddMessageStoresMessageInSession(): void
    {
        $message = 'Test message';

        MessageHelper::addMessage($message);

        $messages = $this->session->get('messages', []);
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertSame($message, $messages[0]);
    }

    public function testAddMultipleMessagesStoresInOrder(): void
    {
        $message1 = 'First message';
        $message2 = 'Second message';
        $message3 = 'Third message';

        MessageHelper::addMessage($message1);
        MessageHelper::addMessage($message2);
        MessageHelper::addMessage($message3);

        $messages = $this->session->get('messages', []);
        $this->assertCount(3, $messages);
        $this->assertSame($message1, $messages[0]);
        $this->assertSame($message2, $messages[1]);
        $this->assertSame($message3, $messages[2]);
    }

    public function testAddMessageToExistingMessages(): void
    {
        // Pre-populate session with a message
        $this->session->set('messages', ['Existing message']);

        $newMessage = 'New message';
        MessageHelper::addMessage($newMessage);

        $messages = $this->session->get('messages', []);
        $this->assertCount(2, $messages);
        $this->assertSame('Existing message', $messages[0]);
        $this->assertSame($newMessage, $messages[1]);
    }

    public function testGetAndClearMessagesReturnsQueuedMessagesAsData(): void
    {
        $message1 = 'Test message 1';
        $message2 = 'Test message 2';

        MessageHelper::addMessage($message1);
        MessageHelper::addMessage($message2);

        self::assertSame([$message1, $message2], MessageHelper::getAndClearMessages());
    }

    public function testGetAndClearMessagesClearsSession(): void
    {
        MessageHelper::addMessage('Test message');

        $this->assertNotEmpty($this->session->get('messages', []));

        self::assertSame(['Test message'], MessageHelper::getAndClearMessages());

        $messages = $this->session->get('messages', []);
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    public function testGetAndClearMessagesWithNoMessages(): void
    {
        // Ensure no messages in session
        $this->session->set('messages', []);

        self::assertSame([], MessageHelper::getAndClearMessages());
    }

    public function testGetAndClearMessagesPreservesSpecialCharactersAsPlainText(): void
    {
        $message = 'Message with "quotes" and <html>';

        MessageHelper::addMessage($message);

        self::assertSame([$message], MessageHelper::getAndClearMessages());
    }

    public function testEmptyStringMessage(): void
    {
        MessageHelper::addMessage('');

        $messages = $this->session->get('messages', []);
        $this->assertCount(1, $messages);
        $this->assertSame('', $messages[0]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Start a clean session for each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $_SESSION = [];
        $this->session = SessionManager::getInstance();
    }

    protected function tearDown(): void
    {
        // Clean up session data
        $_SESSION = [];
        parent::tearDown();
    }
}
