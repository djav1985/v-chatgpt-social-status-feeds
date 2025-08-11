<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Services;

use Enqueue\Dbal\DbalConnectionFactory;
use App\Services\StatusService;
use App\Models\Account;
use App\Core\DatabaseManager;

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

    public function clearQueue(): void
    {
        $db = DatabaseManager::getInstance();
        $db->query('TRUNCATE TABLE status_jobs');
        $db->execute();
    }

    private function enqueueStatusForHour(string $username, string $account, int $hour): void
    {
        $payload = json_encode(['username' => $username, 'account' => $account, 'hour' => $hour]);
        $message = $this->context->createMessage($payload);
        $this->context->createProducer()->send($this->queue, $message);
    }

    public function enqueueDailyJobs(): void
    {
        $accounts = Account::getAllAccounts();
        $dayName = strtolower(date('l'));
        foreach ($accounts as $account) {
            $days = array_map('strtolower', array_map('trim', explode(',', (string) $account->days)));
            if (!in_array('everyday', $days) && !in_array($dayName, $days)) {
                continue;
            }
            $hours = array_filter(array_map('trim', explode(',', (string) $account->cron)), 'strlen');
            foreach ($hours as $hour) {
                $this->enqueueStatusForHour($account->username, $account->account, (int) $hour);
            }
        }
    }

    public function enqueueRemainingJobs(string $username, string $account, string $cron, string $days): void
    {
        $dayName = strtolower(date('l'));
        $currentHour = (int) date('G');
        $daysArr = array_map('strtolower', array_map('trim', explode(',', $days)));
        if (!in_array('everyday', $daysArr) && !in_array($dayName, $daysArr)) {
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
        $db->query("DELETE FROM status_jobs WHERE status IN ('pending','retry') AND JSON_EXTRACT(body, '\$.username') = :user AND JSON_EXTRACT(body, '\$.account') = :acct AND JSON_EXTRACT(body, '\$.hour') >= :hr");
        $db->bind(':user', $username);
        $db->bind(':acct', $account);
        $db->bind(':hr', $currentHour);
        $db->execute();
    }

    public function runQueue(): void
    {
        $db = DatabaseManager::getInstance();
        $currentHour = (int) date('G');

        // Retry jobs first
        $db->query("SELECT id, body, attempts, status FROM status_jobs WHERE status = 'retry'");
        $retryJobs = $db->resultSet();
        foreach ($retryJobs as $job) {
            $this->processJob($job);
        }

        // Pending jobs for current hour
        $db->query("SELECT id, body, attempts, status FROM status_jobs WHERE status = 'pending'");
        $pendingJobs = $db->resultSet();
        foreach ($pendingJobs as $job) {
            $data = json_decode($job['body'], true);
            if (!is_array($data) || !isset($data['hour']) || (int) $data['hour'] > $currentHour) {
                continue;
            }
            $this->processJob($job);
        }
    }

    private function processJob(array $job): void
    {
        $db = DatabaseManager::getInstance();
        $data = json_decode($job['body'], true);
        $id = $job['id'];
        $attempts = (int) ($job['attempts'] ?? 0);
        if (!is_array($data) || !isset($data['username'], $data['account'])) {
            $db->query("UPDATE status_jobs SET status = 'failed' WHERE id = :id");
            $db->bind(':id', $id);
            $db->execute();
            return;
        }

        try {
            StatusService::generateStatus($data['account'], $data['username']);
            $db->query("UPDATE status_jobs SET status = 'done' WHERE id = :id");
            $db->bind(':id', $id);
            $db->execute();
        } catch (\Exception $e) {
            $newStatus = $job['status'] === 'retry' ? 'failed' : 'retry';
            $attempts++;
            $db->query('UPDATE status_jobs SET status = :status, attempts = :attempts WHERE id = :id');
            $db->bind(':status', $newStatus);
            $db->bind(':attempts', $attempts);
            $db->bind(':id', $id);
            $db->execute();
        }
    }
}
