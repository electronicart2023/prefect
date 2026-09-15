# Sri Kalyani Dhamma School — Prefect Guild Merit Register

An independent, non-commercial Student Leadership Merit, Accountability & Dual QR Attendance Management System developed exclusively for **Sri Kalyani Dhamma School**.

---

## Key Features

1. **Self-Service Registration & Private PINs**:
   - New Probation (Tier 1) and Junior Leaders (Tier 2) register in under 60 seconds.
   - Automatically generates a 4-digit PIN for private lookup and an unguessable cryptographic UUIDv4 token for attendance.
2. **Dual QR Attendance Scanners**:
   - **Dedicated Gate Kiosk (`/portal/scanner.php`)**: Gated behind Top Board passcode with a 90-day persistent cookie (`PREFECT_KIOSK_SESSION`) for unattended laptops/tablets at the school gate. Continuous camera scanning + USB handheld barcode scanner input.
   - **In-App Camera Scanner (`index.html#topboard` & `index.html#scanner`)**: Built directly into the Top Board panel with real-time camera scanning and Web Audio API synthesized chimes (`playScanTone`).
3. **5-Minute Anti-Double-Scan Cooldown**:
   - Automatically rejects repeated scans within 5 minutes of check-in, preventing accidental immediate checkouts at the gate.
4. **Dual Attendance Modes (Multi-Shift Attendance)**:
   - **Sunday Roll Call (Default)**: Compares arrival times against the 6:15 AM late cutoff and logs automatic -2 point deductions.
   - **Special Event Attendance**: Records attendance for school and temple events (Pirith, Katina, Vesak Zone) without late arrival deductions. Multiple events can be recorded on the same date.
5. **Three-Pillar Scoring Engine (100 Points/Month)**:
   - Sunday Duty Performance (50 pts)
   - Extra Events & Guild Dedication (35 pts)
   - Conduct & Spiritual Leadership (15 pts)
6. **Live Guild Leaderboard & Automatic Awards Recognition**:
   - Term Awards: Best Probation Leader, Best Junior Leader, Overall Best Prefect, and 100% Commitment Award.
7. **Fortress-Grade Defensive Cybersecurity**:
   - Argon2id master passcode hashing (`m=65536, t=4, p=1`).
   - 256-bit Anti-CSRF token verification on all mutation requests.
   - Session and Token anti-hijacking cryptographic fingerprinting (`HMAC-SHA256(Client_IP + User_Agent)`).
   - 30-Minute idle session timeout (both server and client auto-lock).
   - Real-time client input sanitization and server-side script stripping.
   - Production DevTools & right-click inspection blocker.
   - Database soft deletes (`deleted_at DATETIME NULL`).
   - Automated scanner / honeypot trap (HTTP 403) and 64 KB JSON payload limit (HTTP 413).
   - Mobile app-ready HS256 JWT authentication engine.

---

## Directory Structure

```
prefect/
├── .agents/
│   └── AGENTS.md          # AI Agent & Developer Guidance Rules
├── .htaccess              # Apache military defense shield & security headers
├── CHANGELOG.md           # Master chronological audit log
├── HANDOVER.md            # Comprehensive operational handover guide
├── README.md              # THIS FILE
├── db_config.php          # Database connection settings
├── index.html             # Single-Page Application (HTML5, JS, CSS)
├── portal/
│   └── scanner.php        # Standalone gate attendance kiosk (90-day persistence)
├── schema.sql             # MySQL schema definitions
└── api/
    ├── attendance.php     # QR scan verification, 5-min cooldown, late calculation
    ├── auth.php           # Top Board passcode validation
    ├── bootstrap.php      # SPA initial load, CSRF token issuance, soft-delete filter
    ├── csrf.php           # 256-bit CSRF token generation & validation
    ├── cycles.php         # Evaluation cycle management
    ├── db.php             # Core PDO connection, rate limiter, bot trap, sanitizer
    ├── deductions.php     # Penalty deductions & soft delete removal
    ├── entries.php        # Duty/Event score logging & soft delete removal
    ├── jwt.php            # PHP 7.4 HS256 JWT parser with client binding
    ├── lookup.php         # PIN authentication & private score retrieval
    ├── mobile_auth.php    # Mobile app JWT token authentication
    ├── public_url.php     # Public URL management for registration QR
    ├── register.php       # Prefect registration + UUIDv4 qr_token generation
    ├── roster.php         # Soft-delete removal of prefects
    └── settings.php       # Argon2id passcode updater & late cutoff settings
```

---

## Requirements

- **PHP Version**: `7.4.33` (strictly compatible; no PHP 8+ features).
- **Database**: MySQL 8.0 or MariaDB 10.3+ with `PDO MySQL` extension.
- **Web Server**: Apache 2.4+ (with `mod_rewrite`, `mod_headers`) or LiteSpeed.
- **HTTPS**: Required for camera scanning permissions on mobile devices and browsers.

---

## Quick Setup Guide

1. **Database Setup**:
   - Create a MySQL database (e.g. `prefect_db`).
   - Import `schema.sql`.
2. **Database Credentials**:
   - Update `db_config.php`:
     ```php
     return [
         'host'   => '127.0.0.1',
         'dbname' => 'prefect_db',
         'user'   => 'root',
         'pass'   => '',
     ];
     ```
3. **Run Application**:
   - Web App: `http://localhost/prefect/`
   - Gate Attendance Kiosk: `http://localhost/prefect/portal/scanner.php`
4. **Default Passcode**:
   - Default Top Board passcode is `GUILD2026`. Change this immediately under **Top Board → Settings → Change Top Board passcode**.

---

## Detailed Handover & Operational Manual

For complete operational walkthroughs, teacher guides, gate duty runbooks, backup procedures, and developer architectural rules, refer to:
- [HANDOVER.md](HANDOVER.md) — Official School & Developer Handover Document
- [.agents/AGENTS.md](.agents/AGENTS.md) — Master Guidance & Guardrails for AI Agents & Engineers
- [CHANGELOG.md](CHANGELOG.md) — Master Chronological History & Version Audit Log

---
*Sri Kalyani Dhamma School · Prefect Guild Merit Register · Non-Commercial Community Project*