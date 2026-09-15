# Sri Kalyani Dhamma School — Prefect Guild Merit Register
# Official Project Handover Document & Comprehensive Operational Manual

**Prepared By**: Prefect Guild Technical Team & Lead Developers (Sri Kalyani Dhamma School)  
**Recipient**: School Leadership (Ven. Principal Thero, Teacher-in-Charge, Head Prefect, Top Board) & Successor Engineers  
**Product Version**: 2.6.0 (Multi-Page Architecture & Hardened RBAC Release)  
**Target Environment**: Linux cPanel / LiteSpeed / Apache (PHP 7.4.33 & PHP 8.1–8.3+ Dual-Compatible, MySQL 8.0 / MariaDB)  
**Local Staging URL**: `http://localhost/prefect/`  
**Dedicated Gate Kiosk URL**: `http://localhost/prefect/portal/scanner.php`  
**Database**: `prefect_db` (`utf8mb4_unicode_ci`)  
**Effective Date**: September 2026  

---

## 1. Executive Summary & Purpose

The **Prefect Guild Merit Register** is a standalone, non-commercial student leadership accountability, merit tracking, and dual QR gate attendance system developed exclusively for **Sri Kalyani Dhamma School**.

The system automates the merit lifecycle for student prefects across three core pillars (Sunday Dhamma School Duty, Temple Special Events, and Spiritual Conduct), providing real-time leaderboards, automated term awards, unguessable UUIDv4 attendance badges, and executive reporting for Ven. Principal Thero and the Advisory Board.

### Key Architectural Evolution (v2.6.0)
Originally prototyped as a client-side Single-Page Application (SPA) with hash routing, the system has been refactored into an enterprise-grade **Multi-Page PHP Architecture** featuring:
1. **Dedicated Page Endpoints**: Discrete PHP templates (`index.php`, `leaderboard.php`, `awards.php`, `prefects.php`, `profile.php`, `register.php`, `topboard.php`) sharing standardized headers and footers.
2. **5-Tier Role-Based Access Control (RBAC)**: Distinct permissions for Principal Thero, Head Prefect, Top Board, Senior Prefects, and Developers, plus private individual profiles for student prefects.
3. **Child Data Protection & Legal Minor Consent**: Fully aligned with Section 11 of the Sri Lanka Personal Data Protection Act (PDPA) No. 9 of 2022.
4. **Subnet-Aware Mobile Fingerprinting**: Adaptive session binding tolerating Dialog, Mobitel, and SLT mobile cell tower handovers without dropping gate marshals or students.
5. **Deterministic Cryptography**: Bcrypt (`$2y$10$`) student PIN hashing and Argon2id ($64\text{ MB}$, $t=4$) master passcode encryption.

---

## 2. Role-Based Access Control (RBAC) & Credential Matrix

The system enforces strict functional boundaries. Actions are authenticated via PHP session (`$_SESSION['user_role']`), bearer JWT tokens, or active passcode verification.

| Role | System Identifier | Default Passcode / Auth | Key Responsibilities & Capabilities |
|---|---|---|---|
| **Ven. Principal Thero** | `principal` | `THERO2026` | • **Sole executive authority** to suspend or reactivate prefect accounts.<br>• Access to complete Prefects Directory and contact records.<br>• Full access to Executive Cycle Evaluation Reports (print/CSV).<br>• Authority to permanently purge archived records older than 2 years. |
| **Head Prefect** | `head_prefect` | `HEAD2026` | • Reviews, approves, or rejects pending student registrations.<br>• Authorizes new administrative and Top Board named accounts.<br>• Access to Executive Cycle Evaluation Reports.<br>• Purges graduating student contact details for minor privacy. |
| **Top Board Member** | `top_board` | `GUILD2026` (Master) | • Unlocks Top Board operations panel and Gate Attendance Kiosk.<br>• Logs weekly Sunday Duty and Conduct pillar scores.<br>• Logs Special Event attendance and dedicated merit scores.<br>• Applies disciplinary negative point deductions.<br>• Named gate marshal accountability (deductions record active marshal). |
| **Senior Prefect** | `senior_prefect` | `SENIOR2026` | • Senior advisory tier (non-scored on active leaderboards).<br>• Assists at the temple gate with attendance roll call.<br>• Reads guild directories and reviews personal merit history. |
| **Developer** | `developer` | `DEV@2026` | • Super-administrator role for system testing, index verification, and technical maintenance. |
| **Student Prefect** | `prefect` | Name/ID + 4-Digit PIN | • Self-service login to private profile (`profile.php`).<br>• Displays individual rank, point breakdown, and activity log.<br>• Generates personal unguessable UUIDv4 attendance QR badge. |

