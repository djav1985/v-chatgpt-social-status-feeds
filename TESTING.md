# Database Installation and Upgrade Test Documentation

## Overview
This document describes the testing scenarios for the database installation and upgrade scripts.

## Test Scenarios

### Scenario 1: Fresh Installation
**Purpose:** Verify that install.php/install.sql correctly creates a new database

**Pre-conditions:**
- Empty database exists
- Config.php has correct database credentials

**Steps:**
1. Navigate to /install.php
2. Confirm installation

**Expected Results:**
- All 5 tables created: accounts, ip_blacklist, status_updates, status_jobs, users
- Accounts table has composite primary key (username, account)
- status_updates has AUTO_INCREMENT id field
- status_jobs table created with new schema
- users table has limit_email_sent column
- Default admin user created (username: admin, password: admin)
- All indexes created with idx_ prefix
- install.php and install.sql files deleted after success

**Validation Queries:**
```sql
SHOW TABLES;
DESCRIBE accounts;
DESCRIBE status_updates;
DESCRIBE status_jobs;
DESCRIBE users;
DESCRIBE ip_blacklist;
SELECT * FROM users WHERE username='admin';
SHOW INDEX FROM accounts;
SHOW INDEX FROM users;
```

---

### Scenario 2: Upgrade from Old Schema
**Purpose:** Verify that upgrade.php/upgrade.sql correctly migrates old schema to new

**Pre-conditions:**
- Database contains old schema tables with data
- Old schema characteristics:
  - accounts table has PRIMARY KEY on `account` (single column)
  - accounts table has index named `username_idx`
  - users table missing `limit_email_sent` column
  - status_updates has index named `username` (not `idx_username`)
  - ip_blacklist has indexes named `blacklisted` and `timestamp` (not `idx_*`)

**Steps:**
1. Load old schema with test data
2. Navigate to /upgrade.php
3. Check "I have backed up my database" checkbox
4. Click "Upgrade Database"

**Expected Results:**
- Accounts table PRIMARY KEY changed to (username, account)
- Old index `username_idx` removed, new `idx_username` created
- New `idx_platform` index added to accounts
- users table gets `limit_email_sent` column with default FALSE
- All old indexes renamed to use `idx_` prefix
- status_jobs table recreated with new schema
- All existing data preserved
- upgrade.php and upgrade.sql files deleted after success

**Validation Queries:**
```sql
-- Verify composite primary key on accounts
SHOW CREATE TABLE accounts;

-- Verify limit_email_sent column exists
SELECT * FROM information_schema.COLUMNS 
WHERE TABLE_NAME='users' AND COLUMN_NAME='limit_email_sent';

-- Verify index names
SHOW INDEX FROM accounts;
SHOW INDEX FROM users;
SHOW INDEX FROM status_updates;
SHOW INDEX FROM ip_blacklist;

-- Verify data preservation
SELECT COUNT(*) FROM accounts;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM status_updates;

-- Verify status_jobs schema
DESCRIBE status_jobs;
```

---

### Scenario 3: Install with Existing Tables
**Purpose:** Verify that install.php correctly rejects installation on non-empty database

**Pre-conditions:**
- Database contains at least one table

**Steps:**
1. Navigate to /install.php

**Expected Results:**
- Error message: "Database already contains tables"
- Suggestion to use upgrade.php instead
- No changes made to database

---

### Scenario 4: Upgrade Empty Database
**Purpose:** Verify that upgrade.php correctly rejects upgrade on empty database

**Pre-conditions:**
- Empty database

**Steps:**
1. Navigate to /upgrade.php

**Expected Results:**
- Error message: "Database is empty"
- Suggestion to use install.php instead
- No changes made to database

---

## Key Migration Changes

### accounts Table
**Old:**
- PRIMARY KEY (`account`)
- KEY `username_idx` (`username`)

**New:**
- PRIMARY KEY (`username`, `account`)
- INDEX `idx_username` (`username`)
- INDEX `idx_platform` (`platform`)

### users Table
**Old:**
- Missing `limit_email_sent` column
- KEY `email` (`email`)
- KEY `expires` (`expires`)
- KEY `admin` (`admin`)

**New:**
- Has `limit_email_sent BOOLEAN DEFAULT FALSE`
- INDEX `idx_email` (`email`)
- INDEX `idx_expires` (`expires`)
- INDEX `idx_admin` (`admin`)

