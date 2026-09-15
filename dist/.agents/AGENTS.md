# Sri Kalyani Dhamma School — Prefect Guild Merit Register
# AI Agent & Human Developer Guidance System (`.agents/AGENTS.md`)

This repository contains the standalone, non-commercial Student Leadership Merit, Accountability & Dual QR Attendance System developed for **Sri Kalyani Dhamma School**.

Any AI coding agent (Antigravity, Claude, Cursor, Copilot) or human software engineer maintaining, extending, or deploying this codebase **MUST STRICTLY COMPLY WITH THE RULES BELOW**.

---

## 1. Non-Negotiable Core Rules

### Rule 1: Strict UI & Visual Invariance (Zero Layout/Style Changes)
* **NEVER modify** the visual layout, color palette, typography, margins, or CSS variables in `index.html`.
* The visual identity—parchment backgrounds (`--parchment`), maroon cards/headers (`--maroon`), saffron accents (`--saffron`), serif headings (`Georgia`), and the Dhamma School emblem (`SKDS`)—has been formally reviewed and approved by Principal Thero.
* Do not introduce modern flat frameworks (Tailwind, Bootstrap, etc.) that alter this aesthetic.

### Rule 2: Brand Identity Preservation (Zero Commercial Branding)
* This is an independent, non-commercial community project for **Sri Kalyani Dhamma School**.
* **NEVER inject** external company branding (Pencil Codes, PixelSuite, KSR Networks, etc.), commercial license verification hooks, or commercial copyright headers.
* All user-facing text, headers, and seals must solely reflect Sri Kalyani Dhamma School.

### Rule 3: Dual PHP 7.4.33 & PHP 8.1–8.3+ Compatibility
* The school's current target host runs **PHP 7.4.33** with PDO MySQL on Apache / LiteSpeed, while modern environments run **PHP 8.1 to 8.3+**. The codebase is engineered for dual compatibility across both runtimes.
* To guarantee seamless execution without syntax errors on PHP 7.4 or deprecation warnings on PHP 8+:
  - **Avoid PHP 8-only syntax**: do not use `match` expressions (use `switch` or associative arrays), union types (`int|string`), constructor property promotion, nullsafe operator (`?->`), or named arguments.
  - **Avoid PHP 8+ deprecated patterns**: avoid passing null to non-nullable internal functions, ensure strict types, and use explicit parameter types.
* Always verify syntax compatibility using `php -l <file>` before committing.

### Rule 4: Windows PowerShell UTF-8 BOM Warning (Critical Gotcha)
* When editing or writing PHP files via Windows PowerShell, **NEVER use** `Out-File -Encoding utf8` or `Set-Content -Encoding utf8`.
* PowerShell adds a 3-byte UTF-8 Byte Order Mark (`EF BB BF`), which breaks PHP's `declare(strict_types=1);` and causes fatal HTTP 500 errors (`strict_types declaration must be the very first statement in the script`).
* Always write UTF-8 files **without BOM** (using binary byte writes or clean PHP scripts).

### Rule 5: Standardized Sri Lanka Timezone (`Asia/Colombo`)
* The school operates in Sri Lanka (UTC +05:30).
* `date_default_timezone_set('Asia/Colombo');` is strictly enforced in `api/db.php`.
* Attendance check-ins, late cutoff evaluations (default `06:15 AM`), deductions, and cycle date calculations must strictly evaluate against Sri Lanka local time.

---

## 2. System Architecture & Directory Map

