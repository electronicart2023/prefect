<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$pdo = db();
$body = json_input();
require_passcode($pdo, $body);
require_csrf($body);

$updates = [];
$params = [];

if (isset($body['newPasscode']) && trim((string)$body['newPasscode']) !== '') {
    $np = trim((string)$body['newPasscode']);
    if (strlen($np) < 4) {
        fail('Passcode must be at least 4 characters.');
    }
    $updates[] = 'passcode_hash = :hash';
    $params['hash'] = hash_passcode($np);
}

if (isset($body['lateCutoff']) && trim((string)$body['lateCutoff']) !== '') {
    $cutoff = clean_string($body['lateCutoff'], 8);
    if ($cutoff && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $cutoff)) {
        $updates[] = 'late_cutoff = :cutoff';
        $params['cutoff'] = $cutoff;
    } else {
        fail('Invalid cutoff time format.');
    }
}

if (empty($updates)) {
    fail('Nothing to update.');
}

$sql = 'UPDATE settings SET ' . implode(', ', $updates) . ' WHERE id = 1';
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    fail('Database error updating settings.', 500);
}

respond(['ok' => true]);
