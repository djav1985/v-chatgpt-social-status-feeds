# Migration Guide

This guide outlines how to move existing installations to the latest schema and highlights the key structural changes.

## Recommended Process

1. **Back up your database.** Always create a full backup before running any migration.
2. **Upload the new codebase.** Deploy the updated application files to your server.
3. **Run the upgrade script.** Open `/upgrade.php` in your browser to apply `root/upgrade.sql` and migrate data safely.
4. **Verify the application.** Confirm that status scheduling, queue processing, and feeds operate as expected.

## Schema Changes Included

- **Queue table refresh:** `status_jobs` now uses a compact schema with `scheduled_at`, `account`, `username`, `status`, and a `processing` flag to track in-flight work.
- **Accounts primary key update:** the `accounts` table migrates to a composite primary key of `(username, account)`.
- **Index normalization:** index names use a consistent `idx_` prefix across tables.

For fresh installs, use `/install.php` which executes `root/install.sql`. Existing installations should always use `/upgrade.php`.
