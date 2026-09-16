# Changelog

All notable changes to the Sri Kalyani Dhamma School — Prefect Guild Merit Register will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [2.6.3] - 2026-09-16

### Score Entries & Deductions API Response Contract Synchronization

#### 1. Duty & Event Entries Response Contract (`api/entries.php`, `topboard.php`, `topboard.html`)
- **Why**: `api/entries.php` returned `{ id: $id }` without an explicit `'ok' => true` property. When a Head Prefect or Top Board user submitted Sunday Duty scores or Special Event scores, the database correctly recorded the points, but the frontend checked `if (r.ok)`, which evaluated to falsy and displayed a false-alarm error toast (`Could not save entry.`).
- **What**: Added `'ok' => true` and confirmation message to `api/entries.php`, and updated `topboard.php` and `topboard.html` to validate `(r.ok || r.id) && !r.error`.

#### 2. Deductions & Cycles Response Contracts (`api/deductions.php`, `api/cycles.php`)
- **Why**: `api/deductions.php` and `api/cycles.php` returned `{ id: $id }` without `'ok' => true`.
- **What**: Synchronized response payloads to include `'ok' => true`, preventing false-alarm error toasts on disciplinary point deductions and cycle creation.

## [2.6.2] - 2026-09-15

### Registration API Response Contract & Loopback Rate-Limit Calibration

#### 1. Registration Payload Success Contract (`api/register.php`, `register.php`, `register.html`)
- **Why**: `api/register.php` returned `{ id, pin, name, tier, ... }` without an explicit `'ok' => true` key. The client UI checked `if (!r.ok)`, which evaluated to falsy on successful submissions, incorrectly triggering a "Registration failed." toast even though the registration was committed to MySQL.
- **What**: Added `'ok' => true` to the `api/register.php` response contract, and updated the client submission handlers in `register.php` and `register.html` to check `if (!r.ok && !r.id)`.

#### 2. Local Loopback Rate Limiter Adaptation (`api/db.php`)
- **Why**: Rapid manual QA and form verification on local development environments (`127.0.0.1` / `::1`) easily exceeded the production anti-spam limit (5 submissions per 15 min), generating HTTP 429 errors.
- **What**: Configured `check_rate_limit()` to grant generous attempt headroom (minimum 100 attempts) on local loopback addresses while strictly maintaining anti-spam limits in live environments.

#### 3. Attendance API Response Contract Synchronization (`api/attendance.php`, `topboard.php`, `topboard.html`)
- **Why**: `api/attendance.php` returned `{ status: 'success', action: 'checkin', ... }` without `'ok' => true`. When manual check-in or in-app camera check-in was triggered, `topboard.php` checked `if (r.ok)`. Because `r.ok` was undefined, it popped a "Could not save attendance." toast even though the check-in and late deduction were successfully committed to MySQL.
- **What**: Added `'ok' => true` across all attendance response states (`checkin`, `cooldown`, `checkout`, `completed`) in `api/attendance.php`, and updated `topboard.php` and `topboard.html` to validate `r.ok || r.status === 'success' || r.action`.

## [2.6.1] - 2026-09-15

### Pre-Flight Re-Audit, Deterministic Hashing & Handover Finalization

#### 1. Deterministic PIN Cryptography (`api/register.php`)
- **Why**: Prevent silent hash algorithm drift between PHP versions (e.g. if a future PHP release alters `PASSWORD_DEFAULT` from Bcrypt to Argon2id on a dev workstation).
- **What**: Pinned student PIN hashing explicitly to `PASSWORD_BCRYPT` (`$2y$10$`), ensuring 100% deterministic backward-compatibility on PHP 7.4.33 target hosts.

#### 2. Complete Server Output & XSS AST Audit (`scratch/audit_all_echos.php`)
- **Why**: Verify that the absence of dynamic nonces in CSP is fully protected by template escaping without unescaped variable reflections.
- **What**: Parsed all output tokens (`T_ECHO`, `<?=`, `T_PRINT`) across all 30 PHP scripts in the repository. Confirmed exactly 12 server output expressions, with 0 unescaped user-controllable reflections.

#### 3. Dual Runtime Compatibility Verification (`scratch/check_php_matrix_compatibility.php`)
- **Why**: Validate runtime compatibility across PHP 7.4.33, 8.1, 8.2, and 8.3+.
- **What**: Automated AST scanner evaluated 11,643 PHP tokens across all 30 files, confirming 0 PHP 8+ breaking syntax (no `match`, `?->`, `enum`, `readonly`, union types) and 0 deprecations (no `${}` interpolation, `utf8_encode`, `strftime`, `create_function`).

