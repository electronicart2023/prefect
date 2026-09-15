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
        $stmt = $pdo->prepare('UPDATE entries SET deleted_at = NOW(), deleted_by = :actor WHERE id = :id');
        $stmt->execute(['actor' => $actor, 'id' => $id]);
        respond(['ok' => true]);
    } catch (PDOException $e) {
        fail('Database error deleting entry.', 500);
    }
}

if ($method !== 'POST') {
    fail('Method Not Allowed', 405);
}

require_passcode($pdo, $body);
require_csrf($body);

$type = (string)($body['type'] ?? '');
$prefectId = (string)($body['prefectId'] ?? '');
$date = (string)($body['date'] ?? '');

if (!in_array($type, ['duty', 'event'], true)) {
    fail('Invalid entry type.');
}
if ($prefectId === '' || $date === '') {
    fail('Missing prefect or date.');
}

$check = $pdo->prepare('SELECT id FROM prefects WHERE id = :id AND deleted_at IS NULL');
$check->execute(['id' => $prefectId]);
if (!$check->fetch()) {
    fail('Prefect not found.', 404);
}

$values = is_array($body['values'] ?? null) ? $body['values'] : [];
$eventName = $type === 'event' ? clean_string($body['eventName'] ?? 'Guild event', 120) : null;
$note = clean_string($body['note'] ?? '', 255);
$actor = $_SESSION['user_name'] ?? ($_SESSION['user_role'] ?? 'Top Board');
$id = new_id('E');

$stmt = $pdo->prepare(
    'INSERT INTO entries
        (id, prefect_id, type, entry_date, event_name,
         punctuality, uniform, execution, initiative, buddhist_values, team_synergy,
         event_attendance, task_ownership, problem_solving, note, logged_by)
     VALUES
        (:id, :pid, :type, :date, :ename,
         :punctuality, :uniform, :execution, :initiative, :buddhistValues, :teamSynergy,
         :eventAttendance, :taskOwnership, :problemSolving, :note, :logged_by)'
);
try {
    $stmt->execute([
        'id' => $id,
        'pid' => $prefectId,
        'type' => $type,
        'date' => $date,
        'ename' => $eventName,
        'punctuality' => clamp_int($values['punctuality'] ?? 0, 0, 15),
        'uniform' => clamp_int($values['uniform'] ?? 0, 0, 10),
        'execution' => clamp_int($values['execution'] ?? 0, 0, 15),
        'initiative' => clamp_int($values['initiative'] ?? 0, 0, 10),
        'buddhistValues' => clamp_int($values['buddhistValues'] ?? 0, 0, 10),
        'teamSynergy' => clamp_int($values['teamSynergy'] ?? 0, 0, 5),
        'eventAttendance' => clamp_int($values['eventAttendance'] ?? 0, 0, 15),
        'taskOwnership' => clamp_int($values['taskOwnership'] ?? 0, 0, 15),
        'problemSolving' => clamp_int($values['problemSolving'] ?? 0, 0, 5),
        'note' => $note,
        'logged_by' => $actor,
    ]);
} catch (PDOException $e) {
    fail('Database error saving entry. Please verify inputs.', 500);
}

respond(['id' => $id]);
