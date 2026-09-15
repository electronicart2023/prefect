<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/jwt.php';

$pdo = db();
$body = json_input();

check_rate_limit('mobile_auth', 10, 300);

$passcode = (string)($body['passcode'] ?? '');
if ($passcode !== '') {
    if (!check_passcode($pdo, $passcode)) {
        fail('Incorrect Top Board passcode.', 401);
    }

    $token = JWT::encode([
        'sub'  => 'topboard',
        'role' => 'topboard',
    ], 604800); // 7 days

    respond([
        'ok'    => true,
        'token' => $token,
        'role'  => 'topboard',
        'expiresIn' => 604800,
    ]);
}

// Prefect self-service login via ID/Name and 4-digit PIN
$name = trim((string)($body['name'] ?? ''));
$pin  = trim((string)($body['pin'] ?? ''));

if ($name === '' || $pin === '') {
    fail('Please provide either Top Board passcode or Prefect name and PIN.', 400);
}

$stmt = $pdo->prepare('SELECT id, name, tier, grade, pin FROM prefects WHERE LOWER(name) = LOWER(:name) AND deleted_at IS NULL LIMIT 1');
$stmt->execute(['name' => $name]);
$prefect = $stmt->fetch();

if (!$prefect || !password_verify($pin, $prefect['pin'])) {
    fail("Name and PIN do not match active register.", 404);
}
unset($prefect['pin']);

$token = JWT::encode([
    'sub'       => $prefect['id'],
    'name'      => $prefect['name'],
    'tier'      => $prefect['tier'],
    'role'      => 'prefect',
], 604800);

respond([
    'ok'      => true,
    'token'   => $token,
    'prefect' => $prefect,
    'expiresIn' => 604800,
]);
