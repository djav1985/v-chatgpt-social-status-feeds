# Changelog

All notable changes to this project will be documented in this file.
See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## Unreleased
### Added
- Simplified `status_jobs` schema with `scheduled_at`, `account`, `username`, and a lightweight `status` enum for retry tracking.
- Queue tests covering the new retry lifecycle and fill-queue scheduling rules.

### Changed
- `QueueService::runQueue()` now reads due rows directly from the database, deleting successes, marking the first failure as `retry`, and removing permanently after a second failure.
- `fillQueue()` appends future slots without truncation, skips past hours, and relies on unique `(account, username, scheduled_at)` rows instead of Enqueue payloads.
- Cron documentation updated to describe the simplified worker behaviour and retry policy.

### Removed
- Enqueue DBAL transport usage and the JSON-backed queue schema.

### Fixed
- Prevented HTML encoding of account identifiers before database lookups in `StatusService`, keeping special characters intact while securing image storage paths.