> [!IMPORTANT]
> **Production Security Mandate**: Default passcodes (`THERO2026`, `HEAD2026`, `GUILD2026`, `SENIOR2026`, `DEV@2026`) are pre-seeded solely for initial configuration. They **MUST** be updated immediately upon live server deployment via **Top Board &rarr; Settings** or the administrative accounts manager.

---

## 3. Operational Standard Operating Procedures (SOPs)

### SOP 1: New Student Registration & PDPA Minor Consent
1. **Online Self-Service Entry**:
   - The student visits `register.php` on their smartphone or home computer.
   - Enters Full Name, Guild Tier (Probation, Junior, or Senior), Grade/Class, Email (optional), and 3 Contact Numbers (Primary Student Mobile/WhatsApp, Secondary Home, and Emergency Guardian).
   - Ticks the informational parental consent confirmation checkbox and clicks **Complete Registration**.
   - The screen reveals their newly generated **Prefect ID** (e.g. `SKP-9A4B...`), secret **4-digit PIN**, and personal **QR code**.
   - **Initial Status**: The account is created in `status = 'pending'`. The student **cannot** log into their profile or record attendance yet.
2. **Physical Parental Consent Verification (Mandatory Blocker)**:
   - To comply with Section 11 of the Sri Lanka Personal Data Protection Act No. 9 of 2022 for minors (ages 12–19), the school must distribute the paper **Dhamma School Prefect Application Form**.
   - The student's parent or legal guardian must physically sign the paper form with an ink signature.
   - The signed form is submitted to the temple office.
3. **Head Prefect Approval**:
   - Head Prefect signs into `register.php` or `prefects.php` using the Head Prefect passcode (`HEAD2026`).
   - Navigates to `prefects.php` and filters by **Status: Pending Approval**.
   - Verifies the physical paper form on file, then clicks **Approve**.
   - The student's status changes to `approved`. The student can now sign in to `profile.php` and scan at the gate.

---

### SOP 2: Sunday Morning Roll Call & Gate Kiosk Management
1. **Kiosk Setup at Temple Gate (Laptop or Tablet)**:
   - Open browser to `portal/scanner.php`.
   - The screen shows the locked gate interface.
   - The assigned Top Board marshal logs in with their Admin Username and Passcode (or Master Passcode).
   - The top banner confirms: `Gate Marshal on Duty: [Marshal Name]` and unlocks camera scanning with a **90-day persistent cookie**.
   - Ensure mode toggle is set to **Sunday Roll Call** (default).
2. **Scanning Student Badges**:
   - Arriving prefects present their QR code on their smartphone (from `profile.php` or saved screenshot).
   - Hold QR badge 15–20 cm in front of the camera (or scan using USB handheld barcode scanner).
   - **On-Time Check-In**: If scanned at or before **06:15 AM** (or configured late cutoff), the system flashes green, plays an auditory chime, and records attendance.
   - **Late Check-In**: If scanned after 06:15 AM, the system records check-in marked **LATE**, and **automatically inserts a -2 point deduction** into `deductions` (`type = 'late_arrival'`), attributing `logged_by` to the on-duty gate marshal.
3. **5-Minute Anti-Double-Scan Cooldown**:
   - If a student's QR code is accidentally re-scanned within **300 seconds (5 minutes)**, the kiosk displays a yellow warning (`Scan Too Soon!`) and blocks checkout.
