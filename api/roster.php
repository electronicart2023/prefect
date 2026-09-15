<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$pdo = db();
$body = json_input();
require_passcode($pdo, $body);
require_csrf($body);

$id = (string)($body['id'] ?? '');
if ($id === '') {
    fail('Missing id.');
}

// Soft Delete: sets deleted_at so historical attendance/scores remain traceable
$actor = $_SESSION['user_name'] ?? ($_SESSION['user_role'] ?? 'Top Board');
$stmt = $pdo->prepare('UPDATE prefects SET deleted_at = NOW(), deleted_by = :actor WHERE id = :id');
$stmt->execute(['actor' => $actor, 'id' => $id]);

respond(['ok' => true]);
