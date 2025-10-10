-- =========================
-- Database Upgrade/Migration Script
-- Migrates old schema to new schema while preserving data
-- =========================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =========================
-- ip_blacklist - Ensure table exists and update structure
-- =========================
CREATE TABLE IF NOT EXISTS ip_blacklist (
    ip_address VARCHAR(255) NOT NULL,
    login_attempts INT DEFAULT 0,
    blacklisted BOOLEAN DEFAULT FALSE,
    timestamp BIGINT UNSIGNED,
    PRIMARY KEY (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure all required columns exist in ip_blacklist
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

-- Drop old indexes that may have different names
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ip_blacklist'
          AND INDEX_NAME = 'blacklisted') > 0,
    'ALTER TABLE ip_blacklist DROP INDEX blacklisted',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ip_blacklist'
          AND INDEX_NAME = 'timestamp') > 0,
    'ALTER TABLE ip_blacklist DROP INDEX timestamp',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

-- Create new indexes with consistent naming
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ip_blacklist'
          AND INDEX_NAME = 'idx_blacklisted') = 0,
    'CREATE INDEX idx_blacklisted ON ip_blacklist (blacklisted)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ip_blacklist'
          AND INDEX_NAME = 'idx_timestamp') = 0,
    'CREATE INDEX idx_timestamp ON ip_blacklist (timestamp)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

-- =========================
-- status_updates - Ensure table exists and update structure
-- =========================
CREATE TABLE IF NOT EXISTS status_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    account VARCHAR(255) NOT NULL,
    status TEXT,
    created_at DATETIME,
    status_image VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure all required columns exist in status_updates
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND COLUMN_NAME = 'id') = 0,
    'ALTER TABLE status_updates ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

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

-- Drop old indexes that may have different names
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND INDEX_NAME = 'username') > 0,
    'ALTER TABLE status_updates DROP INDEX username',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

-- Create new indexes with consistent naming
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND INDEX_NAME = 'idx_username') = 0,
    'CREATE INDEX idx_username ON status_updates (username)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND INDEX_NAME = 'idx_account') = 0,
    'CREATE INDEX idx_account ON status_updates (account)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'status_updates'
          AND INDEX_NAME = 'idx_created_at') = 0,
    'CREATE INDEX idx_created_at ON status_updates (created_at)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

-- =========================
-- status_jobs - Recreate with new schema
-- =========================
DROP TABLE IF EXISTS status_jobs;
CREATE TABLE status_jobs (
    id CHAR(36) PRIMARY KEY,
    scheduled_at BIGINT NOT NULL,
    account VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    status ENUM('pending','retry') NOT NULL DEFAULT 'pending',
    processing BOOLEAN NOT NULL DEFAULT FALSE,
    INDEX idx_scheduled (scheduled_at, status),
    UNIQUE INDEX idx_unique_job (account, username, scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- accounts - Migrate primary key and structure
-- =========================
CREATE TABLE IF NOT EXISTS accounts (
    account VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    prompt TEXT,
    hashtags BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    cron VARCHAR(255),
    days VARCHAR(255),
    platform VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Detect if old schema exists (single column primary key on 'account')
SET @old_pk_exists = (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'accounts'
      AND CONSTRAINT_NAME = 'PRIMARY'
      AND COLUMN_NAME = 'account'
      AND ORDINAL_POSITION = 1
      AND (SELECT COUNT(*) 
           FROM information_schema.KEY_COLUMN_USAGE 
           WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'accounts' 
             AND CONSTRAINT_NAME = 'PRIMARY') = 1
);

-- Migrate from old primary key to new composite primary key
SET @stmt := IF(
    @old_pk_exists > 0,
    'ALTER TABLE accounts DROP PRIMARY KEY, ADD PRIMARY KEY (username, account)',
    'SELECT 0'
);
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- If there's no primary key at all, add the composite primary key
SET @has_pk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'accounts'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);

SET @stmt := IF(
    @has_pk = 0,
    'ALTER TABLE accounts ADD PRIMARY KEY (username, account)',
    'SELECT 0'
);
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- Ensure all required columns exist in accounts
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'username') = 0,
    'ALTER TABLE accounts ADD COLUMN username VARCHAR(255) NOT NULL AFTER account',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

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
    'ALTER TABLE accounts ADD COLUMN days VARCHAR(255) DEFAULT ''everyday''',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'platform') = 0,
    'ALTER TABLE accounts ADD COLUMN platform VARCHAR(255) NOT NULL DEFAULT ""',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- Drop old indexes that may have different names
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND INDEX_NAME = 'username_idx') > 0,
    'ALTER TABLE accounts DROP INDEX username_idx',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

-- Create new indexes with consistent naming
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND INDEX_NAME = 'idx_username') = 0,
    'CREATE INDEX idx_username ON accounts (username)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND INDEX_NAME = 'idx_platform') = 0,
    'CREATE INDEX idx_platform ON accounts (platform)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

-- Remove old unused columns from the accounts table (only if they exist)
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'image_prompt') > 0,
    'ALTER TABLE accounts DROP COLUMN image_prompt',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accounts'
          AND COLUMN_NAME = 'cta') > 0,
    'ALTER TABLE accounts DROP COLUMN cta',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- =========================
-- users - Update structure and add missing columns
-- =========================
CREATE TABLE IF NOT EXISTS users (
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL DEFAULT '',
    who TEXT,
    `where` TEXT,
    what TEXT,
    goal TEXT,
    total_accounts INT DEFAULT 10,
    max_api_calls BIGINT DEFAULT 9999999999,
    used_api_calls BIGINT DEFAULT 0,
    expires DATE DEFAULT '9999-12-31',
    admin TINYINT DEFAULT 0,
    PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure all required columns exist in users
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE users ADD COLUMN email VARCHAR(255) NOT NULL DEFAULT "" AFTER password',
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
          AND COLUMN_NAME = 'expires') = 0,
    'ALTER TABLE users ADD COLUMN expires DATE DEFAULT ''9999-12-31'' AFTER limit_email_sent',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'admin') = 0,
    'ALTER TABLE users ADD COLUMN admin TINYINT DEFAULT 0 AFTER expires',
    'SELECT 0');
PREPARE alter_sql FROM @stmt;
EXECUTE alter_sql;
DEALLOCATE PREPARE alter_sql;

-- Drop old indexes that may have different names
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND INDEX_NAME = 'email') > 0,
    'ALTER TABLE users DROP INDEX email',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND INDEX_NAME = 'expires') > 0,
    'ALTER TABLE users DROP INDEX expires',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND INDEX_NAME = 'admin') > 0,
    'ALTER TABLE users DROP INDEX admin',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

-- Create new indexes with consistent naming
SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND INDEX_NAME = 'idx_email') = 0,
    'CREATE INDEX idx_email ON users (email)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND INDEX_NAME = 'idx_expires') = 0,
    'CREATE INDEX idx_expires ON users (expires)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

SET @stmt := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND INDEX_NAME = 'idx_admin') = 0,
    'CREATE INDEX idx_admin ON users (admin)',
    'SELECT 0');
PREPARE idx_sql FROM @stmt;
EXECUTE idx_sql;
DEALLOCATE PREPARE idx_sql;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