4. **Checkout Scan**:
   - At the conclusion of Sunday Dhamma School (after 5 minutes), scanning the badge a second time records **Check-Out** time.
5. **Manual Roll Call Fallback**:
   - If a student's phone battery is depleted, the marshal opens the manual check-in dropdown, selects the student's name, and records attendance instantly.

---

### SOP 3: Temple Special Events Attendance
1. On `portal/scanner.php` (or in-app scanner on `topboard.php`), toggle mode from **Sunday Roll Call** to **Special Event**.
2. Enter the official event title (e.g. `Annual Katina Maha Pinkama`, `All-Night Pirith Chanting`, `Vesak Zone Shramadana`).
3. Scan arriving prefects. Attendance is recorded with `type = 'event'`.
4. **No late penalty deductions** are applied during special events.
5. *Database Note*: Unique constraint `uniq_prefect_shift` allows a student to complete both Sunday duty AND attend a special event on the exact same calendar day without duplicate key errors.

---

### SOP 4: Weekly Duty, Conduct & Event Merit Scoring
1. Log in to `topboard.php` using Top Board credentials.
2. **To Log Sunday Duty & Conduct**:
   - Under **Log Sunday Duty & Conduct Entry**, select the prefect and duty date.
   - Enter scores across the 6 pillars:
     - *Punctuality* (0–15)
     - *Uniform & Cleanliness* (0–10)
     - *Duty Execution* (0–15)
     - *Initiative & Proactiveness* (0–10)
     - *Buddhist Values & Respect* (0–10)
     - *Team Synergy & Harmony* (0–5)
   - Maximum Sunday Base: **50 duty points + 15 conduct points**.
   - Click **Save Sunday Entry**. The record is stamped with `logged_by = 'Marshal Name'`.
3. **To Log Special Event Dedication**:
   - Under **Log Special Event Entry**, select prefect, date, and event title.
   - Enter scores across the 3 pillars:
     - *Event Attendance & Presence* (0–15)
     - *Task Ownership & Reliability* (0–15)
     - *Crisis & Problem Solving* (0–5)
   - Maximum Event Base: **35 event points**.
   - Total Monthly Combined Potential: **100 Points**.

---

### SOP 5: Disciplinary Demerits & Negative Deductions
1. Under **Log Negative Deduction** in `topboard.php`:
   - Select the student and date of infraction.
   - Select infraction category:
     - *Unexcused Sunday Duty Absence*: **-5 points**
     - *Unexcused Temple Event Absence*: **-10 points**
     - *Late Arrival (Manual)*: **-2 points**
     - *Behavioral / Conduct Breach*: Variable **-5 to -10 points** (mandatory disciplinary note required).
   - Click **Log Deduction**. Net scores update instantly on live leaderboards.

---

### SOP 6: Executive Cycle Evaluation Reports
1. In `topboard.php`, open the **Executive Cycle Evaluation Report** panel (accessible to Principal Thero, Head Prefect, and Top Board).
2. Select the evaluation cycle (e.g. `Cycle 1`) and optional tier filter.
3. The dashboard renders live guild metrics: Total Prefects, Average Net Score, Total Sunday Duties, Total Events, and Total Demerits, followed by the ranked standings ledger.
4. **CSV Export**: Click **Export CSV Report** to download an Excel-formatted spreadsheet with embedded UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) and formula injection neutralization.
5. **Official Print Layout**: Click **Print Official Report** (`window.print()`). The interface reformats into an official document complete with the sacred Dhamma School crest, formal headers, and three physical signature verification blocks:
   - *Ven. Principal Thero* (Seal & Signature)
   - *Teacher-in-Charge* (Seal & Signature)
   - *Head Prefect* (Seal & Signature)

---

### SOP 7: Student Private Profile Self-Service
1. Students visit `register.php` and click **Prefect Sign In (Own Profile)**.
2. Enter registered Full Name (or Prefect ID) and 4-digit PIN.
3. Upon authentication, the browser redirects to `profile.php`.
4. Displays:
   - Current Cycle Net Score (out of 100).
   - Component scores: Sunday Duty (/50), Extra Events (/35), Conduct (/15).
   - Current tier rank.
   - Activity breakdown ledger with dates and points.
   - Personal attendance QR code badge for gate scanning.
