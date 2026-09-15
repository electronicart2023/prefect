<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$pdo = db();
$body = json_input();
require_passcode($pdo, $body);
require_csrf($body);

$url = trim((string)($body['publicUrl'] ?? ''));
if ($url === '') {
    fail('URL is required.');
}
if (!preg_match('~^https?://[a-zA-Z0-9\-\._~:/\?#\[\]@!$&\'\(\)\*\+,;=]+$~i', $url)) {
    fail('Invalid URL format. Must start with http:// or https://');
}

$stmt = $pdo->prepare('UPDATE settings SET public_url = :url WHERE id = 1');
$stmt->execute(['url' => $url]);

respond(['ok' => true]);
