-- Create the ip_blacklist table if it doesn't exist
CREATE TABLE IF NOT EXISTS ip_blacklist (
    ip_address VARCHAR(255) NOT NULL,
    login_attempts INT DEFAULT 0,
    blacklisted BOOLEAN DEFAULT FALSE,
    timestamp BIGINT UNSIGNED,
    PRIMARY KEY (ip_address)
);

-- Ensure the ip_blacklist table has all the required columns (alter only if necessary)
-- Add missing columns to ip_blacklist for older MySQL versions
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ip_blacklist'
          AND COLUMN_NAME = 'login_attempts') = 0,
    'ALTER TABLE ip_blacklist ADD COLUMN login_attempts INT DEFAULT 0',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ip_blacklist'
          AND COLUMN_NAME = 'blacklisted') = 0,
    'ALTER TABLE ip_blacklist ADD COLUMN blacklisted BOOLEAN DEFAULT FALSE',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ip_blacklist'
          AND COLUMN_NAME = 'timestamp') = 0,
    'ALTER TABLE ip_blacklist ADD COLUMN timestamp BIGINT UNSIGNED',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- Ensure indexes on ip_blacklist table
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ip_blacklist'
          AND INDEX_NAME = 'blacklisted') > 0,
    'DROP INDEX blacklisted ON ip_blacklist',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX blacklisted ON ip_blacklist (blacklisted);

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ip_blacklist'
          AND INDEX_NAME = 'timestamp') > 0,
    'DROP INDEX timestamp ON ip_blacklist',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX timestamp ON ip_blacklist (timestamp);

-- Create the status_updates table if it doesn't exist
CREATE TABLE IF NOT EXISTS status_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    account VARCHAR(255) NOT NULL,
    status TEXT,
    created_at DATETIME,
    status_image VARCHAR(255)
);

-- Ensure the status_updates table has all the required columns (alter only if necessary)
-- Add missing columns to status_updates table
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND COLUMN_NAME = 'username') = 0,
    'ALTER TABLE status_updates ADD COLUMN username VARCHAR(255) NOT NULL',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND COLUMN_NAME = 'account') = 0,
    'ALTER TABLE status_updates ADD COLUMN account VARCHAR(255) NOT NULL',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND COLUMN_NAME = 'status') = 0,
    'ALTER TABLE status_updates ADD COLUMN status TEXT',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND COLUMN_NAME = 'created_at') = 0,
    'ALTER TABLE status_updates ADD COLUMN created_at DATETIME',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND COLUMN_NAME = 'status_image') = 0,
    'ALTER TABLE status_updates ADD COLUMN status_image VARCHAR(255)',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- Ensure indexes on status_updates table
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND INDEX_NAME = 'username') > 0,
    'DROP INDEX username ON status_updates',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX username ON status_updates (username);

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND INDEX_NAME = 'account') > 0,
    'DROP INDEX account ON status_updates',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX account ON status_updates (account);

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND INDEX_NAME = 'created_at') > 0,
    'DROP INDEX created_at ON status_updates',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX created_at ON status_updates (created_at);

-- Create the status_jobs table if it doesn't exist
CREATE TABLE IF NOT EXISTS status_jobs (
    id CHAR(36) NOT NULL PRIMARY KEY,
    published_at BIGINT NOT NULL,
    body TEXT,
    headers TEXT,
    properties TEXT,
    redelivered BOOLEAN DEFAULT NULL,
    queue VARCHAR(255) NOT NULL,
    priority SMALLINT DEFAULT NULL,
    delayed_until BIGINT DEFAULT NULL,
    time_to_live BIGINT DEFAULT NULL,
    delivery_id CHAR(36) DEFAULT NULL,
    redeliver_after BIGINT DEFAULT NULL,
    status ENUM('pending','retry','failed','done') NOT NULL DEFAULT 'pending',
    attempts INT DEFAULT 0,
    INDEX idx_priority (priority, published_at, queue, delivery_id, delayed_until, id),
    INDEX idx_redeliver (redeliver_after, delivery_id),
    INDEX idx_ttl (time_to_live, delivery_id),
    INDEX idx_delivery (delivery_id)
);

-- Ensure status_jobs has status column
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_jobs'
          AND COLUMN_NAME = 'status') = 0,
    'ALTER TABLE status_jobs ADD COLUMN status ENUM(''pending'',''retry'',''failed'',''done'') NOT NULL DEFAULT ''pending''' ,
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- Ensure status_jobs has attempts column
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_jobs'
          AND COLUMN_NAME = 'attempts') = 0,
    'ALTER TABLE status_jobs ADD COLUMN attempts INT DEFAULT 0',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- Create accounts table if it doesn’t exist
