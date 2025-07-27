<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Services;

use Enqueue\Dbal\DbalConnectionFactory;
use App\Services\StatusService;

class QueueService
{
    private \Interop\Queue\Context $context;
    private \Interop\Queue\Queue $queue;

    public function __construct()
    {
        $factory = new DbalConnectionFactory([
            'connection' => [
                'dbname' => DB_NAME,
                'user' => DB_USER,
                'password' => DB_PASSWORD,
                'host' => DB_HOST,
                'driver' => 'pdo_mysql',
                'charset' => 'utf8mb4',
            ],
            'table_name' => 'status_jobs',
            'lazy' => false,
        ]);

        $this->context = $factory->createContext();
        $this->queue = $this->context->createQueue('status');
    }

    public function enqueueStatus(string $username, string $account): void
    {
        $payload = json_encode(['username' => $username, 'account' => $account]);
        $message = $this->context->createMessage($payload);
        $this->context->createProducer()->send($this->queue, $message);
    }

    public function runQueue(): void
    {
        $consumer = $this->context->createConsumer($this->queue);
        while ($message = $consumer->receiveNoWait()) {
            $data = json_decode($message->getBody(), true);
            if (is_array($data) && isset($data['username'], $data['account'])) {
                StatusService::generateStatus($data['account'], $data['username']);
            }
            $consumer->acknowledge($message);
        }
    }
}