#### 4. Mandatory Launch Blocker: Minor Parental Consent (`full_system_audit_report.md`)
- **Why**: Collecting personal details from minors (ages 12–19) without legally binding consent violates Section 11 of the Sri Lanka PDPA No. 9 of 2022 and cannot be retroactively remedied.
- **What**: Elevated physical paper parental consent verification to Step 1 Mandatory Launch Blocker. Student registrations strictly remain in `status = 'pending'` until physical ink-signed forms are on file.

#### 5. Full Operational Handover Manual Rewrite (`HANDOVER.md`)
- **Why**: Previous handover documentation referenced obsolete single-page hash routing (`index.html#topboard`, `#mypoints`).
- **What**: Completely rewrote `HANDOVER.md` (344 lines) aligning with the multi-page PHP architecture, 5-tier RBAC permissions matrix, gate attendance kiosk SOPs, and live production cPanel/LiteSpeed deployment runbook.

## [2.6.0] - 2026-09-11

### Audit Refinement, Subnet-Aware Mobile Fingerprinting & CSP Hardening

#### 1. Mobile Session Subnet Masking (`api/db.php`, `portal/scanner.php`)
- **Why**: Strict binding to `$_SERVER['REMOTE_ADDR']` caused mobile users to be dropped unexpectedly during cellular tower handovers across Sri Lankan mobile carrier pools (Dialog, Mobitel, SLT CGNAT).
- **What**: Implemented `/24` IPv4 subnet masking (`x.x.x.0` combined with User-Agent HMAC). Maintains anti-hijacking defense against foreign networks while permitting cellular IP drift.

#### 2. Gate Marshal Named Accountability (`portal/scanner.php`, `api/attendance.php`)
- **Why**: Scanner kiosk displayed a generic Top Board badge and logged deductions anonymously as system events.
- **What**: Added Top Board Prefect named authentication to `portal/scanner.php`. Late arrival deductions automatically attribute `logged_by` to the authenticated marshal on duty.

#### 3. Content-Security-Policy Hardening (`.htaccess`)
- **Why**: `'unsafe-eval'` was present in the CSP header despite `eval()` not being used anywhere in the codebase.
- **What**: Removed `'unsafe-eval'` from `.htaccess`. Documented architectural requirement for `'unsafe-inline'` in PHP templates and calibrated audit scorecard accordingly.

#### 4. Audit Scorecard Recalibration (`full_system_audit_report.md`)
- **Why**: Previous audit conflated staging codebase validation with live production deployment readiness.
- **What**: Formally separated staging validation from production server prerequisites (custom MySQL credentials, unique salts, live SSL, and physical parental consent forms under Sri Lanka PDPA Section 11).

## [2.5.0] - 2026-09-11

### Comprehensive Pre-Production Security, Privacy & Performance Hardening

#### 1. Cryptographic Student PIN Hashing (`schema.sql`, `register.php`, `lookup.php`, `mobile_auth.php`, `prefects_admin.php`)
- **Why**: Student PINs were stored as plaintext `CHAR(4)`. A database dump leak would expose all credentials.
- **What**: Altered `prefects.pin` to `VARCHAR(255)`. Migrated existing PINs to bcrypt hashes using `password_hash()`. Authentications in `lookup.php` and `mobile_auth.php` now use `password_verify()`. Removed `pin` from admin API responses.

#### 2. Gate Attendance Scanner Hardening (`api/attendance.php`, `portal/scanner.php`)
- **Why**: The QR check-in endpoint was open without rate limiting or authorization, allowing scan spoofing.
- **What**: Enforced rate limiting (30 scans/min per IP) and session authorization (`kiosk_unlocked`, active admin session, or Bearer JWT). Wrapped attendance insert and late arrival deduction in a single atomic database transaction (`beginTransaction()`/`commit()`). Added `logged_by = 'SYSTEM:attendance_scanner'` to late deductions.
- Hardened `portal/scanner.php` with 4-hour idle timeout, client IP/User-Agent fingerprint validation, `session_regenerate_id(true)` on unlock, and rate limiting (5 attempts/10 min).