### status_updates Table
**Old:**
- KEY `username` (`username`)

**New:**
- INDEX `idx_username` (`username`)
- INDEX `idx_account` (`account`)
- INDEX `idx_created_at` (`created_at`)

### ip_blacklist Table
**Old:**
- KEY `blacklisted` (`blacklisted`)
- KEY `timestamp` (`timestamp`)

**New:**
- INDEX `idx_blacklisted` (`blacklisted`)
- INDEX `idx_timestamp` (`timestamp`)

### status_jobs Table
**Old:** May not exist or have different schema

**New:**
```sql
CREATE TABLE status_jobs (
    id CHAR(36) PRIMARY KEY,
    scheduled_at BIGINT NOT NULL,
    account VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    status ENUM('pending','retry') NOT NULL DEFAULT 'pending',
    processing BOOLEAN NOT NULL DEFAULT FALSE,
    INDEX idx_scheduled (scheduled_at, status),
    UNIQUE INDEX idx_unique_job (account, username, scheduled_at)
);
```

---

## Test Data Examples

### Old Schema Test Data
```sql
-- Insert test user
INSERT INTO users (username, password, email, who, `where`, what, goal, total_accounts, max_api_calls, used_api_calls, expires, admin)
VALUES ('testuser', '$2y$10$test', 'test@example.com', 'John Doe', 'New York', 'Developer', 'Build apps', 5, 1000, 100, '2025-12-31', 0);

-- Insert test account
INSERT INTO accounts (account, username, prompt, hashtags, link, cron, platform, days)
VALUES ('test_account', 'testuser', 'Test prompt', 1, 'https://example.com', '0 9 * * *', 'twitter', 'everyday');

-- Insert test status update
INSERT INTO status_updates (username, account, status, created_at, status_image)
VALUES ('testuser', 'test_account', 'Test status', NOW(), NULL);

-- Insert test IP
INSERT INTO ip_blacklist (ip_address, login_attempts, blacklisted, timestamp)
VALUES ('192.168.1.1', 3, 0, UNIX_TIMESTAMP());
```

### Verification After Upgrade
```sql
-- All data should still exist
SELECT * FROM users WHERE username='testuser';
SELECT * FROM accounts WHERE username='testuser' AND account='test_account';
SELECT * FROM status_updates WHERE username='testuser';
SELECT * FROM ip_blacklist WHERE ip_address='192.168.1.1';

-- New column should exist with default value
SELECT username, limit_email_sent FROM users WHERE username='testuser';
-- Expected: limit_email_sent = 0 (FALSE)

-- Composite primary key should work
SELECT * FROM accounts WHERE username='testuser' AND account='test_account';
```

---

## Common Issues and Solutions

### Issue: Duplicate (username, account) combinations
**Symptom:** Upgrade fails when creating composite primary key
**Solution:** Clean up duplicate records before upgrading
```sql
-- Find duplicates
SELECT username, account, COUNT(*) 
FROM accounts 
GROUP BY username, account 
HAVING COUNT(*) > 1;

-- Remove duplicates (keep first occurrence)
DELETE a1 FROM accounts a1
INNER JOIN accounts a2 
WHERE a1.username = a2.username 
  AND a1.account = a2.account 
  AND a1.rowid > a2.rowid;
```

### Issue: Missing username values in accounts table
**Symptom:** Cannot create composite primary key with NULL username values
**Solution:** Populate username values before upgrading
```sql
-- Find accounts with NULL username
SELECT * FROM accounts WHERE username IS NULL OR username = '';

-- Update with appropriate usernames
UPDATE accounts SET username = 'default_user' WHERE username IS NULL OR username = '';
```

---

## Security Notes

1. Both scripts automatically delete themselves after successful execution
2. upgrade.php requires explicit confirmation checkbox
3. Backup reminder displayed before upgrade
4. Default admin password should be changed immediately after installation
5. All SQL operations use prepared statements where dynamic content is involved

---

## Performance Considerations

- Fresh installation should complete in under 5 seconds
- Upgrade time depends on data volume:
  - Small databases (<1000 rows): ~5-10 seconds
  - Medium databases (1000-10000 rows): ~10-30 seconds
  - Large databases (>10000 rows): ~30-60 seconds
- Primary key changes require table rebuild (most time-consuming operation)
- Index creation is relatively fast
