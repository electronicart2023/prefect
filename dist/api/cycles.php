<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$pdo = db();
$body = json_input();
require_passcode($pdo, $body);
require_csrf($body);

$start = (string)($body['start'] ?? '');
$end = (string)($body['end'] ?? '');

if ($start === '' || $end === '' || $end <= $start) {
    fail('Invalid cycle dates.');
}

$count = (int)$pdo->query('SELECT COUNT(*) AS c FROM cycles')->fetch()['c'];
$id = new_id('CYC');
$label = 'Cycle ' . ($count + 1);

try {
    $stmt = $pdo->prepare('INSERT INTO cycles (id, label, start_date, end_date) VALUES (:id, :label, :start, :end)');
    $stmt->execute(['id' => $id, 'label' => $label, 'start' => $start, 'end' => $end]);
} catch (PDOException $e) {
    fail('Database error creating cycle.', 500);
}

respond([
    'ok' => true,
    'id' => $id,
    'label' => $label,
    'start' => $start,
    'end' => $end
]);