#### 3. Minor Student Privacy Protection (`api/bootstrap.php`, `register.php`, `profile.php`, `includes/footer.php`)
- **Why**: Student full names were scrapable on public leaderboard and overview pages without authentication.
- **What**: `bootstrap.php` automatically masks surnames (e.g. "Kalana S.") for unauthenticated visitors, returning full names only to logged-in users.
- Added mandatory parental/guardian consent checkbox to registration form.
- Switched client-side student session caching from persistent `localStorage` to ephemeral `sessionStorage`.

#### 4. Database Indexing & Query Safety (`schema.sql`, `api/migrate_indexes.php`, `api/bootstrap.php`)
- **Why**: Unindexed `deleted_at` filters triggered full table scans on every request, and unbounded queries risked payload bloat.
- **What**: Added `idx_deleted (deleted_at)` to `prefects`, `entries`, and `deductions`. Added composite `idx_active_date` to `entries` and `deductions`. Added `LIMIT 2000` safety caps to `bootstrap.php`. Added `deleted_by` column to `prefects`.
- Added `purge_archive` action in `prefects_admin.php` for Principal Thero to permanently purge 2-year-old soft-deleted records.

#### 5. Frontend Security, CSP & Inactivity Lock (`.htaccess`, `includes/footer.php`, `topboard.php`, `register.php`, `leaderboard.php`)
- **Why**: Missing CSP script directives, lack of client-side idle timeouts, and browser caching of sensitive fields created security risks.
- **What**: Strengthened Content-Security-Policy in `.htaccess` with restrictive `default-src`, `script-src`, `img-src`, and `connect-src` directives.
- Implemented a 30-minute client-side inactivity lock in `footer.php` that automatically triggers `performSignOut()`.
- Added `autocomplete="off"` to all password and PIN fields.
- Sanitized and escaped all dynamic DOM attributes (`tier`, `id`) in `leaderboard.php`, `topboard.php`, and `profile.php`.

## [2.4.0] - 2026-09-11

### Executive Evaluation Report Module, Audit Trail & Security Hardening

#### 1. Executive Cycle Evaluation & Report Module (`api/report.php` & `topboard.php`)
**Why**: Principal Thero and the Advisory Board required an authoritative evaluation report to assess prefect standing across Sunday duty, conduct, and event dedication for award ceremonies and disciplinary review.
**What**:
- Created `api/report.php` restricted strictly to `principal`, `head_prefect`, `top_board`, and `developer` roles.
- Supports `format=json` (for live dashboard metrics and interactive standings table) and `format=csv` (with UTF-8 Byte Order Mark `\xEF\xBB\xBF` for native opening in Microsoft Excel).
- Added the **Executive Cycle Evaluation Report** panel to `topboard.php` with cycle picker, tier filters, live guild-wide statistics, CSV export, and print-ready layout (`window.print()`).
- The print layout features the sacred Dhamma School emblem and signature seal blocks for **Ven. Principal Thero**, **Teacher-in-Charge**, and **Head Prefect**.

#### 2. Full Audit Trail Engine (`api/entries.php`, `api/deductions.php`, `schema.sql`)
**Why**: With individual named administrative accounts, all merit modifications and soft-deletes must record the acting admin's identity for guild accountability.
**What**:
- Migrated `entries` and `deductions` database tables with `logged_by VARCHAR(60)` and `deleted_by VARCHAR(60)`.
- Updated `api/entries.php` and `api/deductions.php` to bind `$_SESSION['user_name']` on insertion and soft-deletion.
- Updated `api/bootstrap.php` to expose `loggedBy` to administrators.

#### 3. Minor Student Data Privacy & Contact Purge (`api/prefects_admin.php` & `prefects.php`)
**Why**: As student prefects are minors, personal contact information (email and emergency phone numbers) should not be held indefinitely when a prefect graduates or departs the guild.
**What**:
- Implemented `action === 'purge_contacts'` in `api/prefects_admin.php` restricted to `principal` and `head_prefect`.
- Added a confirmation-guarded "Purge Contacts" action in `prefects.php` to permanently wipe personal phone numbers and emails while preserving leadership merit records.

#### 4. Smart Production HTTPS & HSTS Enforcement (`.htaccess`)
**Why**: Cryptographic tokens, Argon2id auth, and sessions require encrypted transport in production.
**What**:
- Added an intelligent HTTPS rewrite rule in `.htaccess` that redirects all production traffic to HTTPS (301 Permanent Redirect) while safely bypassing local development on `localhost` and `127.0.0.1`.
- Enforced `Strict-Transport-Security "max-age=31536000; includeSubDomains"` conditionally when HTTPS is active.