5. Student sessions use ephemeral `sessionStorage`. Closing the browser window clears cached data on shared library computers.

---

## 4. Technical Architecture & Complete Directory Map

```
c:/xampp/htdocs/prefect/
├── .agents/
│   └── AGENTS.md            # Non-negotiable AI Agent & Developer Governance Rules
├── .htaccess                # Apache military defense shield, security headers & HTTPS redirect
├── CHANGELOG.md             # Master chronological engineering changelog
├── HANDOVER.md              # THIS MANUAL (Authoritative operational guide)
├── README.md                # Project introduction & basic setup instructions
├── db_config.php            # MySQL database credentials & cryptographic salts (chmod 600)
├── schema.sql               # Production database schema (MySQL 8.0 / MariaDB 10.4+)
│
├── includes/
│   ├── header.php           # Global HTML header, stylesheet, navigation tabs, DevTools blocker
│   └── footer.php           # Global footer, 30-min idle session timer, toast notification engine
│
├── portal/
│   └── scanner.php          # Dedicated Gate Attendance Kiosk with 90-day persistence & camera scanner
│
├── index.php                # View 1: Public Guild Overview & Pillar Metrics
├── leaderboard.php          # View 2: Real-Time Merit Rankings & Tier Filters
├── awards.php               # View 3: Guild Achievement Badges & Award Criteria Showcase
├── prefects.php             # View 4: Prefects Directory, Status Admin & Contact Purge Tool
├── profile.php              # View 5: Student Private Merit Profile & Personal QR Badge
├── register.php             # View 6: Student Registration & Dual Sign-In Portal
├── topboard.php             # View 7: Top Board Operations, Scoring, Deductions & Executive Reports
│
└── api/
    ├── attendance.php       # QR check-in engine, 5-min cooldown, late deduction transaction
    ├── auth.php             # Admin authentication, session regeneration, logout
    ├── bootstrap.php        # Initial payload loader, minor surname masking, CSRF token issuance
    ├── csrf.php             # 256-bit cryptographic CSRF token generation & validation
    ├── cycles.php           # 2-month evaluation cycle lifecycle management
    ├── db.php               # PDO connection, clean_string(), rate limiter, bot traps, fingerprinting
    ├── deductions.php       # Demerit deduction logging & soft-deletion
    ├── entries.php          # Sunday duty & event score logging & soft-deletion
    ├── jwt.php              # HS256 JWT parser with client subnet binding
    ├── lookup.php           # Student PIN authentication & profile data retrieval
    ├── migrate_indexes.php  # Database covering index verification utility
    ├── mobile_auth.php      # Mobile app JWT token issuer (+7 days validity)
    ├── prefects_admin.php   # Approval, rejection, suspension, admin creation, contact/archive purge
    ├── public_url.php       # Updates public registration QR destination URL
    ├── register.php         # Student registration & unguessable UUIDv4 qr_token generation
    ├── report.php           # Executive cycle evaluation report generator (JSON & CSV export)
    ├── roster.php           # Soft-delete removal of prefects from active roster
    ├── session.php          # Live session query & active role check
    └── settings.php         # Argon2id master passcode updates & late cutoff configuration
```

---

## 5. Security Fortress Specifications

The application incorporates 8 layers of cybersecurity:

1. **Argon2id Passcode Encryption**:
   - Master and administrative passcodes use `PASSWORD_ARGON2ID` ($m=65536\text{ KB}$, $t=4$, $p=1$) in `api/db.php`.
2. **Explicit Bcrypt PIN Storage**:
   - Student 4-digit PINs are stored as `VARCHAR(255)` hashes using `password_hash($pin, PASSWORD_BCRYPT)`. Timing-safe `password_verify()` prevents side-channel analysis.
3. **Anti-CSRF Protection (256-Bit)**:
   - `api/csrf.php` generates cryptographically random tokens via `random_bytes(32)`.
   - Verified on all state-modifying requests (`POST`, `DELETE`) via `X-CSRF-Token` headers.
