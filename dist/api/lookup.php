<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('Method Not Allowed', 405);
}

$pdo = db();
$body = json_input();

$name = clean_string($body['name'] ?? '', 120);
$pin = trim((string)($body['pin'] ?? ''));

if ($name === null || $name === '' || $pin === '') {
    fail('Name and PIN are required.');
}

check_rate_limit('lookup', 10, 300);

$stmt = $pdo->prepare('SELECT id, name, tier, grade, email, contact_primary, pin, status, qr_token AS qrToken FROM prefects WHERE (LOWER(name) = LOWER(?) OR LOWER(id) = LOWER(?)) AND deleted_at IS NULL LIMIT 1');
$stmt->execute([$name, $name]);
$row = $stmt->fetch();

if (!$row || !password_verify($pin, $row['pin'])) {
    fail("Name and PIN don't match our register.", 404);
}
unset($row['pin']);

if ($row['status'] === 'pending') {
    fail("Your registration is currently pending Head Prefect approval. Once approved, you can log in to view your profile.", 403);
}

if ($row['status'] === 'rejected') {
    fail("Your registration has been declined by the Guild administration.", 403);
}

if ($row['status'] === 'suspended') {
    fail("Your account has been suspended by Principal Thero. Please consult the school administration.", 403);
}

if (session_status() === PHP_SESSION_ACTIVE) {
    @session_regenerate_id(true);
    $_SESSION['authenticated'] = true;
    $_SESSION['user_type'] = 'prefect';
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['user_name'] = $row['name'];
    $_SESSION['user_role'] = $row['tier'];
    $_SESSION['user_qr_token'] = $row['qrToken'] ?? '';
    $_SESSION['last_activity'] = time();
    $_SESSION['fingerprint'] = get_client_fingerprint();
}

$row['ok'] = true;
respond($row);
