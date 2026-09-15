-- ====================================================================
-- Sri Kalyani Dhamma School — Prefect Guild Merit Register
-- Complete Production Database Setup (prefect_full_db.sql)
-- Target Runtimes: MySQL 8.0+ / MariaDB 10.4+ with PHP 7.4.33 - 8.3+
-- Timezone Standard: Asia/Colombo (UTC +05:30)
-- ====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------------------
-- Table structure for prefects
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prefects` (
  `id`                VARCHAR(20) NOT NULL,
  `name`              VARCHAR(120) NOT NULL,
  `tier`              ENUM('probation', 'junior', 'senior', 'top_board', 'head_prefect', 'principal') NOT NULL DEFAULT 'probation',
  `grade`             VARCHAR(40) DEFAULT NULL,
  `email`             VARCHAR(120) DEFAULT NULL,
  `contact`           VARCHAR(30) DEFAULT NULL,
  `contact_primary`   VARCHAR(30) DEFAULT NULL,
  `contact_secondary` VARCHAR(30) DEFAULT NULL,
  `contact_emergency` VARCHAR(30) DEFAULT NULL,
  `pin`               VARCHAR(255) NOT NULL,
  `qr_token`          VARCHAR(64) UNIQUE DEFAULT NULL,
  `status`            ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
  `approved_by`       VARCHAR(60) DEFAULT NULL,
  `approved_at`       DATETIME DEFAULT NULL,
  `suspended_by`      VARCHAR(60) DEFAULT NULL,
  `suspended_at`      DATETIME DEFAULT NULL,
  `suspension_reason` VARCHAR(255) DEFAULT NULL,
  `registered_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`        DATETIME DEFAULT NULL,
  `deleted_by`        VARCHAR(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_name` (`name`),
  INDEX `idx_status` (`status`),
  INDEX `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table structure for admin_users
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            VARCHAR(20) NOT NULL,
  `username`      VARCHAR(60) NOT NULL UNIQUE,
  `name`          VARCHAR(120) NOT NULL,
  `role`          ENUM('developer', 'principal', 'head_prefect', 'top_board', 'senior_prefect') NOT NULL,
  `passcode_hash` VARCHAR(255) NOT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table structure for cycles
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cycles` (
  `id`         VARCHAR(20) NOT NULL,
  `label`      VARCHAR(60) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date`   DATE NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table structure for entries (Sunday Duty & Special Event Scores)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `entries` (
  `id`               VARCHAR(20) NOT NULL,
  `prefect_id`       VARCHAR(20) NOT NULL,
  `type`             ENUM('duty','event') NOT NULL,
  `entry_date`       DATE NOT NULL,
  `event_name`       VARCHAR(120) DEFAULT NULL,
  `punctuality`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `uniform`          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `execution`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `initiative`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `buddhist_values`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `team_synergy`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `event_attendance` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `task_ownership`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `problem_solving`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `note`             VARCHAR(255) DEFAULT NULL,
  `logged_by`        VARCHAR(60) DEFAULT NULL,
  `logged_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`       DATETIME DEFAULT NULL,
  `deleted_by`       VARCHAR(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`prefect_id`) REFERENCES `prefects`(`id`) ON DELETE CASCADE,
  INDEX `idx_prefect_date` (`prefect_id`, `entry_date`),
  INDEX `idx_deleted` (`deleted_at`),
  INDEX `idx_active_date` (`deleted_at`, `entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table structure for deductions (Disciplinary -2 Point Records)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `deductions` (
  `id`         VARCHAR(20) NOT NULL,
  `prefect_id` VARCHAR(20) NOT NULL,
  `type`       VARCHAR(30) NOT NULL,
  `points`     SMALLINT NOT NULL,
  `ded_date`   DATE NOT NULL,
  `note`       VARCHAR(255) DEFAULT NULL,
  `logged_by`  VARCHAR(60) DEFAULT NULL,
  `logged_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `deleted_by` VARCHAR(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`prefect_id`) REFERENCES `prefects`(`id`) ON DELETE CASCADE,
  INDEX `idx_prefect_date` (`prefect_id`, `ded_date`),
  INDEX `idx_deleted` (`deleted_at`),
  INDEX `idx_active_date` (`deleted_at`, `ded_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table structure for attendance (Dual QR Sunday & Event Scans)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
  `id`         VARCHAR(20) NOT NULL,
  `prefect_id` VARCHAR(20) NOT NULL,
  `type`       ENUM('sunday', 'event') NOT NULL DEFAULT 'sunday',
  `event_name` VARCHAR(120) DEFAULT NULL,
  `att_date`   DATE NOT NULL,
  `check_in`   TIME NOT NULL,
  `check_out`  TIME DEFAULT NULL,
  `late`       TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_att_prefect` (`prefect_id`),
  UNIQUE KEY `uniq_prefect_shift` (`prefect_id`, `att_date`, `type`, `event_name`),
  FOREIGN KEY (`prefect_id`) REFERENCES `prefects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table structure for settings (Single Row id = 1)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id`            TINYINT NOT NULL,
  `passcode_hash` VARCHAR(255) NOT NULL,
  `late_cutoff`   TIME NOT NULL DEFAULT '06:15:00',
  `public_url`    VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Pre-Seeded Default Records (Settings, Admin Roles & Cycle 1)
-- --------------------------------------------------------------------
INSERT INTO `settings` (`id`, `passcode_hash`, `late_cutoff`, `public_url`) VALUES (1, '$argon2id$v=19$m=65536,t=4,p=1$cHRhTmxtcEV6T3BRVU1hMA$OXcFbZr2d9NlPgy4rRmjEgyQQgZ4mIly8aByjc8j1DU', '06:15:00', NULL) ON DUPLICATE KEY UPDATE `late_cutoff` = '06:15:00';
INSERT INTO `cycles` (`id`, `label`, `start_date`, `end_date`) VALUES ('CYC-57251E5897BBCC02', 'Cycle 2', '2026-11-06', '2027-01-05') ON DUPLICATE KEY UPDATE `label` = 'Cycle 2';
INSERT INTO `cycles` (`id`, `label`, `start_date`, `end_date`) VALUES ('CYC-866E0B0346', 'Cycle 1', '2026-09-06', '2026-11-05') ON DUPLICATE KEY UPDATE `label` = 'Cycle 1';
INSERT INTO `admin_users` (`id`, `username`, `name`, `role`, `passcode_hash`) VALUES ('ADM-DEV01', 'developer', 'Developer (Super Admin)', 'developer', '$argon2id$v=19$m=65536,t=4,p=1$cjJzbTFoN1V2TWovaXlKOQ$nT81hkuTZe6Ie7fHe6956tJPwEITc6I+qUTylKuFW1M') ON DUPLICATE KEY UPDATE `role` = 'developer';
INSERT INTO `admin_users` (`id`, `username`, `name`, `role`, `passcode_hash`) VALUES ('ADM-HED01', 'headprefect', 'Head Prefect', 'head_prefect', '$argon2id$v=19$m=65536,t=4,p=1$UGlHUXU4ZWU1aHhqWTI4Lg$UhH3n3P1JjqE0w6XnMPapA6DP62tLipochFUoAHicok') ON DUPLICATE KEY UPDATE `role` = 'head_prefect';
INSERT INTO `admin_users` (`id`, `username`, `name`, `role`, `passcode_hash`) VALUES ('ADM-PRI01', 'principal', 'Principal Thero', 'principal', '$argon2id$v=19$m=65536,t=4,p=1$NXpkT2RkckdkLlZNUzJSYw$RRd0Ine9eUZEzVa6o8y12WzqCmJBjXc2sYUXDtWKal0') ON DUPLICATE KEY UPDATE `role` = 'principal';
INSERT INTO `admin_users` (`id`, `username`, `name`, `role`, `passcode_hash`) VALUES ('ADM-SNR01', 'senior', 'Senior Prefect Desk', 'senior_prefect', '$argon2id$v=19$m=65536,t=4,p=1$UmsuQ2RaajZ3T1lkcW5Jdg$3WKzAbjjNwIfMmqJ06mhzI7UdatlrnnlYqlTJLy416o') ON DUPLICATE KEY UPDATE `role` = 'senior_prefect';
INSERT INTO `admin_users` (`id`, `username`, `name`, `role`, `passcode_hash`) VALUES ('ADM-TOP01', 'topboard', 'Top Board Operations', 'top_board', '$argon2id$v=19$m=65536,t=4,p=1$Yk84bnR2YmEyMVVUQmt0VA$iJPswLdn0ecejWtICCuamAHEgF+1sJLpO78ezQ7i8l8') ON DUPLICATE KEY UPDATE `role` = 'top_board';

SET FOREIGN_KEY_CHECKS = 1;
