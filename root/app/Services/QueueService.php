<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Services;

use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use App\Core\DatabaseManager;
use App\Services\StatusService;
use Interop\Queue\Message;

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
        $this->queue = $this->context->createQueue('status_generate');
    }

    private function enqueueStatusForHour(string $username, string $account, int $hour): void
    {
        $payload = ['username' => $username, 'account' => $account, 'hour' => $hour];
        $message = $this->context->createMessage(JSON::encode($payload));
        $message->setContentType('application/json');
        $this->context->createProducer()->send($this->queue, $message);
    }

    public function enqueueRemainingJobs(string $username, string $account, string $cron, string $days): void
    {
        $dayName = strtolower(date('l'));
        $currentHour = (int) date('G');
        $daysArr = array_map('strtolower', array_map('trim', explode(',', $days)));
        if (!in_array('everyday', $daysArr, true) && !in_array($dayName, $daysArr, true)) {
            return;
        }
        $hours = array_filter(array_map('trim', explode(',', $cron)), 'strlen');
        foreach ($hours as $hour) {
            $intHour = (int) $hour;
            if ($intHour > $currentHour) {
                $this->enqueueStatusForHour($username, $account, $intHour);
            }
        }
    }

    public function removeFutureJobs(string $username, string $account): void
    {
        $currentHour = (int) date('G');
        $db = DatabaseManager::getInstance();
        $db->query("DELETE FROM status_jobs WHERE JSON_EXTRACT(body, '\$.username') = :user AND JSON_EXTRACT(body, '\$.account') = :acct AND JSON_EXTRACT(body, '\$.hour') >= :hr");
        $db->bind(':user', $username);
        $db->bind(':acct', $account);
        $db->bind(':hr', $currentHour);
        $db->execute();
    }

    public function removeAllJobs(string $username, string $account): void
    {
        $db = DatabaseManager::getInstance();
        $db->query("DELETE FROM status_jobs WHERE JSON_EXTRACT(body, '\$.username') = :user AND JSON_EXTRACT(body, '\$.account') = :acct");
        $db->bind(':user', $username);
        $db->bind(':acct', $account);
        $db->execute();
    }

    public function processLoop(bool $once = false): void
    {
        $consumer = new QueueConsumer($this->context);
        $consumer->bindCallback($this->queue, function (Message $message) {
            $data = JSON::decode($message->getBody());
            if (!is_array($data) || !isset($data['account'], $data['username'])) {
                return Result::REJECT;
            }
            try {
                StatusService::generateStatus($data['account'], $data['username']);
                return Result::ACK;
            } catch (\Throwable $e) {
                $attempts = (int) $message->getProperty('attempts', 0);
                if ($attempts >= 1) {
                    return Result::REJECT;
                }
                $message->setProperty('attempts', $attempts + 1);
                $message->setPriority(3); // MessagePriority::HIGH equivalent
                $message->setDeliveryDelay(3600000);
                return Result::REQUEUE;
            }
        });

        do {
            $consumer->consume(new LimitConsumptionTimeExtension(new \DateTime('+55 minutes')));
            if ($once) {
                break;
            }
            $sleep = 3600 - ((int) date('i') * 60 + (int) date('s'));
            if ($sleep > 0) {
                sleep($sleep);
            }
        } while (!$once);
    }
}