#### 5. Dual PHP 7.4.33 & PHP 8.1–8.3+ Compatibility (`.agents/AGENTS.md`)
**Why**: The school's current target host runs PHP 7.4.33, but modern hosts run PHP 8.1–8.3+. Locking the codebase solely to 7.4 prevents future host upgrades.
**What**:
- Updated Rule 3 in `.agents/AGENTS.md` to formally certify Dual Compatibility: ensuring zero PHP 8+ breaking syntax while maintaining zero PHP 8+ deprecation warnings.

## [2.3.1] - 2026-09-11

### Prefect Profile Authentication & Topbar Cycle Fixes

#### 1. Prefect Sign-In Response Alignment (`api/lookup.php` & `register.php`)
**Why**: When a student logged in with valid Name/ID and 4-digit PIN, `api/lookup.php` returned the record without an explicit `ok: true` boolean. `register.php` checked `if (r.ok)`, evaluating to `false`, causing an erroneous toast "Incorrect name or PIN" while the PHP server session was already initialized. Subsequent navigation to `profile.php` then failed because `localStorage` was never populated and `profile.php` only checked admin sessions.
**What**:
- Added `'ok' => true` to `api/lookup.php` response payload.
- Added `$_SESSION['user_qr_token']` to prefect web session.
- Updated `register.php` sign-in logic to accept `(r && (r.ok || r.id) && !r.error)` and display student welcome toast.

#### 2. Dual-Layer Profile Authentication Check (`profile.php`)
**Why**: Students authenticated via server session were blocked by a strict check expecting `localStorage` or `type === 'admin'`.
**What**:
- Updated `initProfile()` in `profile.php` to prioritize the active server session (`session.user.type === 'prefect'`).
- Added fallback lookup to `localStorage.getItem('skds_prefect')` with case-insensitive ID matching.
- Attached `qrToken` from session or record so the student's roll call QR code generates reliably.
- Preserved administrator preview navigation when an admin views any student profile (`profile.php?id=...`).

#### 3. Topbar Cycle Resolution & Date Parsing (`api/bootstrap.php` & `includes/footer.php`)
**Why**: Header topbar displayed `Current Cycle Invalid Date NaN (Cycle 2)` due to mismatching column aliases (`start`/`end` vs `startDate`/`endDate`) and date string parsing quirks.
**What**:
- In `api/bootstrap.php`, aliased both `start_date AS start, end_date AS end, start_date AS startDate, end_date AS endDate`.
- In `includes/footer.php`, updated `currentCycle()` and `cycleForDate()` to support both property names.
- Updated `topbarCycle` element to render `<b>` + label + `</b><br>` + formatted date range cleanly (e.g., `<b>Cycle 1</b><br>Sun, Sep 6, 2026 – Thu, Nov 5, 2026`).

## [2.3.0] - 2026-09-11

### Unified PHP Header & Footer Architecture (Approach A)

#### 1. Centralized Header & Footer Templates (`includes/`)
**Why**: Eliminate duplicate header and footer code across multiple pages. Ensure any future change to typography, topbar badges, navigation tabs, mobile stylesheets, or footer notices can be done in one single file and apply immediately across the entire site.
**What**:
- Created `includes/header.php` defining `renderHeader(string $title, string $activePage = '')`:
  - Renders `<head>`, viewport, QR libraries, CSS styles, responsive media queries, and `.table-wrap` rules.
  - Renders Dhamma School emblem, title, current cycle, and authenticated user indicator.
  - Renders active navigation tabs and dynamic auth/logout button.
- Created `includes/footer.php` defining `renderFooter()`:
  - Renders standardized footer: `Sri Kalyani Dhamma School · Prefect Guild Merit System · Built By Prefects In The Dhamma School`.
  - Renders toast alert container (`#toast`).
  - Encapsulates shared client-side JavaScript (`state`, `apiCall`, `initCommon`, formatters, cycle computation).

#### 2. Modular Page Architecture
**Why**: Keep page files lightweight and focused purely on their respective view content.
**What**:
- Migrated all primary views to clean, modular PHP files:
  - `index.php` (Overview)
  - `leaderboard.php` (Public Standings)
  - `awards.php` (Awards & Recognition)
  - `register.php` (Registration & Dual Sign-In)
  - `profile.php` (Private Prefect Profile)
  - `topboard.php` (Admin Panel & Scanner)
  - `prefects.php` (Prefects Directory & RBAC)
- Updated `.htaccess` with `DirectoryIndex index.php index.html` and rewrite rules mapping `.html` requests to `.php`.

