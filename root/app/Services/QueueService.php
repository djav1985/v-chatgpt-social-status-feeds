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
use App\Models\Account;
use App\Models\User;
use App\Models\Status;
use App\Core\Mailer;
use App\Models\Blacklist;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Interop\Queue\Message;

/**
 * Service for queue operations and scheduled maintenance tasks.
 */
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

    /**
     * Clean up old statuses for all accounts.
     */
    public function purgeStatuses(): bool
    {
        $accounts = Account::getAllAccounts();
        if (empty($accounts)) {
            return true;
        }

        foreach ($accounts as $account) {
            $accountName = $account->account;
            $accountOwner = $account->username;
            $statusCount = Status::countStatuses($accountName, $accountOwner);

            if ($statusCount > MAX_STATUSES) {
                $deleteCount = $statusCount - MAX_STATUSES;
                if (!Status::deleteOldStatuses($accountName, $accountOwner, $deleteCount)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Purge old images from the public/images directory.
     */
    public function purgeImages(): bool
    {
        $imageDir = __DIR__ . '/../../public/images/';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($imageDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        $now = time();

        foreach ($files as $fileinfo) {
            if ($fileinfo->isFile() && $fileinfo->getExtension() == 'png') {
                $filePath = $fileinfo->getRealPath();
                $fileAge = ($now - $fileinfo->getMTime()) / 86400;

                if ($fileAge > IMG_AGE) {
                    if (!unlink($filePath)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Reset API usage for all users.
     */
    public function resetApi(): bool
    {
        if (!User::resetAllApiUsage()) {
            return false;
        }
        $users = User::getAllUsers();
        foreach ($users as $user) {
            Mailer::sendTemplate(
                $user->email,
                'API Usage Reset',
                'api_usage_reset',
                ['username' => $user->username]
            );
        }
        return true;
    }

    /**
     * Purge old entries from the IP blacklist.
     */
    public function purgeIps(): bool
    {
        return Blacklist::clearIpBlacklist();
    }

    /**
     * Truncate previous jobs and enqueue all of today's jobs.
     */
    public function scheduleDailyQueue(): void
    {
        $this->context->getDbalConnection()->executeStatement('TRUNCATE TABLE status_jobs');

        $producer = $this->context->createProducer();

        $accounts = Account::getAllAccounts();
        $dayName = strtolower(date('l'));

        foreach ($accounts as $account) {
            $days = array_map('strtolower', array_map('trim', explode(',', (string) $account->days)));
            if (!in_array('everyday', $days, true) && !in_array($dayName, $days, true)) {
                continue;
            }
            $hours = array_filter(array_map('trim', explode(',', (string) $account->cron)), 'strlen');
            foreach ($hours as $hour) {
                $payload = [
                    'username' => $account->username,
                    'account' => $account->account,
                    'hour' => (int) $hour,
                ];
                $message = $this->context->createMessage(JSON::encode($payload));
                $message->setContentType('application/json');
                $producer->send($this->queue, $message);
            }
        }
    }

    /**
     * Run hourly maintenance tasks.
     */
    public function runHourly(): void
    {
        // No hourly maintenance tasks currently.
    }

    /**
     * Run all daily maintenance tasks.
     */
    public function runDaily(): void
    {
        if (date('j') === '1') {
            $this->resetApi();
            $this->purgeStatuses();
            $this->purgeImages();
        }
        $this->purgeIps();
        $this->scheduleDailyQueue();
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
