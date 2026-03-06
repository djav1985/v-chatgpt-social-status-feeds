
# Copilot Instructions for v-chatgpt-social-status-feeds

## Big Picture & Architecture
This is a modular PHP app for managing, scheduling, and distributing social media status updates. All source code lives under `root/` and follows a strict MVC pattern:
- **Controllers** (`root/app/Controllers/`): Handle HTTP requests, session logic, and route mapping. Example: `AuthController.php` for login/session, `FeedController.php` for RSS feeds.
- **Models** (`root/app/Models/`): Encapsulate all database logic. Use the custom `Database` class (Doctrine DBAL wrapper) for queries and transactions. Example: `User.php`, `Account.php`.
- **Views** (`root/app/Views/`): Render HTML templates. Use partials and layouts for reuse.
- **Core** (`root/app/Core/`): Shared utilities (routing, error logging, CSRF, mail, etc.).
- **Services** (`root/app/Services/`): Business logic (status scheduling, queue, security, caching).

**Configuration:**
- Centralized in `root/config.php` (DB credentials, API keys, session/cookie settings, cache TTLs).
- All environment-sensitive data should be set here or via Docker env vars.

**Caching:**
- Two-tier caching system: L1 (in-memory static arrays), L2 (APCu persistent cache)
- Managed by `CacheService.php` singleton in Services
- Automatically falls back to in-memory when APCu unavailable
- All Models (`User`, `Account`, `Status`) use two-tier caching for frequently accessed data
- RSS feed XML output is cached for instant delivery
- Cache TTLs configurable via `CACHE_TTL_*` constants in `config.php`

**Routing:**
- Managed by `Router.php` in Core. Controllers define routes and map to views.

**Database:**
- MariaDB backend. Schema in `install.sql`. All access via the `Database` class (uses Doctrine DBAL).
- Status jobs are queued using Enqueue DBAL transport; processed by `cron.php`.

## Developer Workflows

### Quick Start (Docker)
1. `cd v-chatgpt-social-status-feeds && docker compose -f docker/docker-compose.yml up`
2. Access http://localhost
3. Navigate to `/install.php` to initialize the database (MariaDB auto-created)
4. Create a user account and configure API keys in the dashboard

### Quick Start (Manual Setup)
1. Install PHP 8.2+, MariaDB, composer
2. `cd root && composer install`
3. Configure `root/config.php` with DB credentials, OpenAI API key, SMTP settings
4. Access `http://localhost/public/index.php` and visit `/install.php` to create schema
5. Run migrations if needed: visit `/upgrade.php`