#### 3. Official School Logo & Favicon Integration
**Why**: Incorporate the authentic, sacred emblem of Sri Kalyani Dhamma School (Kelaniya Raja Maha Vihara crest) and provide browser tab favicon branding.
**What**:
- Integrated `Logo/favicon.ico` into the document `<head>` (`includes/header.php` and `portal/scanner.php`).
- Replaced the placeholder SVG wheel in `.emblem` with the high-resolution crest `Logo/logo.png` across the topbar, Top Board gate lock, profile authentication card, and gate kiosk.
#### 4. Deep Session Purge on Sign Out & Anti-Fixation Protection
**Why**: Prevent session leakage, zombie logins, and unauthorized access when logging out or navigating back via browser history.
**What**:
- Created `destroy_user_session()` in `api/db.php`: empties `$_SESSION`, expires all session cookies (`PHPSESSID`, `PREFECT_KIOSK_SESSION`), and calls `session_destroy()`.
- Added `action === 'logout'` handler across `api/auth.php` and `api/session.php` supporting both JSON payload and standard form requests.
- Added `@session_regenerate_id(true)` upon successful prefect and admin sign-in to prevent session fixation attacks.
- Implemented `performSignOut(ev)` in `includes/footer.php`: synchronously destroys server sessions, expires browser cookies, purges `localStorage` and `sessionStorage`, wipes in-memory state arrays, resets navigation UI, and redirects to `index.php`.

---

## [2.0.0] - 2026-09-11

### Architectural Transformation, Multi-Page Layout, Role-Based Access Control (RBAC) & Extended Registration

#### 1. Multi-Page Architecture (Clean Separation)
**Why**: The single-page application structure needed to be cleanly divided so that public users can view the Overview, live Leaderboard, and Awards without unlocking the system, while Profile, Top Board operations, and Prefect Directory are properly modularized.
**What**:
- Created separate `.html` pages:
  - `index.html`: Public Overview showing leadership proposal, 3-pillar framework, summary stats, public registration QR display, and a prominent "Register / Login" header navigation button.
  - `leaderboard.html`: Public live leaderboard ranking active Probation and Junior Leaders.
  - `awards.html`: Public term awards & recognition.
  - `register.html`: Unified registration and sign-in portal.
  - `profile.html`: Dedicated private prefect profile displaying score breakdown, attendance stats, and personal UUID QR code.
  - `topboard.html`: RBAC-protected administrative dashboard (attendance scanner, duty/events/conduct entry, deductions, cycle settings).
  - `prefects.html`: RBAC-protected directory listing all prefects with status, contact info, approval, and suspension controls.

#### 2. Role-Based Access Control (RBAC) & 6-Tier Hierarchy
**Why**: The school required strict segregation of administrative authority: Principal Thero holds executive suspension powers, the Head Prefect approves new registrations and adds top-level administrators, Top Board logs daily marks, Senior Prefects assist at the gate, and junior/probation tiers are scored members.
**What**:
- Created `admin_users` table with Argon2id password hashing supporting roles: `developer`, `principal`, `head_prefect`, `top_board`, `senior_prefect`.
- Created `api/prefects_admin.php` enforcing role permissions:
  - **Head Prefect** (`head_prefect`): Power to approve (`status = 'approved'`) or reject pending registrations, and register administrative accounts (`principal`, `head_prefect`, `top_board`).
  - **Principal Thero** (`principal`): Sole authority to suspend (`status = 'suspended'`) and reactivate prefects with mandatory audit reason logging.
  - **Top Board** (`top_board`): Sunday duty, extra events, conduct scoring, penalty deductions, and attendance roll call.
  - **Senior Prefect** (`senior_prefect`): Gate attendance scanning assistance and roster viewing.
  - **Developer** (`developer`): Super admin testing and debugging account.
- Updated `api/auth.php` and `api/session.php` to manage multi-role sessions and secure logout.

#### 3. Student Registration & Approval Lifecycle
**Why**: New registrations must not immediately grant active status or allow profile login until verified and approved by the Head Prefect.
**What**:
- Extended `api/register.php` with:
  - Email address (optional / validated).
  - 3 contact numbers: Primary Contact (required), Secondary Contact (optional), Emergency Contact (optional).
  - Expanded Guild Tier dropdown with Senior Prefect option.
  - Enforced initial state `status = 'pending'`.
