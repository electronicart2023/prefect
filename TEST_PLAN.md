# Live Server Quality Assurance & Deployment Testing Plan
### Sri Kalyani Dhamma School — Prefect Guild Merit Register (v2.6.2)

This testing protocol defines the end-to-end operational and cybersecurity verification procedure to execute immediately after uploading the `dist/` package and importing `prefect_full_db.sql` to your live production hosting (cPanel / LiteSpeed / Apache).

---

## Pre-Flight Checklist: Server Environment

Before initiating functional testing, verify the server meets the baseline technical requirements:

| Check | Specification | Verification Method | Status |
|---|---|---|---|
| **PHP Runtime** | PHP 7.4.33 or PHP 8.1–8.3+ | `php -v` in cPanel Terminal or check cPanel PHP Selector | [ ] |
| **PHP Extensions** | `pdo_mysql`, `openssl`, `json`, `session`, `mbstring` | Check `phpinfo()` or cPanel PHP Extensions | [ ] |
| **MySQL Engine** | MySQL 8.0+ or MariaDB 10.4+ (`utf8mb4_unicode_ci`) | Check phpMyAdmin server version | [ ] |
| **Timezone** | `Asia/Colombo` (UTC +05:30) | Auto-enforced in `api/db.php` | [ ] |
| **SSL / HTTPS** | Valid Let's Encrypt / cPanel AutoSSL Certificate | Verify green padlock in browser on domain | [ ] |

---

## Phase 1: Database Import & Secure Configuration

### Test Case 1.1: Database Import Verification
* **Action**: In cPanel phpMyAdmin, select your newly created database and import `prefect_full_db.sql`.
* **Expected Result**: 
  - Exactly 7 tables created: `prefects`, `admin_users`, `cycles`, `entries`, `deductions`, `attendance`, `settings`.
  - Table `settings` contains 1 row (`id = 1`, `late_cutoff = '06:15:00'`).
  - Table `cycles` contains `Cycle 1`.
  - Table `admin_users` contains 5 default accounts (`developer`, `principal`, `headprefect`, `topboard`, `senior`).

### Test Case 1.2: Configuration Credentials & Cryptographic Salts
* **Action**: In `db_config.php`, configure database credentials and populate unique 64-character hex strings:
  ```php
  return [
      'host'          => 'localhost',
      'dbname'        => 'your_live_dbname',
      'user'          => 'your_live_dbuser',
      'pass'          => 'your_live_password',
      'jwt_secret'    => 'd18c5d4ed93833d08e683d8a24812a714c1d7f76f4266c713bd80ca7f1f2efad',
      'passcode_salt' => '1327276dd6d705bde2b76649ed24ce41933968c0b46119335d79994767748d0a',
  ];
  ```
* **Expected Result**: Visiting `https://your-domain.com/` loads the parchment portal with 0 database connection errors.

---

## Phase 2: Security Shield & Apache Fortress Audit

Execute these quick security checks to verify server-level defenses:

| Test Case | Request / URL | Expected Server Response | Pass/Fail |
|---|---|---|---|
| **2.1 Config Leakage** | `GET https://your-domain.com/db_config.php` | **HTTP 403 Forbidden** (blocked by `.htaccess`) | [ ] |
| **2.2 SQL File Leakage**| `GET https://your-domain.com/schema.sql` | **HTTP 403 Forbidden** (blocked by `.htaccess`) | [ ] |
| **2.3 Honeypot Probe**  | `GET https://your-domain.com/wp-login.php` | **HTTP 403 Forbidden** (bot trap triggered in `db.php`) | [ ] |
| **2.4 CSP Header**      | Inspect Network Headers on `index.php` | `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff` | [ ] |
| **2.5 DevTools Block**  | Right-click or press `F12` on `index.php` | Context menu disabled; inspector shortcuts suppressed | [ ] |

---

## Phase 3: Public Student Registration & Consent Workflow

### Test Case 3.1: Student Registration Submission
1. Open `https://your-domain.com/register.php`.
2. Enter student details:
   - **Full Name**: `Anuja Bandara`
   - **Guild Tier**: `Probation Leader (Tier 1)`
   - **Grade**: `Grade 10`
   - **Primary Contact**: `0771234567`
   - **Secondary Contact**: `0112345678`
   - **Emergency Contact**: `0719876543`
3. Tick the **Parental Consent confirmation** checkbox.
4. Click **Complete Registration**.
* **Expected Result**: 
  - Displays green **Registration Submitted** confirmation screen.
  - Generates a valid Prefect ID (`SKP-XXXX...`) and a secret 4-digit PIN (e.g. `4821`).
  - Renders personal unguessable UUIDv4 Attendance QR Code.
  - Notice confirms: `Status: Pending Head Prefect Approval`.

