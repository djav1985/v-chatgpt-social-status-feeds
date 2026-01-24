<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\MessageHelper;
use App\Core\SessionManager;
use PHPUnit\Framework\TestCase;

final class MessageHelperTest extends TestCase
{
    private SessionManager $session;

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

    public function testDisplayAndClearMessagesOutputsJavaScript(): void
    {
        $message1 = 'Test message 1';
        $message2 = 'Test message 2';
        
        MessageHelper::addMessage($message1);
        MessageHelper::addMessage($message2);
        
        ob_start();
        MessageHelper::displayAndClearMessages();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('showToast', $output);
        $this->assertStringContainsString(json_encode($message1), $output);
        $this->assertStringContainsString(json_encode($message2), $output);
    }

    public function testDisplayAndClearMessagesClearsSession(): void
    {
        MessageHelper::addMessage('Test message');
        
        $this->assertNotEmpty($this->session->get('messages', []));
        
        ob_start();
        MessageHelper::displayAndClearMessages();
        ob_end_clean();
        
        $messages = $this->session->get('messages', []);
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    public function testDisplayAndClearMessagesWithNoMessages(): void
    {
        // Ensure no messages in session
        $this->session->set('messages', []);
        
        ob_start();
        MessageHelper::displayAndClearMessages();
        $output = ob_get_clean();
        
        $this->assertEmpty($output);
    }

    public function testDisplayAndClearMessagesHandlesSpecialCharacters(): void
    {
        $message = 'Message with "quotes" and <html>';
        
        MessageHelper::addMessage($message);
        
        ob_start();
        MessageHelper::displayAndClearMessages();
        $output = ob_get_clean();
        
        // JSON encoding should escape special characters properly
        $this->assertStringContainsString(json_encode($message), $output);
    }

    public function testMessagesPersistAcrossMultipleAdds(): void
    {
        MessageHelper::addMessage('Message 1');
        $this->assertCount(1, $this->session->get('messages', []));
        
        MessageHelper::addMessage('Message 2');
        $this->assertCount(2, $this->session->get('messages', []));
        
        MessageHelper::addMessage('Message 3');
        $this->assertCount(3, $this->session->get('messages', []));
    }

    public function testEmptyStringMessage(): void
    {
        MessageHelper::addMessage('');
        
        $messages = $this->session->get('messages', []);
        $this->assertCount(1, $messages);
        $this->assertSame('', $messages[0]);
    }
}