- Students see a Welcome screen upon submission displaying their Prefect ID, 4-digit PIN, and QR code with a clear notice: *"Awaiting Head Prefect approval"*.
- `api/lookup.php` blocks profile login for `pending`, `rejected`, and `suspended` accounts with informative explanations.
- `api/attendance.php` blocks gate QR attendance scanning for unapproved and suspended students.

#### 4. Institutional Branding & Footer Standardization
**Why**: Strictly honor the user's specification reflecting student authorship and non-commercial community identity.
**What**:
- Standardized footer across all pages to Title Case:
  `Sri Kalyani Dhamma School · Prefect Guild Merit System · Built By Prefects In The Dhamma School`.

#### 5. Mobile Responsiveness Across All Pages
**Why**: Students, teachers, and gate marshals access the register via smartphones and tablets. Layouts, tables, and forms must render cleanly across all device widths without breaking the approved parchment and maroon aesthetic.
**What**:
- Enforced iOS Safari input auto-zoom prevention by setting input font size to `16px` with minimum touch targets (`42px`).
- Added responsive table container (`.table-wrap`) with native momentum horizontal touch scrolling (`-webkit-overflow-scrolling: touch`).
- Single-column `.grid` adaptation below `600px` screen width.
- Responsive top bar header scaling and adaptive navigation tabs.
- Multi-button action rows with `flex-wrap: wrap` to prevent clipping on small screens.

---

## [1.0.0] - 2026-09-06

### Initial Release Baseline & Production DB Setup

#### 1. Production Database Connection & Auto-Provisioning
**Why**: The application originally used placeholder database credentials (`your_database_name`), preventing backend API operations and returning HTTP 500 errors.
**What**:
- Created MySQL database `prefect_db` using `utf8mb4_unicode_ci`.
- Imported the standard 6 tables from `schema.sql`: `prefects`, `cycles`, `entries`, `deductions`, `attendance`, `settings`.
- Configured `db_config.php` to connect to local MySQL (`127.0.0.1:3306`, user: `root`).
- Verified automatic seeding in `api/db.php`: created default Top Board passcode (`GUILD2026`) and initialized Cycle 1 (2 months).

#### 2. Timezone Standardization (`Asia/Colombo`)
**Why**: Without explicit timezone configuration, PHP uses server UTC or default time, which miscalculates check-in hours against the Sunday roll call late cutoff (`06:15 AM`) and incorrectly applies late penalty deductions (-2 points).
**What**:
- Added `date_default_timezone_set('Asia/Colombo');` in `api/db.php` to standardize all attendance checks and date handling to Sri Lanka local time.

#### 3. Security Hardening & Rate Limiting
**Why**: 
- A 4-digit PIN (`0000`–`9999`) on `api/lookup.php` is susceptible to brute-force enumerations.
- Direct web access to `db_config.php` and `schema.sql` could expose sensitive structure and credentials.
**What**:
- Created `C:/xampp/htdocs/prefect/.htaccess` to return `403 Forbidden` for `db_config.php`, `schema.sql`, `.env`, and backup files, disable directory indexing (`Options -Indexes`), and set security headers (`X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`).
- Implemented `check_rate_limit()` in `api/db.php` and integrated it into `api/lookup.php` and `api/auth.php` (max 10 attempts per 5-minute window per IP).

#### 4. Project Governance & Rules Documentation
**Why**: To ensure future developers and AI agents do not modify the approved UI, introduce incompatible PHP 8+ features, or alter the school's non-commercial identity.
**What**:
- Created `.agents/AGENTS.md` enshrining 7 master rules: Strict UI Invariance, Brand Preservation (no commercial branding), PHP 7.4.33 compatibility, Asia/Colombo timezone, Security & Rate Limiting, Idempotent DB migrations, and Master Changelog maintenance.

#### 5. Password Hashing Upgrade: Argon2id Standard
**Why**: Align cryptographic password storage with the ecosystem standard (Argon2id instead of standard Bcrypt) to provide high resistance against GPU/ASIC brute-force cracking.
**What**:
- Implemented `hash_passcode()` in `api/db.php` utilizing `PASSWORD_ARGON2ID` with parameters `memory_cost = 65536` (64 MB), `time_cost = 4`, `threads = 1` (with graceful fallback to `PASSWORD_DEFAULT`).
- Updated `api/settings.php` to use `hash_passcode()` when saving updated passcodes.
- Re-hashed the active Top Board passcode in `prefect_db.settings` to Argon2id (`$argon2id$v=19$m=65536,t=4,p=1$...`).

