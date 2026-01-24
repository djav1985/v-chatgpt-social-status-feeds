# Changelog

All notable changes to this project will be documented in this file.
See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## Unreleased
### Added
- **APCu Caching Infrastructure**: Implemented comprehensive two-tier caching system (L1: in-memory static, L2: APCu persistent) across all Models and Controllers
- **Code Quality Improvements**: Refactored class methods to follow PHP standards and improve maintainability
  - New `CacheService` class with singleton pattern, automatic APCu detection, and fallback to in-memory caching
  - Integrated caching into `User`, `Account`, and `Status` models with configurable TTLs
  - RSS feed XML output caching for instant delivery of cached feeds
  - Image metadata caching to eliminate filesystem I/O bottlenecks (up to 99% reduction in file operations)
  - Cache configuration via environment variables or config.php constants: `CACHE_ENABLED`, `CACHE_TTL_USER`, `CACHE_TTL_ACCOUNT`, `CACHE_TTL_STATUS`, `CACHE_TTL_FEED`
- Simplified `status_jobs` schema with `scheduled_at`, `account`, `username`, and a lightweight `status` enum for retry tracking.
- Queue tests covering the new retry lifecycle and fill-queue scheduling rules.
- Separate installation and upgrade paths: `install.php`/`install.sql` for fresh installs and `upgrade.php`/`upgrade.sql` for schema migrations.
- `MIGRATION.md` documentation file detailing the migration process and schema changes.
- Regression coverage for next-day scheduling, stale job recovery, quota enforcement, and image purge edge cases.

### Changed
- **Method Organization**: Reorganized methods in `QueueService` and `MaintenanceService` to follow PHP standards (properties → constructor → public → protected → private)
- **Code Simplification**: Inlined trivial wrapper methods in `User`, `Account`, and `QueueService` models to reduce unnecessary abstraction
- Aligned the PHP 8.2 requirement across Composer, Docker, and README prerequisites.
- Documented the `processing` flag in the queue table schema.
- Queue worker invocations now use `php cron.php worker <task>` to acquire a PID lock before spawning the single-argument worker, ensuring only one queue runner is active at a time.
- Worker locks are now tracked per job flag, preventing duplicate launches of the same task while allowing different cron workers to run in parallel.
- `QueueService::runQueue()` loops with a fresh timestamp until no retry or pending jobs remain, draining any work that becomes due mid-run while continuing to prioritise retries.
- Introduced lightweight in-memory caches for frequent account and user lookups to cut duplicate database queries during status generation and dashboard actions.
- `QueueService::runQueue()` now reads due rows directly from the database, deleting successes, marking the first failure as `retry`, and removing permanently after a second failure.
- `fillQueue()` clears existing rows before scheduling all slots for the current day, including hours earlier in the day.
- Cron documentation updated to describe the simplified worker behaviour, optional guarded worker prefix, and retry policy.
- Dashboard collapse controls now provide deterministic IDs, synchronized ARIA attributes, and visually hidden copy to improve assistive technology support.
- Footer spacing and layout styles updated so primary content remains visible on compact screens.
- `install.sql` and `install.php` now perform fresh installations only without migration logic.
- `upgrade.sql` and `upgrade.php` handle all migrations from old schema to new schema with data preservation.
- Accounts table primary key changed from single column `(account)` to composite key `(username, account)`.
- All table indexes updated to use consistent naming convention with `idx_` prefix.
- Accounts dashboard and queue maintenance now reuse fetched account rows, share timestamp calculations, and prune only over-limit status histories to avoid redundant database work.

### Removed
- **Dead Code Elimination**: Removed unused `statusesPerJob()` method from `QueueService`
- Enqueue DBAL transport usage and the JSON-backed queue schema.
- `STATUS_JOB_BATCH_SIZE` configuration option and the batch-size helper methods.

### Fixed
- Added detection and handling for incomplete OpenAI API responses with `status: 'incomplete'` to provide clear error messages instead of failing silently when responses are truncated.
- Increased `max_output_tokens` for status generation (512→1024 for most platforms, 256→512 for Twitter) to reduce likelihood of truncation errors.
- Prevented HTML encoding of account identifiers before database lookups in `StatusService`, keeping special characters intact while securing image storage paths.
- Scoped full-width form button styling to avoid stretching logout actions in the header.
- Sanitized Semgrep SARIF output in the CI workflow to keep GitHub Code Scanning from rejecting the uploaded report.
- Deferred RSS escaping in `FeedController` so feed generation keeps special characters intact for account lookups while still outputting safe XML.
- Adjusted truncated JSON repair for generated statuses to append only the missing closing braces, eliminating stray quote characters that broke decoding.
- Returned raw account links while escaping them at render time and tightened feed/dashboard metadata escaping, including GUIDs now built from status identifiers.
- Dashboard share/copy buttons now emit URL-encoded paths so clipboard and share APIs work with names containing spaces or reserved characters.
- Queue scheduling releases stale `processing` jobs, enforces API quotas for background runs, and guards image purging when the directory is missing.
- Account schedule updates now clear queued jobs before re-enqueuing, and queue scheduling skips past hours instead of rescheduling them.