```
C:/xampp/htdocs/prefect/
├── .agents/
│   └── AGENTS.md            # Master Agent & Developer Governance Rules
├── .htaccess                # Military fortress Apache security shield & headers
├── CHANGELOG.md             # Master changelog (all additions/fixes documented)
├── HANDOVER.md              # Operational handover guide for school & developers
├── README.md                # Project overview, installation, and deployment guide
├── db_config.php            # MySQL credentials (ignored by git / blocked by .htaccess)
├── index.html               # Single-Page Application (HTML5, Vanilla JS, CSS3)
├── portal/
│   └── scanner.php          # Dedicated Gate Attendance Kiosk (90-day session)
├── schema.sql               # Production database schema (MySQL 8.0 / MariaDB)
└── api/
    ├── attendance.php       # Dual-mode QR attendance & 5-min cooldown engine
    ├── auth.php             # Web session authentication against Argon2id hash
    ├── bootstrap.php        # Initial payload, CSRF issuance & active records
    ├── csrf.php             # 256-bit CSRF token generation & validation
    ├── cycles.php           # 2-month evaluation cycle lifecycle management
    ├── db.php               # PDO DB connection, rate limiter, bot trap, sanitization
    ├── deductions.php       # Negative point deduction logging & soft deletes
    ├── entries.php          # Sunday duty & event score logging & soft deletes
    ├── jwt.php              # Pure PHP 7.4 HS256 JWT parser with client binding
    ├── lookup.php           # Prefect score lookup via 4-digit PIN (rate-limited)
    ├── mobile_auth.php      # Mobile app JWT token issuer (+7 days validity)
    ├── public_url.php       # Updates public registration QR destination URL
    ├── register.php         # Prefect registration + UUIDv4 qr_token generation
    ├── roster.php           # Soft-delete removal of prefects from active roster
    └── settings.php         # Passcode hashing (Argon2id) & late cutoff update
```

---

## 3. Security Fortress Specifications

The system implements 8 layers of defensive cybersecurity:

1. **Password Hashing Standard: Argon2id**:
   - Master passcode uses `PASSWORD_ARGON2ID` with `memory_cost = 65536` (64 MB), `time_cost = 4`, `threads = 1`.
   - Auto-migrates older hashes upon successful login.
2. **256-Bit Anti-CSRF Token Validation**:
   - `api/csrf.php` generates cryptographically secure 256-bit random tokens via `random_bytes(32)`.
   - Verified via `X-CSRF-Token` header on all mutation requests (`POST`, `DELETE`).
3. **Session & Token Anti-Hijacking Fingerprinting**:
   - Server computes client fingerprint: `HMAC-SHA256(Client_IP + User_Agent)`.
   - If an attacker intercepts the session cookie or JWT and replays it from a different IP or browser, the session is instantly killed with HTTP 401.
4. **30-Minute Idle Session Timeout**:
   - Sliding 30-minute inactivity window. Both server and client automatically lock Top Board access if no mouse, keyboard, or touch events occur for 30 minutes.
5. **Real-Time Client & Server Script Sanitization**:
   - Client event listener intercepts form inputs to strip `<script>`, HTML tags, and `javascript:` URIs.
   - Server-side `clean_string()` in `api/db.php` purges malicious tags and non-printable control characters.
6. **Production DevTools & Tampering Shield**:
   - Right-click context menus are disabled on `index.html`.
   - Inspection keyboard shortcuts (`F12`, `Ctrl+Shift+I/J/C`, `Ctrl+U`) are intercepted and blocked.
7. **Automated Scanner & Honeypot Trap**:
   - `api/db.php` checks incoming URIs for malicious vulnerability probe signatures (`wp-admin`, `phpunit`, `eval-stdin`, `.env`, `alfa.php`, etc.) and immediately aborts with HTTP 403.
   - Max JSON payload enforced at 64 KB (HTTP 413 Payload Too Large).