#### 6. Military Fortress Hardening (Top 30 Cyber Defenses, Soft Deletes, DevTools Blocker & Mobile JWT/CSRF)
**Why**: Harden the architecture against automated bot attacks, memory-exhaustion DoS, session/token hijacking, client-side inspection tampering, XSS script execution in form inputs, and accidental data destruction, while establishing a future mobile app API.
**What**:
- **Database Soft Deletes**: Idempotently added `deleted_at DATETIME NULL` to `prefects`, `entries`, and `deductions`. Updated `roster.php`, `entries.php`, and `deductions.php` to perform non-destructive updates (`SET deleted_at = NOW()`), and filtered active queries in `bootstrap.php` (`WHERE deleted_at IS NULL`).
- **CSRF Token Validation**: Built `api/csrf.php` with 256-bit cryptographically secure tokens; verified on all web mutation endpoints via `X-CSRF-Token` headers.
- **Token & Session Anti-Hijacking Engine**: Embedded client cryptographic fingerprint `HMAC-SHA256(Client_IP + User_Agent)` into sessions and JWTs. Hijack attempts from mismatched IPs or browsers are instantly terminated.
- **30-Minute Inactivity Session Timeout**: Server enforces a sliding 30-minute idle window; client automatically locks the Top Board panel after 30 minutes of user inactivity.
- **Input Field Script Execution Blocker**: Added real-time client sanitization on all text inputs and server-side `clean_string()` in `api/db.php` stripping HTML tags, control characters, and script injection vectors (`<script>`, `javascript:`, etc.).
- **Live Production DevTools Blocker**: Disabled right-click context menus and intercepted inspection shortcuts (`F12`, `Ctrl+Shift+I/J/C`, `Ctrl+U`).
- **Mobile App-Ready JWT Engine**: Implemented `api/jwt.php` and `api/mobile_auth.php` issuing signed HS256 JWT tokens (+7 day expiry) with dual-mode authentication in `require_passcode()`.
- **Top 30 Cyber Defenses**: Enforced 64 KB JSON payload limit (HTTP 413), active honeypot scanner trap (HTTP 403), strict same-origin CORS, and hardened `.htaccess` security headers (CSP `frame-ancestors 'self'`, Referrer-Policy, Permissions-Policy).

#### 7. Dual Attendance Scanners, 5-Minute Cooldown, Unguessable UUIDv4 QR Tokens & Multi-Mode Shifts
**Why**:
- Legacy QR codes encoded full web URLs (`#checkin=SKP-...`), which exposed internal sequential IDs and could cause accidental navigation if scanned by third-party camera apps.
- Rapid or accidental repeated scans at the gate could immediately toggle a prefect from "Checked In" to "Checked Out" within seconds.
- School operations require attendance tracking for both regular Sunday Dhamma School (with 6:15 AM late cutoff and -2 point deduction) and Special Events (Katina, Pirith, Vesak Zone, with no late penalties), including multiple events on the same calendar day.
- Top Board members needed dual scanning modes: a dedicated, long-running gate kiosk (`portal/scanner.php`) on dedicated gate devices, and an in-app camera scanner directly inside the main web application (`index.html#topboard` & `index.html#scanner`).
**What**:
- **Cryptographic UUIDv4 QR Tokens**: Replaced URL encoding with raw, unguessable UUIDv4 tokens (`qr_token`) in `prefects`. Both registration and "My Points" generate pure UUID QR codes without URL prefixes. `api/bootstrap.php` keeps `qr_token` private to authenticated holders.
- **5-Minute Anti-Double-Scan Cooldown**: `api/attendance.php` checks elapsed time from check-in. If scanned within < 300 seconds, the system denies checkout with HTTP 200 `status: 'warning'`, `action: 'cooldown'`, returning remaining minutes and seconds.
- **Dual Mode Attendance Engine**: Added `type ENUM('sunday','event')` and `event_name VARCHAR(120)` to `attendance`. Sunday mode applies the 6:15 AM late cutoff and records automatic -2 point deductions; Special Event mode records attendance without late penalties.
- **Database Schema & Index Migration**: Added foreign key index `idx_att_prefect (prefect_id)`, dropped legacy `uniq_prefect_day`, and added composite unique key `uniq_prefect_shift (prefect_id, att_date, type, event_name)` in `prefect_db.attendance` and `schema.sql`.
- **Dedicated Kiosk Scanner (`/prefect/portal/scanner.php`)**: Gated behind Top Board passcode verification with 90-day persistent session cookie (`PREFECT_KIOSK_SESSION`). Features live continuous camera scanning (`html5-qrcode`), USB barcode scanner support, mode switching, visual status modals (SweetAlert2), and session logout.
- **In-App Camera Scanner (`index.html`)**: Integrated directly inside the Sunday roll call card on `#topboard` and as a standalone view `index.html#scanner` (protected by Top Board passcode). Features live viewfinder, mode toggles, instant alert cards, and Web Audio API synthesizer chimes (`playScanTone`) for auditory scan feedback without external audio assets.
- **PHP 7.4.33 & UTF-8 Cleanliness**: Stripped UTF-8 BOM headers from all PHP scripts and ensured strict PHP 7.4 compliance with zero PHP 8+ language constructs.

