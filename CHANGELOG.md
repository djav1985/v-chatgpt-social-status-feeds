# Changelog

All notable changes to this project will be documented in this file.
See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## Unreleased
### Added
- Simplified `status_jobs` schema with `scheduled_at`, `account`, `username`, and a lightweight `status` enum for retry tracking.
- Queue tests covering the new retry lifecycle and fill-queue scheduling rules.
- Separate installation and upgrade paths: `install.php`/`install.sql` for fresh installs and `upgrade.php`/`upgrade.sql` for schema migrations.
- `MIGRATION.md` documentation file detailing the migration process and schema changes.

### Changed
- Queue worker now generates multiple statuses per job using a configurable batch size (default 3) to support bulk content creation.
- Introduced lightweight in-memory caches for frequent account and user lookups to cut duplicate database queries during status generation and dashboard actions.
- `QueueService::runQueue()` now reads due rows directly from the database, deleting successes, marking the first failure as `retry`, and removing permanently after a second failure.
- `fillQueue()` appends future slots without truncation, skips past hours, and relies on unique `(account, username, scheduled_at)` rows instead of Enqueue payloads.
- Cron documentation updated to describe the simplified worker behaviour and retry policy.
- Dashboard collapse controls now provide deterministic IDs, synchronized ARIA attributes, and visually hidden copy to improve assistive technology support.
- Footer spacing and layout styles updated so primary content remains visible on compact screens.
- `install.sql` and `install.php` now perform fresh installations only without migration logic.
- `upgrade.sql` and `upgrade.php` handle all migrations from old schema to new schema with data preservation.
- Accounts table primary key changed from single column `(account)` to composite key `(username, account)`.
- All table indexes updated to use consistent naming convention with `idx_` prefix.

### Removed
- Enqueue DBAL transport usage and the JSON-backed queue schema.

### Fixed
- Prevented HTML encoding of account identifiers before database lookups in `StatusService`, keeping special characters intact while securing image storage paths.
- Scoped full-width form button styling to avoid stretching logout actions in the header.
- Sanitized Semgrep SARIF output in the CI workflow to keep GitHub Code Scanning from rejecting the uploaded report.
- Deferred RSS escaping in `FeedController` so feed generation keeps special characters intact for account lookups while still outputting safe XML.
- Adjusted truncated JSON repair for generated statuses to append only the missing closing braces, eliminating stray quote characters that broke decoding.
- Returned raw account links while escaping them at render time and tightened feed/dashboard metadata escaping, including GUIDs now built from status identifiers.
