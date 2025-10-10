# Database Migration Guide

## Overview

The project now has separate installation and upgrade paths:
- **install.sql / install.php**: For fresh installations only
- **upgrade.sql / upgrade.php**: For migrating from old schema to new schema while preserving data

## Migration Features

### 1. Accounts Table Migration
**Old Schema:**
- Primary Key: `account` (single column)
- Index: `idx_username`

**New Schema:**
- Primary Key: `(username, account)` (composite key)
- Indexes: `idx_username`, `idx_platform`

**Migration Process:**
- Detects if old primary key structure exists
- Automatically drops old primary key and adds new composite primary key
- Preserves all existing data
- Ensures all required columns exist
- Removes deprecated columns (`image_prompt`, `cta`)

### 2. Users Table Migration
**Old Schema:**
- Missing `limit_email_sent` column

**New Schema:**
- Adds `limit_email_sent BOOLEAN DEFAULT FALSE` column

**Migration Process:**
- Detects if `limit_email_sent` column exists
- Adds column if missing with default value FALSE
- Preserves all existing user data

### 3. Index Updates
All tables receive updated index names for consistency:
- `ip_blacklist`: `idx_blacklisted`, `idx_timestamp`
- `status_updates`: `idx_username`, `idx_account`, `idx_created_at`
- `accounts`: `idx_username`, `idx_platform`
- `users`: `idx_email`, `idx_expires`, `idx_admin`

### 4. Status Jobs Table
The `status_jobs` table is always recreated with the new schema to ensure compatibility with the queue system.

## Usage

### Fresh Installation
1. Ensure database exists and configuration is correct in `config.php`
2. Navigate to `/install.php` in your browser or run via command line
3. The script will create all tables with the new schema
4. Default admin user (username: `admin`, password: `admin`) will be created
5. The script will automatically delete itself after successful installation

### Migration from Old Schema
1. **IMPORTANT: Backup your database before migration!**
   ```bash
   mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
   ```
2. Ensure all database configuration is correct in `config.php`
3. Navigate to `/upgrade.php` in your browser or run via command line
4. Confirm you have backed up your database
5. The script will:
   - Detect old schema structure
   - Migrate primary keys and indexes
   - Add missing columns (including `limit_email_sent`)
   - Preserve all existing data
   - Remove deprecated columns (`image_prompt`, `cta`)
6. The script will automatically delete itself after successful upgrade

## Database Schema After Migration

### accounts
```sql
CREATE TABLE accounts (
    account VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    prompt TEXT,
    hashtags BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    cron VARCHAR(255),
    days VARCHAR(255),
    platform VARCHAR(255) NOT NULL,
    PRIMARY KEY (username, account),
    INDEX idx_username (username),
    INDEX idx_platform (platform)
);
```

### users
```sql
CREATE TABLE users (
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
    PRIMARY KEY (username),
    INDEX idx_email (email),
    INDEX idx_expires (expires),
    INDEX idx_admin (admin)
);
```

### ip_blacklist
```sql
CREATE TABLE ip_blacklist (
    ip_address VARCHAR(255) NOT NULL,
    login_attempts INT DEFAULT 0,
    blacklisted BOOLEAN DEFAULT FALSE,
    timestamp BIGINT UNSIGNED,
    PRIMARY KEY (ip_address),
    INDEX idx_blacklisted (blacklisted),
    INDEX idx_timestamp (timestamp)
);
```

### status_updates
```sql
CREATE TABLE status_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    account VARCHAR(255) NOT NULL,
    status TEXT,
    created_at DATETIME,
    status_image VARCHAR(255),
    INDEX idx_username (username),
    INDEX idx_account (account),
    INDEX idx_created_at (created_at)
);
```

### status_jobs
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

## Migration Safety

The migration script uses the following safety measures:

1. **Conditional Alterations**: All schema changes check for existing structures before altering
2. **Data Preservation**: Primary key migrations preserve all existing rows
3. **Prepared Statements**: All dynamic SQL uses prepared statements for safety
4. **Transactional Integrity**: MySQL's DDL auto-commit ensures consistency

## Troubleshooting

### Migration Fails on Primary Key Change
**Issue**: Error when changing accounts table primary key
**Solution**: Ensure no duplicate (username, account) combinations exist in the accounts table before migration

### Missing Columns After Migration
**Issue**: Expected columns not present
**Solution**: Check MySQL/MariaDB error logs for specific ALTER TABLE failures

### Data Loss Concerns
**Issue**: Worried about data loss during migration
**Solution**: Always backup your database before running migrations:
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

## Rollback Procedure

If migration fails or causes issues:

1. Restore from backup:
```bash
mysql -u username -p database_name < backup_YYYYMMDD.sql
```

2. Review error logs to identify the issue

3. Contact support or review migration script for conflicts

## Testing the Migration

To test the migration process:

1. Create a test database with old schema
2. Insert test data
3. Run the migration script
4. Verify:
   - All data is preserved
   - Primary keys are updated
   - Indexes are correct
   - New columns have default values

## Support

For issues or questions about the migration process, please open an issue on GitHub with:
- Your current database schema
- MySQL/MariaDB version
- Error messages from the migration
- Database error logs
