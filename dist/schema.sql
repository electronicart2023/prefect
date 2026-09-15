-- Prefect Guild Merit Register — database schema
-- Import this once, empty, into a MySQL/MariaDB database your host gives you.
-- Safe to re-run: it only creates tables if they don't already exist.

CREATE TABLE IF NOT EXISTS prefects (
  id                VARCHAR(20) PRIMARY KEY,
  name              VARCHAR(120) NOT NULL,
  tier              ENUM('probation', 'junior', 'senior', 'top_board', 'head_prefect', 'principal') NOT NULL DEFAULT 'probation',
  grade             VARCHAR(40) DEFAULT NULL,
  email             VARCHAR(120) DEFAULT NULL,
  contact           VARCHAR(30) DEFAULT NULL,
  contact_primary   VARCHAR(30) DEFAULT NULL,
  contact_secondary VARCHAR(30) DEFAULT NULL,
  contact_emergency VARCHAR(30) DEFAULT NULL,
  pin               VARCHAR(255) NOT NULL,
  qr_token          VARCHAR(64) UNIQUE DEFAULT NULL,
  status            ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
  approved_by       VARCHAR(60) DEFAULT NULL,
  approved_at       DATETIME DEFAULT NULL,
  suspended_by      VARCHAR(60) DEFAULT NULL,
  suspended_at      DATETIME DEFAULT NULL,
  suspension_reason VARCHAR(255) DEFAULT NULL,
  registered_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at        DATETIME DEFAULT NULL,
  deleted_by        VARCHAR(60) DEFAULT NULL,
  INDEX idx_name (name),
  INDEX idx_status (status),
  INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id            VARCHAR(20) PRIMARY KEY,
  username      VARCHAR(60) NOT NULL UNIQUE,
  name          VARCHAR(120) NOT NULL,
  role          ENUM('developer', 'principal', 'head_prefect', 'top_board', 'senior_prefect') NOT NULL,
  passcode_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cycles (
  id         VARCHAR(20) PRIMARY KEY,
  label      VARCHAR(60) NOT NULL,
  start_date DATE NOT NULL,
  end_date   DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entries (
  id               VARCHAR(20) PRIMARY KEY,
  prefect_id       VARCHAR(20) NOT NULL,
  type             ENUM('duty','event') NOT NULL,
  entry_date       DATE NOT NULL,
  event_name       VARCHAR(120) DEFAULT NULL,
  punctuality      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  uniform          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  execution        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  initiative       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  buddhist_values  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  team_synergy     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  event_attendance TINYINT UNSIGNED NOT NULL DEFAULT 0,
  task_ownership   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  problem_solving  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  note             VARCHAR(255) DEFAULT NULL,
  logged_by        VARCHAR(60) DEFAULT NULL,
  logged_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at       DATETIME DEFAULT NULL,
  deleted_by       VARCHAR(60) DEFAULT NULL,
  FOREIGN KEY (prefect_id) REFERENCES prefects(id) ON DELETE CASCADE,
  INDEX idx_prefect_date (prefect_id, entry_date),
  INDEX idx_deleted (deleted_at),
  INDEX idx_active_date (deleted_at, entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deductions (
  id         VARCHAR(20) PRIMARY KEY,
  prefect_id VARCHAR(20) NOT NULL,
  type       VARCHAR(30) NOT NULL,
  points     SMALLINT NOT NULL,
  ded_date   DATE NOT NULL,
  note       VARCHAR(255) DEFAULT NULL,
  logged_by  VARCHAR(60) DEFAULT NULL,
  logged_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME DEFAULT NULL,
  deleted_by VARCHAR(60) DEFAULT NULL,
  FOREIGN KEY (prefect_id) REFERENCES prefects(id) ON DELETE CASCADE,
  INDEX idx_prefect_date (prefect_id, ded_date),
  INDEX idx_deleted (deleted_at),
  INDEX idx_active_date (deleted_at, ded_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance (
  id         VARCHAR(20) PRIMARY KEY,
  prefect_id VARCHAR(20) NOT NULL,
  type       ENUM('sunday', 'event') NOT NULL DEFAULT 'sunday',
  event_name VARCHAR(120) DEFAULT NULL,
  att_date   DATE NOT NULL,
  check_in   TIME NOT NULL,
  check_out  TIME DEFAULT NULL,
  late       TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_att_prefect (prefect_id),
  UNIQUE KEY uniq_prefect_shift (prefect_id, att_date, type, event_name),
  FOREIGN KEY (prefect_id) REFERENCES prefects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Single-row settings table (id is always 1)
CREATE TABLE IF NOT EXISTS settings (
  id            TINYINT PRIMARY KEY,
  passcode_hash VARCHAR(255) NOT NULL,
  late_cutoff   TIME NOT NULL DEFAULT '06:15:00',
  public_url    VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: the default Top Board passcode (GUILD2026) and the first two-month
-- cycle are created automatically the first time the app is opened —
-- api/db.php does this so the bcrypt hash is generated properly by PHP.
