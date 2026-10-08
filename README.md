<p align="center">
    <img src="v-chatgpt-social-status-feeds.png" align="center" width="100%">
</p>
<p align="center"><h1 align="center"><code>❯v-chatgpt-social-status-feeds</code></h1></p>
<p align="center">
	<em>Streamline Your Social Statuses, Effortlessly!</em>
</p>
<p align="center">
	<!-- local repository, no metadata badges. --></p>
<p align="center">Built with the tools and technologies:</p>
<p align="center">
	<img src="https://img.shields.io/badge/PHP-777BB4.svg?style=flat-square&logo=PHP&logoColor=white" alt="PHP">
</p>
<br>

## Table of Contents

I. [ Overview](#-overview)
II. [ Features](#-features)
III. [ Project Structure](#-project-structure)
IV. [ Usage](#usage)
V. [ Getting Started](#-getting-started)
VI. [ Docker](#docker)
VII. [ Changelog](#-changelog)
VIII. [ License](#-license)

---

## Overview

v-chatgpt-social-status-feeds is a modular PHP application for managing, scheduling, and distributing social media status updates. It features user authentication, account management, status scheduling, and real-time RSS feeds, all with a focus on security and extensibility. Scheduled posts are recorded in a compact `status_jobs` table and processed by purpose-built cron targets. Built for social media managers and developers, it streamlines multi-account status posting and automation.

Application PHP source lives inside `root/`, primarily in `root/app/` and `root/public/`. Runtime dependencies are managed under `root/composer.json` and `root/vendor/`, while development tooling, tests, and the root Composer manifest live at the repository root. Container setup is in `docker/`; its Compose file provisions Apache and MariaDB. The application uses an MVC structure with controllers, models, services, helpers, and views. The front controller builds an HTTP `Request`, dispatches it through `Router`, and sends the resulting `Response`.

Version 3.0.0 introduces improvements such as dedicated classes for all database operations, a more intuitive user interface, and enhanced user settings for prompt customization. The API schema is now more structured, and the platform is more robust and user-friendly.

- **CSRF Protection:** All forms include CSRF tokens to prevent cross-site request forgery attacks.
- **Input Validation:** User inputs are validated and sanitized to prevent SQL injection and XSS attacks.
- **Session Management:** Secure session handling to prevent session fixation and hijacking.
- **Cookie Security:** Session cookies are configured via `session_set_cookie_params()`
  with `httponly`, `secure`, and `SameSite=Lax` flags for better protection.
- **IP Blacklisting:** Monitors and blacklists suspicious IP addresses to prevent brute-force attacks.
- **Safe Media Storage:** Generates images using sanitized filesystem paths while preserving original account identifiers for database access.
- **Efficient Database Queries:** Uses optimized SQL queries and indexing to ensure fast data retrieval.
- **Two-Tier APCu Caching:** High-performance caching layer (L1: in-memory static, L2: APCu persistent) dramatically reduces database queries and filesystem I/O. Models cache user info, account data, and status feeds. RSS feed XML output is cached for instant delivery. Image metadata is cached to eliminate up to 99% of filesystem operations. Fully configurable via `CACHE_ENABLED` and `CACHE_TTL_*` constants.
- **Modular Classes:** Core logic is organized into classes such as `DatabaseManager`, `UserModel`, `AccountModel`, `StatusModel`, `StatusService`, `FeedController`, `CacheService`, and `ErrorManager`.
- **Request/Response Handling:** `Request` captures HTTP method, path, query, form, and server data; `Router` dispatches routes and the front controller sends the resulting `Response`.
- **Global Error Handling:** Centralized logging and exception management via the `ErrorManager` singleton.
- **Accessible Dashboard UI:** Collapsible status feeds expose synchronized ARIA states, scoped form controls, and responsive footer spacing so navigation and content remain usable across assistive technologies and compact screens.


## Features

|     |      Feature      | Summary |
| :-- | :---------------: | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ⚙️  | **Architecture**  | <ul><li>MVC structure with controllers, models, services, helpers, and views under <code>root/app/</code>.</li><li>Two-tier caching with APCu for persistent cross-request data and in-memory arrays for single-request optimization.</li><li>Configuration centralized in <code>root/config.php</code>.</li><li>Application dependencies are loaded by Composer from <code>root/vendor/autoload.php</code>; root-level Composer metadata provides development tooling.</li><li>HTTP requests are represented by <code>Request</code>, dispatched by <code>Router</code>, and returned as <code>Response</code>.</li><li>Cron automation managed via <code>root/cron.php</code>.</li></ul> |
| 🔩  | **Code Quality**  | <ul><li>Follows PHP best practices and design patterns.</li><li>Centralized database operations using Doctrine DBAL in <code>DatabaseManager.php</code>.</li><li>Robust error handling via the <code>ErrorManager</code> singleton.</li></ul> |
| 📄  | **Documentation** | <ul><li>Includes install and usage steps.</li><li>Written in <code>PHP</code>, <code>SQL</code>, and <code>text</code> formats.</li><li>Simple onboarding for developers and admins.</li></ul> |
| 🔌  | **Integrations**  | <ul><li>Generates status content using OpenAI through <code>StatusService.php</code>.</li><li>Publishes RSS feeds through <code>FeedController</code> for integrations such as IFTTT.</li><li>Secure login and session control via the MVC controllers.</li><li>Email notifications powered by PHPMailer with customizable templates.</li></ul> |
| 🧩  |  **Modularity**   | <ul><li>All logic encapsulated in single-purpose classes.</li><li>Autoloading supports scalability and clean structure.</li><li>Code reuse across handlers and views.</li></ul> |
| 🔒  |   **Security**    | <ul><li>Full CSRF protection on form actions.</li><li>Strict input validation and sanitization.</li><li>Session hardening and IP blacklisting to block abuse.</li></ul> |
                                                                                                                                                        |
---

## Project Structure

```sh
└── /
    ├── CHANGELOG.md
    ├── README.md
    ├── composer.json
    ├── composer.lock
    ├── docker/
    │   ├── Dockerfile
    │   └── docker-compose.yml
    ├── root/
    │   ├── config.php
    │   ├── composer.json
    │   ├── composer.lock
    │   ├── cron.php
    │   ├── install.sql
    │   ├── upgrade.sql
    │   ├── app/
    │   │   ├── Core/
    │   │   ├── Controllers/
    │   │   ├── Helpers/
    │   │   ├── Models/
    │   │   ├── Services/
    │   │   ├── Templates/
    │   │   └── Views/
    │   ├── public/
    │   │   ├── assets/
    │   │   ├── images/
    │   │   ├── index.php
    │   │   ├── install.php
    │   │   └── upgrade.php
    │   └── vendor/
    ├── tests/
    └── vendor/
```
The code under `root/app/` follows an MVC pattern. Shared request, response, routing, database, session, and error-handling components live in `Core`; reusable helpers and business logic live in `Helpers` and `Services`.

## Usage

To access the database connection within the application, retrieve the singleton instance of the `DatabaseManager` class:

```php
use App\Core\DatabaseManager;

$db = DatabaseManager::getInstance();
```

### Request Routing

The front controller creates the router and dispatches a request built from PHP's globals:

```php
use App\Core\Request;
use App\Core\Router;

$router = new Router();
$response = $router->dispatch(Request::fromGlobals());
$response->send();
```

The router registers the application routes and returns a `Response` for the front controller to send.

### Session Management

Manage sessions through the `SessionManager` singleton:

```php
use App\Core\SessionManager;

$session = SessionManager::getInstance();
$session->set('name', 'value');
$value = $session->get('name');
$session->regenerate();      // Initialize or resume the session and regenerate its ID
```

Session initialization is lazy. Call `$session->destroy();` to end the session during logout.

---

## 🚀 Getting Started

### ☑️ Prerequisites

Before getting started with the installation, ensure your runtime environment meets the following requirements:

- **Web Server:** Apache
- **Programming Language:** PHP 8.2+
- **Database:** MySQL 8.0+ or compatible MariaDB

### ⚙️ Installation

Install the project using the following steps:

1. **Upload Files:**

   - Upload all the project files to your hosting server.

2. **Set Webroot:**

   - Set the `public` folder as the webroot directory on your hosting server.

3. **Update Configuration:**

   - Open `root/config.php` and update the necessary variables, including MySQL database credentials.
   - **Timezone Configuration (IMPORTANT):** Set `DEFAULT_TIMEZONE` to your local timezone to ensure scheduled posts run at the correct times. Examples: `'America/New_York'`, `'America/Los_Angeles'`, `'Europe/London'`, `'UTC'`. See [PHP Timezones](https://www.php.net/manual/en/timezones.php) for the full list. This setting ensures that when you schedule a post for 7am, it runs at 7am in YOUR timezone, not UTC.
   - Optionally adjust `CRON_MAX_EXECUTION_TIME` and `CRON_MEMORY_LIMIT` to control how long the cron script runs and how much memory it can use.
   - **Cache Configuration (Optional):** APCu caching is enabled by default with sensible TTL values. Configure via environment variables or edit these constants in `config.php`:
     - `CACHE_ENABLED` (default: true) - Master switch for APCu caching
     - `CACHE_TTL_USER` (default: 300s) - User info cache lifetime
     - `CACHE_TTL_ACCOUNT` (default: 600s) - Account data cache lifetime
     - `CACHE_TTL_STATUS` (default: 60s) - Status data cache lifetime
     - `CACHE_TTL_FEED` (default: 180s) - RSS feed and image metadata cache lifetime
   - **Note:** If APCu extension is not available, the system automatically falls back to in-memory caching for the current request only.

4. **Install Database:**

   - **For fresh installations:** Navigate to `/install.php` in your browser. The script will create all tables with the latest schema and a default admin user.
   - **For upgrades from old schema:** Navigate to `/upgrade.php` in your browser. The script will migrate your existing database to the new structure while preserving all data. **Make sure to backup your database before upgrading!**

5. **Default Login:**

   - Use the default login credentials: `admin` for both username and password.
   - Change the default password immediately after the first login, before exposing the application to other users or the internet.

6. **Set Up Cron Jobs:**
  - Configure cron to call the guarded worker entry points (all four tasks also support the single-argument form for direct execution):
    ```sh
    0 0 * * * /usr/bin/php /PATH-TO-APP/cron.php worker daily
    5 0 * * * /usr/bin/php /PATH-TO-APP/cron.php worker fill-queue
    */10 * * * * /usr/bin/php /PATH-TO-APP/cron.php worker run-queue
    0 0 1 * * /usr/bin/php /PATH-TO-APP/cron.php worker monthly
    ```
  - Replace `/PATH-TO-APP/` with the actual path to your installation.
   - The `worker` prefix acquires a PID-based lock for the specific task being launched. That guarantees only one instance of a given job flag runs at once while still allowing the other workers to execute concurrently. When the guard succeeds, `run-queue` is spawned as a background process so the cron entry returns immediately.
   - On hosts that cannot spawn background processes, call the single-argument form directly (for example, `php cron.php run-queue`). `run-queue` still respects its per-flag lock when invoked inline and drains all due jobs before exiting.
   - **daily:** runs cleanup tasks (purge statuses, images, IPs)
  - **fill-queue:** replaces pending jobs with scheduled slots for the current day; retry jobs are retained
   - **run-queue:** executes due jobs (`scheduled_at <= now`) and enforces a single retry before permanent failure
   - **monthly:** resets API usage counters (run on the 1st of each month)

### Queue Table

The queue uses a lean `status_jobs` table purpose-built for cron orchestration:

```sql
CREATE TABLE status_jobs (
    id CHAR(36) PRIMARY KEY,
    scheduled_at BIGINT NOT NULL,
    account VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    status ENUM('pending','retry') NOT NULL DEFAULT 'pending',
    processing BOOLEAN NOT NULL DEFAULT FALSE
);
CREATE INDEX idx_scheduled ON status_jobs (scheduled_at, status);
CREATE UNIQUE INDEX idx_unique_job ON status_jobs (account, username, scheduled_at);
```

- **fill-queue** runs once daily to replace pending rows with the configured schedule for the current day. Retry rows are retained.
- **run-queue** runs frequently to process records where `scheduled_at <= NOW()`. Each invocation drains all due retry jobs before moving to pending jobs and repeats until neither queue has work remaining. Successful jobs are deleted. A first failure updates the row to `status = 'retry'`; a second failure deletes the job permanently.
- `status` only tracks whether the job is on its original attempt (`pending`) or retrying (`retry`). There is no long-lived worker loop—cron cadence controls execution.
- `processing` is flipped to `true` when a worker claims a job and reset to `false` when work completes or a stale job is released, preventing duplicate workers from acting on the same row.

### 🤖 Usage

Login as `admin` with the password `admin`. Follow these steps:

1. **Create or Change Users:**

   - Navigate to the **User** tab.
   - Add or update user details as needed.

2. **Create Status Campaigns:**
   - Go to the **Account** tab.
   - Click on **Add/Update New Account**.
   - Fill in the following details:
     - **Account Name**
     - **Platform**
     - **Prompt**
     - **Link**
     - **Image Instructions**
     - **Days**
     - **Post Schedule**
     - **Include Hashtags**
   - Click **Add/Update**.

Statuses are generated on schedule and added to the respective account feed and the user's collective omni feed. Use the feed with tools like IFTTT to update social media.

## Docker

From the repository root, build and start the application and database with:

```sh
docker compose -f docker/docker-compose.yml up --build
```

This launches Apache and MariaDB using the settings in the Compose file. Replace the example API and database settings with values appropriate for your environment before exposing the service publicly.

---

## Changelog

See [CHANGELOG.md](./CHANGELOG.md) for the release history and current unreleased changes.

---

## 🎗 License

This project is licensed under the [MIT License](./LICENSE).

---
