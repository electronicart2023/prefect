<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'POST';
$body = json_input();

if ($method === 'DELETE') {
    require_passcode($pdo, $body);
    require_csrf($body);
    $id = (string)($body['id'] ?? '');
    if ($id === '') {
        fail('Missing id.');
    }
    $actor = $_SESSION['user_name'] ?? ($_SESSION['user_role'] ?? 'Top Board');
    // Soft Delete: update deleted_at timestamp and record actor
    try {
        $stmt = $pdo->prepare('UPDATE deductions SET deleted_at = NOW(), deleted_by = :actor WHERE id = :id');
        $stmt->execute(['actor' => $actor, 'id' => $id]);
        respond(['ok' => true]);
    } catch (PDOException $e) {
        fail('Database error deleting deduction.', 500);
    }
}

if ($method !== 'POST') {
    fail('Method Not Allowed', 405);
}

require_passcode($pdo, $body);
require_csrf($body);

$prefectId = (string)($body['prefectId'] ?? '');
$type = (string)($body['type'] ?? '');
$date = (string)($body['date'] ?? '');
$note = clean_string($body['note'] ?? '', 255);

$fixedPoints = [
    'unexcused_duty' => -5,
    'unexcused_event' => -10,
    'late_arrival' => -2,
];

if ($prefectId === '' || $date === '') {
    fail('Missing prefect or date.');
}
if (!array_key_exists($type, $fixedPoints) && $type !== 'conduct') {
    fail('Invalid deduction type.');
}

$check = $pdo->prepare('SELECT id FROM prefects WHERE id = :id AND deleted_at IS NULL');
$check->execute(['id' => $prefectId]);
if (!$check->fetch()) {
    fail('Prefect not found.', 404);
}

if ($type === 'conduct') {
    $points = (int)($body['points'] ?? -5);
    if ($points < -10 || $points > -5) {
        fail('Conduct deduction must be between -5 and -10.');
    }
} else {
    $points = $fixedPoints[$type];
}

$id = new_id('D');
$actor = $_SESSION['user_name'] ?? ($_SESSION['user_role'] ?? 'Top Board');
$stmt = $pdo->prepare(
    'INSERT INTO deductions (id, prefect_id, type, points, ded_date, note, logged_by) VALUES (:id, :pid, :type, :points, :date, :note, :logged_by)'
);
try {
    $stmt->execute([
        'id' => $id,
        'pid' => $prefectId,
        'type' => $type,
        'points' => $points,
        'date' => $date,
        'note' => $note,
        'logged_by' => $actor,
    ]);
} catch (PDOException $e) {
    fail('Database error saving deduction. Please check values and try again.', 500);
}

respond(['id' => $id]);