CREATE TABLE IF NOT EXISTS accounts (
    account VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    prompt TEXT,
    hashtags BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    cron VARCHAR(255),
    days VARCHAR(255),
    platform VARCHAR(255) NOT NULL,
    PRIMARY KEY (username, account)
);

-- Ensure the accounts table has all the required columns (alter only if necessary)
-- Add missing columns to accounts table
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'prompt') = 0,
    'ALTER TABLE accounts ADD COLUMN prompt TEXT',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'hashtags') = 0,
    'ALTER TABLE accounts ADD COLUMN hashtags BOOLEAN DEFAULT FALSE',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'link') = 0,
    'ALTER TABLE accounts ADD COLUMN link VARCHAR(255)',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'cron') = 0,
    'ALTER TABLE accounts ADD COLUMN cron VARCHAR(255)',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'days') = 0,
    'ALTER TABLE accounts ADD COLUMN days VARCHAR(255)',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'platform') = 0,
    'ALTER TABLE accounts ADD COLUMN platform VARCHAR(255) NOT NULL',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- Ensure indexes on accounts table
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND INDEX_NAME = 'username_idx') > 0,
    'DROP INDEX username_idx ON accounts',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX username_idx ON accounts (username);

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND INDEX_NAME = 'platform_idx') > 0,
    'DROP INDEX platform_idx ON accounts',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX platform_idx ON accounts (platform);

-- Check if the users table exists before creating it and inserting default data
SET @users_table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users');

-- Create users table if it doesn’t exist
CREATE TABLE IF NOT EXISTS users (
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    who TEXT,
    `where` TEXT,
    what TEXT,
    goal TEXT,
    total_accounts INT DEFAULT 10,
    max_api_calls BIGINT DEFAULT 9999999999,
    used_api_calls BIGINT DEFAULT 0,
    limit_email_sent BOOLEAN DEFAULT FALSE,
    expires DATE DEFAULT '9999-12-31',
    admin TINYINT DEFAULT 0,
    PRIMARY KEY (username)
);

-- Insert default admin user **only if the users table did not previously exist**
INSERT INTO users (username, password, email, who, `where`, what, goal, total_accounts, max_api_calls, used_api_calls, limit_email_sent, admin)
SELECT 'admin', '$2y$10$4idUpn/Kgpxx.GHfyLgKWeHVyZq3ugpx1mUMC6Aze9.yj.KWKTaKG', 'admin@example.com', 'Who I am', 'Where I am', 'What I do', 'My goal', 10, 9999999999, 0, 0, 1
WHERE @users_table_exists = 0;

-- Ensure the users table has all the required columns (alter only if necessary)
-- Add missing columns to users table
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE users ADD COLUMN email VARCHAR(255) NOT NULL AFTER password',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'who') = 0,
    'ALTER TABLE users ADD COLUMN who TEXT AFTER email',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'where') = 0,
    'ALTER TABLE users ADD COLUMN `where` TEXT AFTER who',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'what') = 0,
    'ALTER TABLE users ADD COLUMN what TEXT AFTER `where`',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'limit_email_sent') = 0,
    'ALTER TABLE users ADD COLUMN limit_email_sent BOOLEAN DEFAULT FALSE AFTER used_api_calls',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'goal') = 0,
    'ALTER TABLE users ADD COLUMN goal TEXT AFTER what',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'expires') = 0,
    'ALTER TABLE users ADD COLUMN expires DATE DEFAULT ''9999-12-31'' AFTER limit_email_sent',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- Ensure indexes on users table
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND INDEX_NAME = 'email') > 0,
    'DROP INDEX email ON users',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX email ON users (email);

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND INDEX_NAME = 'expires') > 0,
    'DROP INDEX expires ON users',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX expires ON users (expires);

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND INDEX_NAME = 'admin') > 0,
    'DROP INDEX admin ON users',
    'SELECT 0');
PREPARE drop_sql FROM @stmt;
EXECUTE drop_sql;
DEALLOCATE PREPARE drop_sql;
CREATE INDEX admin ON users (admin);

-- Remove old unused columns from the accounts table (only if they exist)
ALTER TABLE accounts 
DROP COLUMN IF EXISTS image_prompt,
DROP COLUMN IF EXISTS cta;
