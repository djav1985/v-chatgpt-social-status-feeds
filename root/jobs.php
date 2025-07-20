<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

use App\Models\JobQueue;

/**
 * Wrapper for JobQueue::fillQueryJobs so it can be called as a function.
 */
function fillQueryJobs(): bool
{
    return JobQueue::fillQueryJobs();
}