4. **Subnet-Aware Anti-Hijacking Fingerprinting**:
   - Sessions bind to `HMAC-SHA256(Client IPv4 Subnet /24 | User-Agent)`.
   - Accommodates Sri Lankan cellular carrier handovers (Dialog, Mobitel, SLT CGNAT) while blocking stolen session cookies replayed from foreign networks or browsers.
5. **30-Minute Idle Session Timeout**:
   - Server sliding window (`SESSION_IDLE_TIMEOUT = 1800`) coupled with client-side event listeners in `includes/footer.php` automatically locks Top Board access after 30 minutes of inactivity.
6. **Dual-Layer Script Sanitization & XSS Defense**:
   - Server-side `clean_string()` in `api/db.php` purges HTML tags, non-printable control characters, and `javascript:` URIs.
   - Output templates escape all dynamic attributes and HTML with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. Token scan confirms 0 unescaped user reflections.
7. **Production DevTools Shield**:
   - Right-click context menus are disabled; inspection keyboard shortcuts (`F12`, `Ctrl+Shift+I/J/C`, `Ctrl+U`) are intercepted.
8. **Apache Fortress Shield (`.htaccess`)**:
   - Blocks direct web access to `db_config.php`, `schema.sql`, `.env`, and git archives (HTTP 403).
   - Enforces `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, and `Permissions-Policy`.
   - Enforces automatic 301 redirection from HTTP to HTTPS with HSTS headers.

---

## 6. Production Deployment Runbook (cPanel / LiteSpeed Host)

Follow these steps when moving the codebase from staging to the school's live domain:

### Step 1: Physical Parental Consent Verification (PDPA Mandatory Blocker)
Distribute the physical Dhamma School Admission / Prefect Application Form. No student registrations may be approved on the live system until physical paper forms bearing parental ink signatures are on file in the temple office.

### Step 2: Database Provisioning
1. Log into your hosting control panel (cPanel / DirectAdmin).
2. Create a new MySQL database: `skds_prefect_db`.
3. Create a MySQL database user with a strong password (20+ characters): `skds_db_user`.
4. Grant **ALL PRIVILEGES** to the user on `skds_prefect_db`.
5. Open phpMyAdmin, select `skds_prefect_db`, and import [schema.sql](file:///c:/xampp/htdocs/prefect/schema.sql).

### Step 3: Configure Database Credentials & Cryptographic Salts
Edit `db_config.php` on the live server:
```php
<?php
return [
    'host'          => 'localhost',
    'dbname'        => 'skds_prefect_db',
    'user'          => 'skds_db_user',
    'pass'          => 'YourStrongProductionPasswordHere#2026',
    'jwt_secret'    => 'PASTE_64_CHAR_HEX_HERE',
    'passcode_salt' => 'PASTE_64_CHAR_HEX_HERE',
];
```
*To generate unique 64-character hex strings, run via terminal:*
```bash
php -r "echo bin2hex(random_bytes(32));"
```
*Set file permissions on Linux shared hosting:*
```bash
chmod 600 db_config.php
```

### Step 4: Upload Codebase & Verify Permissions
1. Upload the entire contents of `prefect/` into your domain's document root (e.g. `/home/user/public_html/prefect/`).
2. Confirm `.htaccess` is uploaded (ensure hidden dotfiles are visible in cPanel File Manager).
3. Confirm Apache modules `mod_headers` and `mod_rewrite` are active.

### Step 5: Enable HTTPS Certificate
Activate Free Let's Encrypt / cPanel AutoSSL. The `.htaccess` file will automatically redirect all visitors from `http://` to `https://` and activate HTTP Strict Transport Security (HSTS). *HTTPS is strictly required for mobile camera viewfinder access.*

### Step 6: Rotate Initial Administrative Passcodes
1. Visit `https://yourdomain.com/prefect/register.php`.
2. Sign in with Top Board passcode `GUILD2026`, open Settings, and update the master passcode.
3. Sign in with Head Prefect passcode `HEAD2026`, open `prefects.php`, and create individual named administrator accounts for active Top Board leaders.