### Testing
- **Run all tests:** `composer test` (from root directory or project root)
- **Run specific test:** `composer test -- tests/AccountsControllerTest.php`
- **Bootstrap:** [tests/bootstrap.php](tests/bootstrap.php) sets up test environment
- **Coverage:** PHPUnit generates coverage for `root/app/` in `coverage/` directory
- **Test files location:** `tests/` (PSR-4 namespace: `Tests\`)

### Code Quality & Linting
- **Check style:** `composer phpcs` (PSR-12 standard, configured in [phpcs.xml](phpcs.xml))
- **Auto-fix style:** `composer phpcbf` (auto-corrects PSR-12 violations)
- **Type checking:** `composer phpstan` (PHPStan level 6, configured in [phpstan.neon](phpstan.neon))
- **Before committing:** Run all three commands and fix any failures

### Cron Jobs
**Location:** [root/cron.php](root/cron.php) — Main job processor for scheduled statuses and maintenance

**Setup:** Configure two system cron schedules:
- **Hourly:** `0 * * * * php /path/to/root/cron.php` — Process scheduled post jobs, retry failed jobs
- **Daily:** `0 2 * * * php /path/to/root/cron.php daily` — Purge old job history, reset daily quota counters, cleanup expired sessions

**Job Flow:**
1. User creates a status and schedules it for a future time
2. `StatusService::scheduleStatus()` inserts a row into `status_jobs` table
3. Hourly cron executes `cron.php` → `QueueService::processQueue()` checks for due jobs
4. Due jobs are published to social media accounts via OpenAI API
5. Successful jobs are removed; failed jobs are retried with exponential backoff
6. Daily cron (`cron.php daily`) purges completed jobs older than retention period

**Monitoring:** Check `status_jobs` table for queue health: `status='pending'` (due), `'retrying'` (failed), `'completed'` (done)

### Debugging
- **Error logs:** `ErrorManager::logMessage()` (singleton) writes to file if `ENABLE_LOGGING=true` in config
- **Check logs/** directory for diagnostic info
- **DB inspection:** Use any MariaDB client to query `users`, `accounts`, `status_jobs`, `status_updates` tables
- **Session debug:** `SessionManager.php` tracks login attempts, IP blacklist via `ip_blacklist` table

## Project-Specific Conventions
- All forms must include a CSRF token (see `Csrf.php`). Validate in controllers.
- Input validation: Use regex for usernames/passwords in `UsersController.php`.
- Session management: Sessions start in `config.php`; always call `session_regenerate_id(true)` after login.
- Cookie security: Set `httponly`, `secure`, and `SameSite=Lax` in config.
- IP blacklisting: Use `Utility.php` for suspicious IPs.
- All DB access must use the `Database` class for consistency and error handling.
- Caching: Use `CacheService` for persistent data. Models implement two-tier caching automatically. Cache invalidation happens in Model mutation methods (create, update, delete).

## HTTP Request Flow
1. **Entry point:** [root/public/index.php](root/public/index.php) \u2014 All requests route through here (Apache rewrite rules hide `index.php` in URLs)
2. **Router:** `Router.php` (in Core) matches request URI to controller action
3. **Controllers:** In `app/Controllers/` \u2014 Each extends base `Controller` class
   - `AuthController` \u2014 Login, logout, session regeneration
   - `AccountsController` \u2014 Add/edit/delete social media accounts
   - `FeedController` \u2014 Generate and serve RSS feeds
   - `HomeController` \u2014 Dashboard and UI
   - `UsersController` \u2014 User management and quota tracking\n4. **Response:** Controller calls `$this->render(view, data)` to render template or returns JSON/XML\n\n## Integration Points & External Dependencies\n- **OpenAI API:** Managed by `ApiHandler.php` for post/image generation.\n- **Enqueue DBAL:** Used for status job queueing (see `QueueService.php`).\n- **PHPMailer:** For email notifications (see `Mailer.php` and templates).\n- **RSS Feeds:** Generated by `FeedController::outputRssFeed()`; feeds at `/feeds/{user}/{account}` and `/feeds/{user}/all`.

## Key Files & Examples
- `root/config.php`: All config and environment settings
- `root/public/index.php`: HTTP entry point for all requests
- `root/public/install.php`: Database schema initialization (visited during setup)
- `root/public/upgrade.php`: Database migration tool (preserves data through schema changes)
- `root/cron.php`: Background job processor (must be scheduled via system cron)
- `root/app/Controllers/AuthController.php`: Auth/session logic
- `root/app/Controllers/FeedController.php`: RSS feed generation
- `root/app/Models/User.php`: User DB logic with two-tier caching
- `root/app/Models/Account.php`: Account DB logic with two-tier caching
- `root/app/Models/Status.php`: Status DB logic with APCu caching
- `root/app/Models/StatusJob.php`: Queue job records
- `root/app/Services/CacheService.php`: APCu cache abstraction layer
- `root/app/Services/StatusService.php`: Status scheduling/queue logic
- `root/app/Services/QueueService.php`: Job processing and retry logic
- `root/app/Services/MaintenanceService.php`: Cleanup and quota reset
- `root/app/Core/Utility.php`: RSS, IP blacklist, helpers
- `root/app/Core/Router.php`: FastRoute-based URL routing
- `root/app/Core/Database.php`: Doctrine DBAL wrapper for all DB access
- `root/app/Core/SessionManager.php`: Session handling and security
- `docker/docker-compose.yml`: Local dev environment (Apache + MariaDB)
- `phpunit.xml`: PHPUnit configuration (coverage target: `root/app/`)
- `phpcs.xml`: PHP CodeSniffer PSR-12 rules
- `phpstan.neon`: PHPStan level 6 type checking config

## Notes
- Strictly follow MVC and use provided service/util classes.
- Never hardcode secrets; use config or env vars.
- Update this file as the codebase evolves.
