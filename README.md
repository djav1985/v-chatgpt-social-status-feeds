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

v-chatgpt-social-status-feeds is a modular PHP application for managing, scheduling, and distributing social media status updates. It features user authentication, account management, status scheduling, and real-time RSS feeds, all with a focus on security and extensibility. Scheduled posts are queued using Enqueue's DBAL transport and processed by the cron script. Built for social media managers and developers, it streamlines multi-account status posting and automation.

All PHP source files live inside the `root` directory. The code uses a lightweight MVC approach with controllers, models, and views organized under `root/app`. Bootstrapping is handled by Composer's `vendor/autoload.php` and `root/config.php`. For an easy local setup, the repository includes a `docker` folder containing a `Dockerfile` and `docker-compose.yml` that provision Apache and MariaDB.

Version 3.0.0 introduces improvements such as dedicated classes for all database operations, a more intuitive user interface, and enhanced user settings for prompt customization. The API schema is now more structured, and the platform is more robust and user-friendly.

- **CSRF Protection:** All forms include CSRF tokens to prevent cross-site request forgery attacks.
- **Input Validation:** User inputs are validated and sanitized to prevent SQL injection and XSS attacks.
- **Session Management:** Secure session handling to prevent session fixation and hijacking.
- **Cookie Security:** Session cookies are configured via `session_set_cookie_params()`
  with `httponly`, `secure`, and `SameSite=Lax` flags for better protection.
- **IP Blacklisting:** Monitors and blacklists suspicious IP addresses to prevent brute-force attacks.
- **Safe Media Storage:** Generates images using sanitized filesystem paths while preserving original account identifiers for database access.
- **Efficient Database Queries:** Uses optimized SQL queries and indexing to ensure fast data retrieval.
- **Modular Classes:** Core logic is organized into classes such as DatabaseManager, User, Account, StatusService, FeedController, and ErrorManager for maintainability and scalability.
- **Global Error Handling:** Centralized logging and exception management via the `ErrorManager` singleton.


## Features

|     |      Feature      | Summary |
| :-- | :---------------: | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ⚙️  | **Architecture**  | <ul><li>Modular structure with dedicated classes: <code>DatabaseManager</code>, <code>User</code>, <code>Account</code>, <code>StatusService</code>, <code>FeedController</code>, and the singleton <code>ErrorManager</code> (see <code>root/app/</code>).</li><li>Configuration centralized in <code>root/config.php</code>.</li><li>Autoloading handled by Composer via <code>vendor/autoload.php</code>.</li><li>Cron automation managed via <code>root/cron.php</code>.</li></ul> |
| 🔩  | **Code Quality**  | <ul><li>Follows PHP best practices and design patterns.</li><li>Centralized database operations using Doctrine DBAL in <code>DatabaseManager.php</code>.</li><li>Robust error handling via the <code>ErrorManager</code> singleton.</li><li>Clean inline documentation throughout core files.</li></ul> |
| 📄  | **Documentation** | <ul><li>Includes install and usage steps.</li><li>Written in <code>PHP</code>, <code>SQL</code>, and <code>text</code> formats.</li><li>Simple onboarding for developers and admins.</li></ul> |
| 🔌  | **Integrations**  | <ul><li>Posts to social platforms via <code>StatusService.php</code>.</li><li>Real-time RSS feed generation using <code>FeedController::outputRssFeed()</code>.</li><li>Secure login and session control via the MVC controllers.</li><li>Email notifications powered by PHPMailer with customizable templates.</li></ul> |
| 🧩  |  **Modularity**   | <ul><li>All logic encapsulated in single-purpose classes.</li><li>Autoloading supports scalability and clean structure.</li><li>Code reuse across handlers and views.</li></ul> |
| 🔒  |   **Security**    | <ul><li>Full CSRF protection on form actions.</li><li>Strict input validation and sanitization.</li><li>Session hardening and IP blacklisting to block abuse.</li></ul> |
                                                                                                                                                        |
---

## Project Structure

```sh
└── /
    ├── README.md
    ├── docker/
    └── root
        ├── vendor/
        ├── composer.json
        ├── composer.lock
        ├── config.php
        ├── cron.php
        ├── install.sql
        ├── app/
        │   ├── Core/
        │   ├── Controllers/
        │   ├── Models/
        │   └── Views/
        └── public/
            ├── assets/
            ├── images/
            ├── index.php
            └── install.php
```
The code under `root/app` follows an MVC pattern with `Controllers`, `Models`, and `Views`. Shared framework classes live in the `Core` directory.