### Test Case 3.2: Anti-Spam Rate Limiter Test
* **Action**: Try submitting registration forms rapidly 6 times from the same IP address.
* **Expected Result**: On the 6th submission, returns **HTTP 429 Too Many Requests** (`Too many attempts. Please try again in a few minutes.`).

### Test Case 3.3: Pending Account Isolation
* **Action**: With the unapproved account (`Anuja Bandara`), attempt to sign in at `register.php` under *Prefect Sign In (Own Profile)*.
* **Expected Result**: Access denied with error: `Your account is pending Head Prefect approval.`

---

## Phase 4: Administrative RBAC & Approval Protocol

### Test Case 4.1: Head Prefect Authentication
1. Open `https://your-domain.com/register.php` (or `topboard.php`).
2. In the *Top Board & Admin Sign In* box, enter:
   - **Username**: `headprefect`
   - **Passcode**: `HEAD2026`
3. Click **Sign In to Admin Panel**.
* **Expected Result**: Authenticates successfully and redirects to `topboard.php` with admin badge: `Admin: Head Prefect`.

### Test Case 4.2: Approval of Pending Student (PDPA Minor Consent Protocol)
1. In the navigation bar, click **Prefects List** (`prefects.php`).
2. Filter by status or inspect the table for `Anuja Bandara`.
3. Notice status badge: `PENDING`.
4. Click **Approve**.
* **Expected Result**: 
  - Toast confirmation: `Prefect registration approved successfully.`
  - Status badge changes to `APPROVED`.
  - Student is now eligible for profile access and attendance check-ins.

---

## Phase 5: Student Profile & Badge Verification

### Test Case 5.1: Approved Prefect Profile Login
1. Open `https://your-domain.com/register.php`.
2. Under *Prefect Sign In (Own Profile)*, enter:
   - **Name or ID**: `Anuja Bandara`
   - **4-Digit PIN**: [The 4-digit PIN generated in Test 3.1]
3. Click **Sign In to My Profile**.
* **Expected Result**: 
  - Seamlessly redirects to `profile.php`.
  - Displays: Name, Tier badge, Grade, Prefect ID, Current Points (`0 / 100`), and Rank.
  - Renders personal high-resolution QR Attendance Badge for gate presentation.

---

## Phase 6: Dual Gate Attendance Kiosk (`portal/scanner.php`)

### Test Case 6.1: Dedicated Kiosk Unlock & 90-Day Persistence
1. Navigate to `https://your-domain.com/portal/scanner.php`.
2. Enter:
   - **Username**: `topboard`
   - **Passcode**: `GUILD2026`
3. Click **Unlock Kiosk Session**.
* **Expected Result**:
  - Unlocks gate scanner interface.
  - Header displays: `Gate Marshal on Duty: Top Board Operations`.
  - Cookie `PREFECT_KIOSK_SESSION` set with 90-day persistence. Refreshing the browser keeps the gate unlocked.

### Test Case 6.2: Sunday Duty Check-In (On-Time & Late Logic)
1. Point camera at Anuja's QR code badge (or enter UUIDv4 token manually).
2. **Evaluation**:
   - If current Sri Lanka time is **before 06:15 AM**: Records on-time check-in, plays audio chime, shows green success modal.
   - If current Sri Lanka time is **after 06:15 AM**: Records check-in marked **LATE**, plays chime, and **automatically logs a -2 point deduction** in `deductions` table attributed to marshal `Top Board Operations`.

### Test Case 6.3: 5-Minute Anti-Double-Scan Cooldown
* **Action**: Immediately scan the same QR code a second time within 300 seconds.
* **Expected Result**: 
  - System denies check-out with yellow warning modal: `Scan Too Soon! Please wait X minute(s) before checking out.`
  - Database attendance record remains unchanged (check-out time not written).

### Test Case 6.4: Check-Out Scan (After 5 Minutes)
* **Action**: Scan the badge after 5 minutes have elapsed.
* **Expected Result**: 
  - Records check-out time. Green modal displays: `Goodbye, Anuja Bandara! Checked out at [Time].`

### Test Case 6.5: Third Scan (Completed Shift)
* **Action**: Scan the same badge a third time on the same day.
* **Expected Result**: 
  - Informational modal displays: `Attendance already completed today for Anuja Bandara (In: ..., Out: ...).`

