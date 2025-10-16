# Worker Lock Fix Documentation

## Problem Description

The system defines four worker job types: `run-queue`, `fill-queue`, `daily`, and `monthly`. Each worker should create its own lock file to prevent multiple instances of the same worker from running simultaneously, while allowing different workers to run concurrently.

### Root Cause

The worker locking logic was duplicated and inconsistent between two locations:

1. **cron.php** (lines 57-114 in original):
   - `workerLockPath()` - Created lock file paths per job type
   - `workerGuardCanLaunch()` - Checked and optionally claimed locks
   - Called with `$claimLockForSelf = true` for all workers except `run-queue`

2. **QueueService.php** (lines 343-382 in original):
   - `getWorkerLockPath()` - Defined lock file paths per job type (duplicate)
   - `claimWorkerLock()` - Tried to claim locks again
   - `releaseWorkerLock()` - Released locks
   - `isProcessRunning()` - Checked if process is running (duplicate)

### The Issue

When launching `fill-queue`, `daily`, or `monthly` workers:

1. `cron.php` would claim the lock via `workerGuardCanLaunch(..., true)`
2. Control would pass to `QueueService`
3. `QueueService::fillQueue()`/`runDaily()`/`runMonthly()` would call `claimWorkerLock()`
4. `claimWorkerLock()` would try to re-acquire the same lock
5. This would fail, causing false "already running" messages

Only `run-queue` worked differently because it was spawned as a separate process.

## Solution

### 1. Created WorkerHelper Class

Created `root/app/Helpers/WorkerHelper.php` with centralized lock management:

- **`getLockPath(string $jobType): string`** - Returns lock file path for a job type
- **`isProcessRunning(int $pid): bool`** - Checks if a process is running
- **`canLaunch(string $jobType): bool`** - Checks if worker can launch (no conflict)
- **`claimLock(string $jobType): ?array`** - Claims lock and returns handle
- **`releaseLock(?array $lockInfo): void`** - Releases lock and cleans up
- **`claimLockAndWritePid(string $jobType): bool`** - Simplified lock claim

### 2. Updated cron.php

- Moved autoloader loading to the top of the file (before argument parsing) so `WorkerHelper` can be used directly
- Changed worker launch logic to be consistent for ALL worker types:
  - All workers now use `WorkerHelper::canLaunch()` to check if another instance is running
  - None of the workers claim locks in `cron.php`
  - Lock claiming is deferred to `QueueService`

### 3. Updated QueueService.php

- Replaced internal lock handling with `WorkerHelper` calls
- Changed `$workerLockHandle` and `$workerLockPath` to single `$workerLock` array
- `claimWorkerLock()` now calls `WorkerHelper::claimLock()`
- `releaseWorkerLock()` now calls `WorkerHelper::releaseLock()`
- Removed duplicate `getWorkerLockPath()` and `isProcessRunning()` methods

### 4. Added Comprehensive Tests

Created `tests/WorkerHelperTest.php` with 17 tests covering:
- Lock path generation for all job types
- Process running detection
- Lock claiming and releasing
- Concurrent lock handling
- Multiple worker types running simultaneously

## Behavior After Fix

### All Four Workers Are Now Handled Identically

```php
// In cron.php for ALL workers (run-queue, fill-queue, daily, monthly):
if (!WorkerHelper::canLaunch($jobType)) {
    echo 'Worker "' . $jobType . '" already running.' . PHP_EOL;
    exit(0);
}
```

### Lock Claiming Happens in QueueService

```php
// In QueueService methods (runQueue, fillQueue, runDaily, runMonthly):
if (!$this->claimWorkerLock()) {
    error_log('[QueueService] Worker already running; skipping invocation.');
    return;
}

try {
    // Do work...
} finally {
    $this->releaseWorkerLock();
}
```

### Multiple Workers Can Run Simultaneously

Each worker type has its own lock file:
- `/tmp/socialrss-worker-run-queue.lock`
- `/tmp/socialrss-worker-fill-queue.lock`
- `/tmp/socialrss-worker-daily.lock`
- `/tmp/socialrss-worker-monthly.lock`

Different worker types can claim their locks simultaneously without conflict.

### Same Worker Type Is Properly Blocked

If you try to run `daily` twice:
1. First instance claims `/tmp/socialrss-worker-daily.lock`
2. Second instance checks lock file, sees running process, exits with "already running"

### Crash Recovery

If a worker crashes without releasing its lock:
1. The PID in the lock file becomes stale
2. Next launch detects the process is not running
3. Stale lock is cleaned up automatically
4. New worker can proceed

## Test Results

### Before Fix
- 41 tests, 3 errors (pre-existing, unrelated to worker locks)
- Worker lock tests passing but behavior was incorrect

### After Fix
- 58 tests (+17 new tests for WorkerHelper), same 3 errors (pre-existing)
- All worker lock tests passing
- Manual verification confirms correct behavior

## Files Changed

### New Files
- `root/app/Helpers/WorkerHelper.php` - Centralized lock management (234 lines)
- `tests/WorkerHelperTest.php` - Comprehensive test suite (194 lines)

### Modified Files
- `root/cron.php` - Simplified lock handling (+140 lines, -73 lines)
- `root/app/Services/QueueService.php` - Uses WorkerHelper (-42 lines)

## Usage Examples

### Checking if Worker Can Launch

```php
use App\Helpers\WorkerHelper;

if (WorkerHelper::canLaunch('daily')) {
    // Safe to launch daily worker
}
```

### Claiming and Releasing Lock

```php
$lockInfo = WorkerHelper::claimLock('fill-queue');
if ($lockInfo === null) {
    // Another instance is running
    return;
}

try {
    // Do work...
} finally {
    WorkerHelper::releaseLock($lockInfo);
}
```

### Verifying Multiple Workers Can Run

```php
$runQueueLock = WorkerHelper::claimLock('run-queue');
$fillQueueLock = WorkerHelper::claimLock('fill-queue');
$dailyLock = WorkerHelper::claimLock('daily');
$monthlyLock = WorkerHelper::claimLock('monthly');

// All four succeed - no conflicts!

WorkerHelper::releaseLock($runQueueLock);
WorkerHelper::releaseLock($fillQueueLock);
WorkerHelper::releaseLock($dailyLock);
WorkerHelper::releaseLock($monthlyLock);
```

## Verification Steps

To verify the fix works correctly:

1. Run all tests: `phpunit --no-coverage`
   - Should show 58 tests passing (3 pre-existing errors unrelated to locks)

2. Test WorkerHelper directly: `phpunit --no-coverage tests/WorkerHelperTest.php`
   - Should show 17 tests passing

3. Test concurrent workers: `phpunit --no-coverage tests/CronCliTest.php`
   - Should show 10 tests passing, including independence test

4. Manual verification:
   ```bash
   # Start daily worker
   php root/cron.php worker daily &
   
   # Start fill-queue worker (should succeed)
   php root/cron.php worker fill-queue &
   
   # Try to start daily again (should be blocked)
   php root/cron.php worker daily
   # Expected output: Worker "daily" already running.
   ```

## Conclusion

The fix successfully centralizes all worker lock logic in `WorkerHelper`, eliminating the duplicate locking that was causing false "already running" messages. All four worker types are now handled identically, can run simultaneously when different, and properly prevent duplicate instances of the same type.