8. **Apache Fortress `.htaccess` Shield**:
   - Blocks direct web access to `db_config.php`, `schema.sql`, `.env`, and backup files.
   - Sets headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Content-Security-Policy: frame-ancestors 'self'`, `X-XSS-Protection: 1; mode=block`, and `Referrer-Policy: strict-origin-when-cross-origin`.

---

## 4. Dual QR Attendance & Cooldown Specifications

### Unguessable UUIDv4 Tokens
* QR codes encode a raw 36-character UUIDv4 token (`qr_token`), **NOT web URLs**.
* Example: `daea4944-5140-4677-b90c-6a960aa6e15b`.
* Legacy IDs (`SKP-...`) are kept for internal indexing, but public QR scanning relies on the unguessable `qr_token`.

### 5-Minute Anti-Double-Scan Cooldown
* Once a prefect checks in, any subsequent scan within **300 seconds (5 minutes)** is rejected with:
  - HTTP 200, `status: 'warning'`, `action: 'cooldown'`.
  - Returns `remaining_minutes` and `remaining_seconds`.
* After 5 minutes, the next scan logs **Check-Out** (`action: 'checkout'`).
* Subsequent scans on the same day indicate completed attendance (`action: 'completed'`).

### Dual Attendance Modes
* **Sunday Roll Call (`mode: 'sunday'`)**:
  - Compares check-in time against `settings.late_cutoff` (default `06:15:00`).
  - If late: automatically inserts a `-2` point deduction into `deductions` (`type = 'late_arrival'`).
* **Special Event Attendance (`mode: 'event'`)**:
  - Requires `eventName` (e.g., *Katina Pinkama*, *Annual Pirith Chanting*).
  - No late arrival deductions are applied.
* **Database Unique Constraint**:
  - `UNIQUE KEY uniq_prefect_shift (prefect_id, att_date, type, event_name)`.
  - Allows a prefect to complete Sunday duty roll call AND attend one or more special events on the same calendar day without duplicate key collisions.

### Two Scanner Interfaces
1. **Dedicated Kiosk (`portal/scanner.php`)**:
   - Intended for unattended laptops/tablets stationed at the school gate.
   - Unlocked via Top Board passcode with a 90-day persistent cookie (`PREFECT_KIOSK_SESSION`).
   - Uses `html5-qrcode` camera viewfinder + supports physical USB handheld barcode scanners (Enter key detection).
2. **In-App Camera Scanner (`index.html#topboard` & `index.html#scanner`)**:
   - Embedded inside `#topboardPanel` under Sunday roll call card.
   - Also accessible via direct route `index.html#scanner`.
   - Synthesizes audio chimes via Web Audio API (`playScanTone`) so gate staff hear instant auditory confirmation.

---

## 5. Database Schema & Data Integrity

* **Engine**: MariaDB / MySQL 8.0 with `utf8mb4_unicode_ci`.
* **Soft Deletes**:
  - `prefects`, `entries`, and `deductions` include `deleted_at DATETIME NULL`.
  - Hard `DELETE` operations are strictly forbidden. Deletions execute `UPDATE table SET deleted_at = NOW() WHERE id = :id`.
  - All read queries in `bootstrap.php` must filter `WHERE deleted_at IS NULL`.
* **ID Formatting**:
  - Prefects: `SKP-[16-CHAR-HEX]`
  - Attendance: `A-[16-CHAR-HEX]`
  - Entries: `E-[16-CHAR-HEX]`
  - Deductions: `D-[16-CHAR-HEX]`
  - Cycles: `C-[16-CHAR-HEX]`

---

## 6. Guidelines for Future AI Agents & Developers

1. **Do Not Break Single-Page Application (SPA) Routing**:
   - `VIEWS = ['overview', 'register', 'mypoints', 'leaderboard', 'awards', 'topboard', 'checkin', 'scanner']`.
   - New views must follow the `#view-<name>` structure and update the router array.
2. **Preserve Scoring Engine Constants**:
   - Sunday Duty max: 50 points (Punctuality 15, Uniform 10, Execution 15, Initiative 10).
   - Conduct max: 15 points (Buddhist Values 10, Team Synergy 5).
   - Event max: 35 points (Attendance 15, Task Ownership 15, Problem Solving 5).
   - Monthly Base: 100 points across the three pillars.
3. **Always Log Architectural Changes**:
   - Update `CHANGELOG.md` immediately following any modification, describing the "Why" as well as the "What".
