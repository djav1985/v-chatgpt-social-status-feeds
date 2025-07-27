<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Models;

use DateTime;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Interop\Queue\Context;

class JobQueue
{
    private static ?Context $context = null;

    /**
     * Expose the queue context for consumers.
     */
    public static function context(): Context
    {
        return self::getContext();
    }

    /**
     * Get or create the queue context using Doctrine DBAL transport.
     */
    private static function getContext(): Context
    {
        if (self::$context === null) {
            $factory = new DbalConnectionFactory([
                'connection' => [
                    'dbname'   => DB_NAME,
                    'user'     => DB_USER,
                    'password' => DB_PASSWORD,
                    'host'     => DB_HOST,
                    'driver'   => 'pdo_mysql',
                    'charset'  => 'utf8mb4',
                ],
                'table_name' => QUEUE_TABLE,
            ]);
            /** @var DbalContext $ctx */
            $ctx = $factory->createContext();
            $ctx->createDataBaseTable();
            self::$context = $ctx;
        }

        return self::$context;
    }

    /**
     * Enqueue a status generation job with optional delay.
     */
    private static function enqueue(string $username, string $account, DateTime $runAt): void
    {
        $context = self::getContext();
        $queue   = $context->createQueue('status');
        $message = $context->createMessage('', [
            'username' => $username,
            'account'  => $account,
        ]);
        $delay = max(0, ($runAt->getTimestamp() - time()) * 1000);
        $context->createProducer()
            ->setDeliveryDelay($delay)
            ->send($queue, $message);
    }

    /**
     * Populate jobs for the current day.
     */
    public static function fillQueryJobs(): bool
    {
        $start    = new DateTime('today');
        $end      = (clone $start)->modify('+1 day');
        $accounts = Account::getAllAccounts();
        foreach ($accounts as $account) {
            self::scheduleAccount($account, $start, $end);
        }
        return true;
    }

    /**
     * Populate remaining jobs today for a specific account.
     */
    public static function fillRemainingToday(string $username, string $account): bool
    {
        $start = new DateTime();
        $end   = (clone $start)->setTime(0, 0)->modify('+1 day');
        $acct  = Account::getAcctInfo($username, $account);
        if (!$acct) {
            return false;
        }
        self::scheduleAccount($acct, $start, $end);
        return true;
    }

    /**
     * Helper to schedule an account's jobs within a time range.
     */
    private static function scheduleAccount($account, DateTime $start, DateTime $end): void
    {
        $hours = array_filter(array_map('trim', explode(',', $account->cron)), 'strlen');
        if (empty($hours)) {
            return;
        }
        $days = array_map('strtolower', array_map('trim', explode(',', $account->days)));

        foreach ($hours as $hour) {
            if (!is_numeric($hour)) {
                continue;
            }
            $hour = (int) $hour;
            if ($hour < 0 || $hour > 23) {
                continue;
            }
            $runTime = (clone $start)->setTime($hour, 0);
            if ($runTime < $start) {
                $runTime->modify('+1 day');
            }
            if ($runTime >= $end) {
                continue;
            }
            $dayName = strtolower($runTime->format('l'));
            if (!in_array('everyday', $days) && !in_array($dayName, $days)) {
                continue;
            }
            self::enqueue($account->username, $account->account, $runTime);
        }
    }
}
