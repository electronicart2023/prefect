<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('Method Not Allowed', 405);
}

$pdo = db();
$body = json_input();

// CSRF check
require_csrf($body);

// Anti-Spam Rate Limit (max 5 registrations per 15 min per IP)
check_rate_limit('register_prefect', 5, 900);

$name = clean_string($body['name'] ?? '', 120);
$tier = (string)($body['tier'] ?? '');
$grade = clean_string($body['grade'] ?? '', 40);
$email = clean_string($body['email'] ?? '', 120);
$contact_primary = clean_string($body['contact_primary'] ?? ($body['contact'] ?? ''), 30);
$contact_secondary = clean_string($body['contact_secondary'] ?? '', 30);
$contact_emergency = clean_string($body['contact_emergency'] ?? '', 30);

if ($name === null || $name === '') {
    fail('Name is required.');
}
if (!in_array($tier, ['probation', 'junior', 'senior'], true)) {
    fail('Invalid guild tier selected.');
}
if ($contact_primary === null || $contact_primary === '') {
    fail('Primary contact number is required.');
}
if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please enter a valid email address.');
}

$id = new_id('SKP');
$pin = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
$pinHash = password_hash($pin, PASSWORD_BCRYPT);

// Generate secure unguessable UUIDv4 for personal QR code (no web links)
$data = random_bytes(16);
$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
$qrToken = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

$stmt = $pdo->prepare(
    'INSERT INTO prefects (id, name, tier, grade, email, contact, contact_primary, contact_secondary, contact_emergency, pin, qr_token, status)
     VALUES (:id, :name, :tier, :grade, :email, :contact, :contact_primary, :contact_secondary, :contact_emergency, :pin, :qr_token, "pending")'
);
try {
    $stmt->execute([
        'id' => $id,
        'name' => $name,
        'tier' => $tier,
        'grade' => $grade,
        'email' => $email,
        'contact' => $contact_primary,
        'contact_primary' => $contact_primary,
        'contact_secondary' => $contact_secondary,
        'contact_emergency' => $contact_emergency,
        'pin' => $pinHash,
        'qr_token' => $qrToken,
    ]);
} catch (PDOException $e) {
    fail('Database error saving registration. Please check inputs and try again.', 500);
}

respond([
    'ok' => true,
    'id' => $id,
    'pin' => $pin,
    'name' => $name,
    'tier' => $tier,
    'status' => 'pending',
    'qrToken' => $qrToken,
    'message' => 'Registration submitted successfully! Your account is pending Head Prefect approval. Once approved, you will be able to log in to your Profile.'
]);