### Step 7: Create Gate Kiosk Tablet Shortcut
On the dedicated gate tablet or laptop:
1. Open `https://yourdomain.com/prefect/portal/scanner.php`.
2. Sign in with Top Board credentials.
3. In Chrome/Safari, tap **Add to Home Screen** to create a full-screen kiosk launcher.

---

## 7. Maintenance, Backups & Soft-Delete Recovery

### Automated Weekly Database Backup
To prevent the database password from being exposed in Linux process listings (`ps aux`), system process trees, or cron execution logs on shared cPanel hosting, **never pass credentials directly in the command line**.

1. Create a secure client credentials file in your home directory (outside `public_html`):
   ```bash
   nano ~/.my.cnf
   ```
2. Add your database credentials:
   ```ini
   [client]
   user = skds_db_user
   password = "YourStrongProductionPasswordHere#2026"
   ```
3. Restrict permissions so only your Linux user can read it:
   ```bash
   chmod 600 ~/.my.cnf
   ```
4. Add this clean cron job in cPanel (runs every Sunday at 11:00 PM after Dhamma school closes):
   ```bash
   mysqldump skds_prefect_db > /home/user/backups/prefect_backup_$(date +\%Y\%m\%d).sql
   ```
   *`mysqldump` will automatically authenticate using `~/.my.cnf` with zero credentials exposed in process tables or history.*

### Soft-Delete Restoration Queries
All student removals, score deletions, and penalty deletions execute non-destructive soft-deletes (`deleted_at = NOW()`). To recover accidentally deleted records:

```sql
-- Restore a removed prefect:
UPDATE prefects SET deleted_at = NULL, deleted_by = NULL WHERE id = 'SKP-XXXXXXXXXXXX';

-- Restore a deleted duty score:
UPDATE entries SET deleted_at = NULL, deleted_by = NULL WHERE id = 'E-XXXXXXXXXXXX';

-- Restore a deleted deduction:
UPDATE deductions SET deleted_at = NULL, deleted_by = NULL WHERE id = 'D-XXXXXXXXXXXX';
```

### Two-Year Data Retention Purge
Under Sri Lanka PDPA guidelines, historical records should not be held indefinitely. Ven. Principal Thero can trigger the **Purge Archive (2+ Years)** action in `prefects.php` to permanently delete soft-deleted records older than 2 years:
```sql
DELETE FROM entries WHERE deleted_at IS NOT NULL AND deleted_at < NOW() - INTERVAL 2 YEAR;
DELETE FROM deductions WHERE deleted_at IS NOT NULL AND deleted_at < NOW() - INTERVAL 2 YEAR;
DELETE FROM prefects WHERE deleted_at IS NOT NULL AND deleted_at < NOW() - INTERVAL 2 YEAR;
```

---

## 8. Non-Negotiable Governance Rules for Future Engineers (`AGENTS.md`)

Any human developer or AI coding agent maintaining this repository must strictly obey:

1. **Strict UI Invariance**: Never alter color variables (`--maroon`, `--parchment`, `--saffron`), font families (`Georgia`), margins, or Buddhist parchment styling.
2. **Zero Commercial Branding**: Never inject commercial corporate logos, external links, licensing paywalls, or third-party company attributions.
3. **Dual PHP 7.4.33 & PHP 8.1–8.3+ Compatibility**: Never introduce PHP 8+ breaking syntax (`match()`, union types, constructor promotion, nullsafe `?->`). Always verify with `php -l`.
4. **No PowerShell UTF-8 BOM**: Never use `Out-File -Encoding utf8` in PowerShell. The 3-byte BOM breaks PHP's `declare(strict_types=1);`.
5. **Asia/Colombo Timezone**: Always evaluate check-ins and late cutoffs against `Asia/Colombo` (UTC +05:30).
6. **Maintain Master Documentation**: Every architectural update must be documented in `CHANGELOG.md`.

---

*Sri Kalyani Dhamma School · Prefect Guild Merit Register · Official Handover Document (v2.6.0)*