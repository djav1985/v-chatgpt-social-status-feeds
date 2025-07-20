-- Create the ip_blacklist table if it doesn't exist
CREATE TABLE IF NOT EXISTS ip_blacklist (
    ip_address VARCHAR(255) NOT NULL,
    login_attempts INT DEFAULT 0,
    blacklisted BOOLEAN DEFAULT FALSE,
    timestamp BIGINT UNSIGNED,
    PRIMARY KEY (ip_address)
);

-- Ensure the ip_blacklist table has all the required columns (alter only if necessary)
ALTER TABLE ip_blacklist 
ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS blacklisted BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS timestamp BIGINT UNSIGNED;

-- Ensure indexes on ip_blacklist table
DROP INDEX IF EXISTS blacklisted ON ip_blacklist;
CREATE INDEX blacklisted ON ip_blacklist (blacklisted);

DROP INDEX IF EXISTS timestamp ON ip_blacklist;
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
ALTER TABLE status_updates 
ADD COLUMN IF NOT EXISTS username VARCHAR(255) NOT NULL,
ADD COLUMN IF NOT EXISTS account VARCHAR(255) NOT NULL,
ADD COLUMN IF NOT EXISTS status TEXT,
ADD COLUMN IF NOT EXISTS created_at DATETIME,
ADD COLUMN IF NOT EXISTS status_image VARCHAR(255);

-- Ensure indexes on status_updates table
DROP INDEX IF EXISTS username ON status_updates;
CREATE INDEX username ON status_updates (username);

DROP INDEX IF EXISTS account ON status_updates;
CREATE INDEX account ON status_updates (account);

DROP INDEX IF EXISTS created_at ON status_updates;
CREATE INDEX created_at ON status_updates (created_at);

-- Create the status_jobs table if it doesn't exist
CREATE TABLE IF NOT EXISTS status_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    account VARCHAR(255) NOT NULL,
    run_at DATETIME NOT NULL,
    status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    payload TEXT,
    UNIQUE KEY unique_job (username, account, run_at)
);

-- Ensure the status_jobs table has all the required columns (alter only if necessary)
ALTER TABLE status_jobs
ADD COLUMN IF NOT EXISTS username VARCHAR(255) NOT NULL,
ADD COLUMN IF NOT EXISTS account VARCHAR(255) NOT NULL,
ADD COLUMN IF NOT EXISTS run_at DATETIME NOT NULL,
ADD COLUMN IF NOT EXISTS status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS payload TEXT;

-- Ensure indexes on status_jobs table
DROP INDEX IF EXISTS username_idx ON status_jobs;
CREATE INDEX username_idx ON status_jobs (username);

DROP INDEX IF EXISTS run_at_idx ON status_jobs;
CREATE INDEX run_at_idx ON status_jobs (run_at);

DROP INDEX IF EXISTS account_idx ON status_jobs;
CREATE INDEX account_idx ON status_jobs (account);

ALTER TABLE status_jobs DROP INDEX IF EXISTS unique_job;
ALTER TABLE status_jobs ADD CONSTRAINT unique_job UNIQUE (username, account, run_at);

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
ALTER TABLE accounts 
ADD COLUMN IF NOT EXISTS prompt TEXT,
ADD COLUMN IF NOT EXISTS hashtags BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS link VARCHAR(255),
ADD COLUMN IF NOT EXISTS cron VARCHAR(255),
ADD COLUMN IF NOT EXISTS days VARCHAR(255),
ADD COLUMN IF NOT EXISTS platform VARCHAR(255) NOT NULL;

-- Ensure indexes on accounts table
DROP INDEX IF EXISTS username_idx ON accounts;
CREATE INDEX username_idx ON accounts (username);

DROP INDEX IF EXISTS platform_idx ON accounts;
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
    expires DATE DEFAULT '9999-12-31',
    admin TINYINT DEFAULT 0,
    PRIMARY KEY (username)
);

-- Insert default admin user **only if the users table did not previously exist**
INSERT INTO users (username, password, email, who, `where`, what, goal, total_accounts, max_api_calls, used_api_calls, admin)
SELECT 'admin', '$2y$10$4idUpn/Kgpxx.GHfyLgKWeHVyZq3ugpx1mUMC6Aze9.yj.KWKTaKG', 'admin@example.com', 'Who I am', 'Where I am', 'What I do', 'My goal', 10, 9999999999, 0, 1
WHERE @users_table_exists = 0;

-- Ensure the users table has all the required columns (alter only if necessary)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS email VARCHAR(255) NOT NULL AFTER password,
ADD COLUMN IF NOT EXISTS who TEXT AFTER email,
ADD COLUMN IF NOT EXISTS `where` TEXT AFTER who,
ADD COLUMN IF NOT EXISTS what TEXT AFTER `where`,
ADD COLUMN IF NOT EXISTS goal TEXT AFTER what,
ADD COLUMN IF NOT EXISTS expires DATE DEFAULT '9999-12-31' AFTER used_api_calls;

-- Ensure indexes on users table
DROP INDEX IF EXISTS email ON users;
CREATE INDEX email ON users (email);

DROP INDEX IF EXISTS expires ON users;
CREATE INDEX expires ON users (expires);

DROP INDEX IF EXISTS admin ON users;
CREATE INDEX admin ON users (admin);

-- Remove old unused columns from the accounts table (only if they exist)
ALTER TABLE accounts 
DROP COLUMN IF EXISTS image_prompt,
DROP COLUMN IF EXISTS cta;
