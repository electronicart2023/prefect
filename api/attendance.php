<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/jwt.php';
require __DIR__ . '/csrf.php';

$pdo = db();
$body = json_input();

// Rate-limit scan attempts (max 30 per minute per IP)
check_rate_limit('attendance_scan', 30, 60);

// Require active kiosk session, admin session, or valid JWT
$hasKiosk = (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['kiosk_unlocked']));
$hasAdmin = (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['authenticated']));
$hasJwt = false;
$bearer = JWT::getBearerToken();
if ($bearer !== null && JWT::decode($bearer) !== null) {
    $hasJwt = true;
}
if (!$hasKiosk && !$hasAdmin && !$hasJwt) {
    fail('Attendance scanning requires an authorized kiosk or admin session.', 401);
}

// 1. Resolve Prefect from qr_token (UUIDv4) OR prefectId
$tokenOrId = trim((string)($body['token'] ?? $body['qr_token'] ?? $body['prefectId'] ?? ''));
if ($tokenOrId === '') {
    fail('Missing scan token or prefect ID.', 400);
}

// Find prefect by qr_token first, then fallback to id
$stmt = $pdo->prepare('SELECT id, name, tier, qr_token, status, suspension_reason FROM prefects WHERE (qr_token = :token OR id = :id) AND deleted_at IS NULL LIMIT 1');
$stmt->execute(['token' => $tokenOrId, 'id' => $tokenOrId]);
$prefect = $stmt->fetch();

if (!$prefect) {
    fail('Prefect QR code not recognised.', 404);
}

if ($prefect['status'] === 'pending') {
    fail("Attendance rejected: Prefect registration is pending Head Prefect approval.", 403);
}

if ($prefect['status'] === 'rejected') {
    fail("Attendance rejected: Registration was declined.", 403);
}

if ($prefect['status'] === 'suspended') {
    $reason = $prefect['suspension_reason'] ? ' (Reason: ' . $prefect['suspension_reason'] . ')' : '';
    fail("Attendance rejected: Account has been suspended by Principal Thero{$reason}.", 403);
}

$prefectId = $prefect['id'];
$today = date('Y-m-d');
$now = date('H:i');
$now_ts = time();

// Mode: sunday (default) vs event
$mode = (string)($body['mode'] ?? 'sunday');
if (!in_array($mode, ['sunday', 'event'], true)) {
    $mode = 'sunday';
}
$eventName = ($mode === 'event') ? clean_string($body['eventName'] ?? 'Special Event', 120) : null;

// 2. Fetch today's record for this prefect and mode
if ($mode === 'event' && $eventName !== null) {
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE prefect_id = :pid AND att_date = :date AND type = "event" AND event_name = :ename');
    $stmt->execute(['pid' => $prefectId, 'date' => $today, 'ename' => $eventName]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE prefect_id = :pid AND att_date = :date AND type = "sunday"');
    $stmt->execute(['pid' => $prefectId, 'date' => $today]);
}
$existing = $stmt->fetch();

$settingsRow = $pdo->query('SELECT late_cutoff FROM settings WHERE id = 1')->fetch();
$cutoff = $settingsRow ? substr((string)$settingsRow['late_cutoff'], 0, 5) : '06:15';

// 3. FIRST SCAN: CHECK-IN
if (!$existing) {
    $late = ($mode === 'sunday') && ($now > $cutoff);
    $id = new_id('A');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO attendance (id, prefect_id, type, event_name, att_date, check_in, late) 
             VALUES (:id, :pid, :type, :ename, :date, :checkin, :late)'
        );
        $stmt->execute([
            'id' => $id,
            'pid' => $prefectId,
            'type' => $mode,
            'ename' => $eventName,
            'date' => $today,
            'checkin' => $now,
            'late' => $late ? 1 : 0,
        ]);

        if ($late) {
            $marshalActor = $_SESSION['kiosk_user_name'] ?? ($_SESSION['user_name'] ?? 'SYSTEM:attendance_scanner');
            $note = 'Auto-logged: checked in ' . $now . ' via QR (cutoff ' . $cutoff . ')';
            $stmt2 = $pdo->prepare(
                "INSERT INTO deductions (id, prefect_id, type, points, ded_date, note, logged_by)
                 VALUES (:id, :pid, 'late_arrival', -2, :date, :note, :logged_by)"
            );
            $stmt2->execute([
                'id' => new_id('D'),
                'pid' => $prefectId,
                'date' => $today,
                'note' => $note,
                'logged_by' => $marshalActor,
            ]);
        }
        $pdo->commit();
    } catch (Exception $ex) {
        $pdo->rollBack();
        fail('Attendance check-in failed. Please try again.', 500);
    }

    respond([
        'ok'     => true,
        'status' => 'success',
        'action' => 'checkin',
        'time'   => $now,
        'late'   => $late,
        'mode'   => $mode,
        'name'   => $prefect['name'],
        'tier'   => $prefect['tier'],
        'message' => "Welcome, {$prefect['name']}! Checked in at $now" . ($late ? " (Late - 2 pts deducted)" : " on time.")
    ]);
}

// 4. CHECK-OUT COOLDOWN CHECK (5 Minutes)
$inTimeStr = substr((string)$existing['check_in'], 0, 5);
$in_ts = strtotime($today . ' ' . $inTimeStr . ':00');
$diff_sec = $now_ts - $in_ts;

if ($existing['check_out'] === null) {
    // If scanned within < 5 minutes (300 seconds)
    if ($diff_sec < 300) {
        $remaining_sec = 300 - $diff_sec;
        $remaining_min = (int)ceil($remaining_sec / 60);
        respond([
            'ok'      => true,
            'status'  => 'warning',
            'action'  => 'cooldown',
            'name'    => $prefect['name'],
            'tier'    => $prefect['tier'],
            'time'    => $now,
            'remaining_minutes' => $remaining_min,
            'remaining_seconds' => $remaining_sec,
            'message' => "Scan too soon! Please wait $remaining_min minute(s) before checking out."
        ]);
    }

    // After 5 minutes: Check-out
    $stmt = $pdo->prepare('UPDATE attendance SET check_out = :now WHERE id = :id');
    $stmt->execute(['now' => $now, 'id' => $existing['id']]);

    respond([
        'ok'      => true,
        'status'  => 'success',
        'action'  => 'checkout',
        'time'    => $now,
        'late'    => (bool)$existing['late'],
        'mode'    => $mode,
        'name'    => $prefect['name'],
        'tier'    => $prefect['tier'],
        'message' => "Goodbye, {$prefect['name']}! Checked out at $now."
    ]);
}

// 5. ALREADY COMPLETED SHIFT
$outTimeStr = substr((string)$existing['check_out'], 0, 5);
respond([
    'ok'      => true,
    'status'  => 'info',
    'action'  => 'completed',
    'time'    => $now,
    'name'    => $prefect['name'],
    'tier'    => $prefect['tier'],
    'message' => "Attendance already completed today for {$prefect['name']} (In: $inTimeStr, Out: $outTimeStr)."
]);