### Test Case 6.6: Special Event Attendance
1. On the scanner kiosk, toggle mode to **Special Event**.
2. Enter Event Name: `Katina Pinkama 2026`.
3. Scan an approved student badge.
* **Expected Result**: 
  - Records check-in under event mode.
  - **No late deduction** is applied regardless of time of day.
  - Table `attendance` stores row with `type = 'event'` and `event_name = 'Katina Pinkama 2026'`.

---

## Phase 7: Top Board Scoring & Disciplinary Deductions

### Test Case 7.1: Sunday Duty & Conduct Scoring
1. Sign in to `topboard.php`.
2. Under **Log Sunday Duty & Conduct**:
   - Select: `Anuja Bandara`
   - Punctuality: `15` / 15
   - Uniform: `10` / 10
   - Execution: `15` / 15
   - Initiative: `10` / 10
   - Buddhist Values: `10` / 10
   - Team Synergy: `5` / 5
   - Subtotal: `65 / 65`
   - Note: `Exemplary hall arrangement leadership`
3. Click **Submit Duty Entry**.
* **Expected Result**: Toast confirms `Duty entry saved!`. `profile.php` reflects updated total (+65 points).

### Test Case 7.2: Special Event Scoring
1. Under **Log Special Event**:
   - Event Name: `Katina Pinkama 2026`
   - Prefect: `Anuja Bandara`
   - Attendance: `15` / 15
   - Task Ownership: `15` / 15
   - Problem Solving: `5` / 5
   - Subtotal: `35 / 35`
2. Click **Submit Event Entry**.
* **Expected Result**: Toast confirms `Event entry saved!`. Total points on profile update to `100 / 100`.

### Test Case 7.3: Disciplinary Deduction & Soft-Delete Audit
1. Under **Apply Disciplinary Deduction**:
   - Prefect: `Anuja Bandara`
   - Reason: `Improper belt alignment`
   - Points to Deduct: `-2`
2. Click **Apply Deduction**.
* **Expected Result**: Deducts 2 points (total is now 98).
3. Under **Recent Activity**: Click the red &times; button to delete the deduction.
* **Database Audit Verification**: Check table `deductions` in phpMyAdmin:
  - Row is **NOT deleted** from MySQL.
  - Column `deleted_at` is populated with the current timestamp.
  - Column `deleted_by` records active user.

---

## Phase 8: Leaderboard, Awards & Executive Reports

### Test Case 8.1: Live Leaderboard
1. Open `https://your-domain.com/leaderboard.php`.
* **Expected Result**: 
  - Displays `Anuja Bandara` ranked #1 with 98 points.
  - Shows breakdown across Sunday Duty (50), Conduct (15), and Special Events (35) minus deductions (2).

### Test Case 8.2: Awards & Cycle Progress
1. Open `https://your-domain.com/awards.php`.
* **Expected Result**: Evaluates current Cycle 1 dates and calculates badge tiers (Gold, Silver, Bronze) accurately.

### Test Case 8.3: Top Board Executive Cycle Evaluation Report
1. On `topboard.php`, scroll to **Cycle Evaluation Report**.
2. Click **Generate Report**.
* **Expected Result**: 
  - Renders detailed rank, attendances, duty totals, and conduct tallies for all active prefects.
  - **Print Official Report**: Opens clean temple letterhead print preview (`@media print` styled).
  - **Export CSV**: Downloads clean `.csv` file ready for Excel / Google Sheets archiving.

---

## Phase 9: Post-Launch Mandatory Security Hardening

Execute immediately prior to public student orientation:

- [ ] **Rotate Default Passcodes**:
  - Sign in as `principal` &rarr; change passcode from `THERO2026` to a private secure passphrase.
  - Sign in as `headprefect` &rarr; change passcode from `HEAD2026`.
  - In `topboard.php` &rarr; **Settings** &rarr; update the master Top Board passcode from `GUILD2026`.
- [ ] **Distribute Physical Parental Consent Paper Forms**:
  - Collect physical ink-signed consent forms from parents before Head Prefect clicks **Approve** on new student registrations (compliance with Sri Lanka PDPA No. 9 of 2022, Section 11).
- [ ] **Verify Scheduled Automated Backups**:
  - Configure cPanel daily MySQL dump cron with `~/.my.cnf` credential storage.

---

### Verification Sign-Off

| Role | Name | Signature / Approval | Date |
|---|---|---|---|
| **Lead Developer** | Antigravity AI & Dev Team | Verified (v2.6.2 Live Staging) | 2026-09-16 |
| **Head Prefect** | Student Leadership Head | _____________________________ | ___ / ___ / 2026 |
| **Principal Thero** | Ven. Principal Thero | _____________________________ | ___ / ___ / 2026 |
