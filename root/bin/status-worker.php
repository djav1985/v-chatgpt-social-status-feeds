<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

require __DIR__ . '/../config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Services\QueueService;

$once = in_array('--once', $argv, true);
$service = new QueueService();
$service->processLoop($once);