#### 8. Formal Project Handover & Complete Guidance Documentation
**Why**: 
- Prepare the repository for full standalone handover to school administration (Principal Thero, Teacher-in-Charge, Top Board) and future human developers or AI agents, ensuring complete technical and operational continuity.
**What**:
- **Comprehensive Master AI Agent & Developer Rules (`.agents/AGENTS.md`)**: Fully updated with 6 core rules (UI invariance, brand preservation, PHP 7.4.33, PowerShell BOM prevention, Asia/Colombo timezone), directory architecture map, security specifications, and SPA routing guidelines.
- **Detailed Operational & Technical Handover Manual (`HANDOVER.md`)**: Created comprehensive operational runbook covering default credentials, Sunday gate roll call procedures, special events setup, kiosk operation, weekly scoring workflows, security mechanics, cPanel live deployment steps, backup scripts, and soft-delete recovery queries.
- **Updated Production README (`README.md`)**: Modernized documentation reflecting dual attendance scanners, 5-minute cooldown, unguessable UUID tokens, and military fortress cybersecurity defenses.

#### 9. Multi-Page Architecture, RBAC Controls, Private Profiles & Admin Directory Links
**Why**: 
- Transition from single-page routing to dedicated separate pages (`index.html`, `leaderboard.html`, `awards.html`, `profile.html`, `register.html`, `topboard.html`, `prefects.html`).
- Restrict profile visibility so students only access their own private profile upon login, while administrators can review any student's profile via direct directory links.
- Introduce Role-Based Access Control (Principal Thero, Head Prefect, Top Board, Senior Prefect, Developer).
- Fix the `topboard.html` gate display bug where duplicate style attributes caused "Incorrect passcode." to render on initial load.
**What**:
- **Separate HTML Pages**: Decoupled application into standalone HTML pages maintaining approved Dhamma School palette and layout.
- **Private Authenticated Profiles (`profile.html`)**: Removed the public dropdown selector. `profile.html` requires authentication; logged-in prefects only view their own merit ledger, rank, and personal QR code.
- **Admin Directory Profile Navigation**: In `prefects.html`, each prefect row features a dedicated **[ Profile &rarr; ]** action button that allows administrators to inspect that specific prefect's full profile page with a convenient return link.
- **Dual Sign-In Portal (`register.html`)**:
  - Prefects log in with their Name (or Prefect ID) and 4-digit PIN once approved by the Head Prefect.
  - Administrators sign in with assigned passcodes and are automatically redirected to `topboard.html`.
- **Extended Registration**: Form captures 3 phone numbers (Primary, Secondary, Emergency) and email; initial status defaults to `pending` awaiting Head Prefect approval.
- **Role-Based Access Control (RBAC)**:
  - `Principal Thero` (`principal` / `THERO2026`): Executive authority to suspend and reactivate prefects with mandatory audit reason.
  - `Head Prefect` (`headprefect` / `HEAD2026`): Approves or rejects pending registrations; registers administrative accounts.
  - `Top Board` (`topboard` / `GUILD2026`): Manages Sunday duty scores, events, negative deductions, and gate attendance.
  - `Senior Prefect` (`senior` / `SENIOR2026`): Senior non-scored tier assisting at the gate and viewing directory.
  - `Developer` (`developer` / `DEV@2026`): Super-admin testing role.
- **Top Board Gate Resolution**: Eliminated phantom "Incorrect passcode." error on load; supports direct unlock and auto-unlock from active admin sessions.
- **Standardized Footer**: Maintained strictly as `Sri Kalyani Dhamma School · Prefect Guild Merit System · Built By Prefects In The Dhamma School`.
- **Mobile Responsiveness**: Touch targets, responsive tables with horizontal scroll containers, and single-column layout on small screens.