## Usage

To access the database connection within the application, retrieve the singleton instance of the `DatabaseManager` class:

```php
use App\Core\DatabaseManager;

$db = DatabaseManager::getInstance();
```

### Routing

The router is exposed as a singleton. Use the shared instance to dispatch requests:

```php
use App\Core\Router;

Router::getInstance()->dispatch($method, $uri);
```

This ensures the underlying FastRoute dispatcher is constructed only once.

### Session Management

Manage sessions through the `SessionManager` singleton:

```php
use App\Core\SessionManager;

$session = SessionManager::getInstance();
$session->start();           // Start or resume a session
$session->set('name', 'value');
$value = $session->get('name');
$session->regenerate();      // Regenerate ID after login
```

Call `$session->destroy();` to end the session during logout.

---

## 🚀 Getting Started

### ☑️ Prerequisites

Before getting started with the installation, ensure your runtime environment meets the following requirements:

- **Web Server:** Apache
- **Programming Language:** PHP 8.0+
- **Database:** MySQL 8.0+ or compatible MariaDB
  - Required for the `FOR UPDATE SKIP LOCKED` feature used by the queue to allow concurrent workers.

### ⚙️ Installation

Install the project using the following steps:

1. **Upload Files:**

   - Upload all the project files to your hosting server.

2. **Set Webroot:**

   - Set the `public` folder as the webroot directory on your hosting server.

3. **Update Configuration:**

   - Open `root/config.php` and update the necessary variables, including MySQL database credentials.
  - Optionally adjust `CRON_MAX_EXECUTION_TIME` and `CRON_MEMORY_LIMIT` to control how long the cron script runs and how much memory it can use.

4. **Install Database:**

   - Load the application in your web browser. Go to /install.php and the application will automatically install the database.

5. **Default Login:**

   - Use the default login credentials: `admin` for both username and password.

6. **Set Up Cron Jobs:**
  - Configure cron to call the explicit entry points:
    ```sh
    0 0 * * * /usr/bin/php /PATH-TO-APP/cron.php daily
    5 0 * * * /usr/bin/php /PATH-TO-APP/cron.php fill-queue  
    */10 * * * * /usr/bin/php /PATH-TO-APP/cron.php run-queue
    0 0 1 * * /usr/bin/php /PATH-TO-APP/cron.php monthly
    ```
  - Replace `/PATH-TO-APP/` with the actual path to your installation.
   - **daily:** runs cleanup tasks (purge statuses, images, IPs)
   - **fill-queue:** adds future job slots for the current day without truncating existing jobs
   - **run-queue:** processes only jobs that are scheduled for now or past times
   - **monthly:** resets API usage counters (run on the 1st of each month)

### Queue Table

The `status_jobs` table uses Enqueue's DBAL schema. Each message stores a JSON
payload identifying the user, account, and scheduled hour for status updates. The
`fill-queue` cron safely appends future job slots without truncating existing jobs,
enforcing uniqueness by (account_id, scheduled_time). The `run-queue` cron processes
only jobs with scheduled_time <= now(), implementing a retry lifecycle: first failure
marks retry=1, second failure deletes the job.

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

The **docker** directory contains a `Dockerfile` and `docker-compose.yml` for running the project locally. Start the containers with:

```sh
docker compose up
```

This launches an Apache web server and MariaDB instance with configuration values taken from the compose file. Adjust the environment variables there to set API keys and database credentials.

The `db` service allows an empty MariaDB root password by setting `MARIADB_ALLOW_EMPTY_ROOT_PASSWORD: '1'`. If you want a password instead, set `MYSQL_ROOT_PASSWORD` and update `DB_PASSWORD` in the `web` service.

---

## Changelog

### 2025-07-05

- Switched feed URLs to `/feeds/{user}/{account}` and `/feeds/{user}/all`.
- Added rewrite for new feed paths under `/feeds/{user}/{account}`.
- Updated navigation links to use the new structure.

### 2025-07-04

- Moved RSS feed generation into `FeedController` and removed `rss-lib.php`.
- Added type hints across the codebase and improved error propagation.
- Improved error logging for RSS feed generation.
- Refactored API interactions into `StatusService`.
- Various security fixes and bug improvements.

---

## 🎗 License

This project is licensed under the [MIT License](./LICENSE).

---
