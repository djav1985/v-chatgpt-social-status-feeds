# Changelog

All notable changes to this project will be documented in this file.
See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## Unreleased
### Added
- Initialized coding standards and test suite.
- Updated tooling paths and bootstrap references.
- Added unit coverage for status identifier normalization and filesystem sanitization.
- **New QueueService explicit entry points**: `runQueue()`, `fillQueue()`, `runDaily()`, `runMonthly()`
- **New cron targets**: `run-queue`, `fill-queue`, `daily`, `monthly` for precise cron scheduling
- **Enhanced queue processing**: Bounded execution per cron pass, time-based job filtering
- **Improved retry logic**: First failure marks retry=1, second failure deletes job
- **Comprehensive test coverage**: Queue timing, retry lifecycle, CLI argument parsing

### Changed  
- **BREAKING**: Replaced legacy `processLoop()` with bounded `runQueue()` - no more long-lived workers
- **BREAKING**: Removed `--once` flag - each cron invocation now exits after one bounded pass
- **BREAKING**: Cron targets changed from `daily`/`hourly`/`worker` to `run-queue`/`fill-queue`/`daily`/`monthly`
- **Queue safety**: `fillQueue()` appends jobs without truncation, enforces uniqueness
- **Task separation**: `runDaily()` only handles cleanup, `runMonthly()` only resets API

### Removed
- **BREAKING**: Removed `processLoop()`, `scheduleDailyQueue()`, `runHourly()` methods  
- **BREAKING**: Removed legacy switch statement and long-lived worker support
- **BREAKING**: Removed `--once` flag handling

### Fixed
- Prevented HTML encoding of account identifiers before database lookups in `StatusService`, keeping special characters intact while securing image storage paths.
