-- =========================
-- Fresh Database Installation Script
-- For new installations only - use upgrade.sql for existing databases
-- =========================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =========================
-- accounts
-- =========================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- ip_blacklist
-- =========================
CREATE TABLE ip_blacklist (
    ip_address VARCHAR(255) NOT NULL,
    login_attempts INT DEFAULT 0,
    blacklisted BOOLEAN DEFAULT FALSE,
    timestamp BIGINT UNSIGNED,
    PRIMARY KEY (ip_address),
    INDEX idx_blacklisted (blacklisted),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- status_updates
-- =========================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- status_jobs
-- =========================
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
-- users
-- =========================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin user (password: admin)
INSERT INTO users (username, password, email, who, `where`, what, goal, total_accounts, max_api_calls, used_api_calls, limit_email_sent, admin)
VALUES ('admin', '$2y$10$4idUpn/Kgpxx.GHfyLgKWeHVyZq3ugpx1mUMC6Aze9.yj.KWKTaKG', 'admin@example.com', 'Who I am', 'Where I am', 'What I do', 'My goal', 10, 9999999999, 0, 0, 1);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
