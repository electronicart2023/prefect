<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$pdo = db();

// Issue or renew CSRF token for web session
$csrfToken = get_csrf_token();

$isAuthenticated = (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['authenticated']));
$isAdmin = ($isAuthenticated && ($_SESSION['user_type'] ?? '') === 'admin');
$qrSql = $isAdmin ? ', qr_token AS qrToken' : '';
$prefects = $pdo->query(
    "SELECT id, name, tier, grade, status{$qrSql}, registered_at AS registeredAt FROM prefects WHERE deleted_at IS NULL AND status = 'approved' ORDER BY name"
)->fetchAll();

// Privacy: Protect minors by masking surname for unauthenticated public viewers
if (!$isAuthenticated) {
    foreach ($prefects as &$p) {
        $parts = preg_split('/\s+/', trim((string)$p['name']));
        if (count($parts) > 1) {
            $p['name'] = $parts[0] . ' ' . mb_substr($parts[1], 0, 1, 'UTF-8') . '.';
        }
    }
    unset($p);
}

$auditField = $isAdmin ? ', logged_by AS loggedBy' : '';
$entryRows = $pdo->query(
    "SELECT id, prefect_id AS prefectId, type, entry_date AS date, event_name AS eventName,
            punctuality, uniform, execution, initiative, buddhist_values AS buddhistValues,
            team_synergy AS teamSynergy, event_attendance AS eventAttendance,
            task_ownership AS taskOwnership, problem_solving AS problemSolving,
            note, logged_at AS loggedAt{$auditField}
     FROM entries WHERE deleted_at IS NULL ORDER BY logged_at DESC LIMIT 2000"
)->fetchAll();

$entries = [];
foreach ($entryRows as $e) {
    $rowItem = [
        'id' => $e['id'],
        'prefectId' => $e['prefectId'],
        'type' => $e['type'],
        'date' => $e['date'],
        'eventName' => $e['eventName'],
        'note' => $e['note'],
        'loggedAt' => $e['loggedAt'],
        'values' => [
            'punctuality' => (int)$e['punctuality'],
            'uniform' => (int)$e['uniform'],
            'execution' => (int)$e['execution'],
            'initiative' => (int)$e['initiative'],
            'buddhistValues' => (int)$e['buddhistValues'],
            'teamSynergy' => (int)$e['teamSynergy'],
            'eventAttendance' => (int)$e['eventAttendance'],
            'taskOwnership' => (int)$e['taskOwnership'],
            'problemSolving' => (int)$e['problemSolving'],
        ],
    ];
    if (isset($e['loggedBy'])) {
        $rowItem['loggedBy'] = $e['loggedBy'];
    }
    $entries[] = $rowItem;
}

$deductions = $pdo->query(
    "SELECT id, prefect_id AS prefectId, type, points, ded_date AS date, note, logged_at AS loggedAt{$auditField} FROM deductions WHERE deleted_at IS NULL ORDER BY logged_at DESC LIMIT 2000"
)->fetchAll();
foreach ($deductions as &$d) {
    $d['points'] = (int)$d['points'];
}
unset($d);

$attendance = $pdo->query(
    'SELECT a.id, a.prefect_id AS prefectId, a.att_date AS date, a.check_in AS checkIn, a.check_out AS checkOut, a.late 
     FROM attendance a 
     INNER JOIN prefects p ON a.prefect_id = p.id 
     WHERE p.deleted_at IS NULL 
     ORDER BY a.att_date DESC 
     LIMIT 2000'
)->fetchAll();
foreach ($attendance as &$a) {
    $a['late'] = (bool)$a['late'];
    $a['checkIn'] = substr((string)$a['checkIn'], 0, 5);
    if ($a['checkOut'] !== null) {
        $a['checkOut'] = substr((string)$a['checkOut'], 0, 5);
    }
}
unset($a);

$cycles = $pdo->query(
    'SELECT id, label, start_date AS start, end_date AS end, start_date AS startDate, end_date AS endDate FROM cycles ORDER BY start_date'
)->fetchAll();

$settingsRow = $pdo->query('SELECT late_cutoff AS lateCutoff, public_url AS publicUrl FROM settings WHERE id = 1')->fetch();
$config = [
    'lateCutoff' => $settingsRow ? substr((string)$settingsRow['lateCutoff'], 0, 5) : '06:15',
    'publicUrl' => $settingsRow ? $settingsRow['publicUrl'] : null,
    'csrfToken' => $csrfToken,
];

$currentUser = null;
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['authenticated'])) {
    $currentUser = [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'type' => $_SESSION['user_type'] ?? null,
    ];
}

respond([
    'csrfToken' => $csrfToken,
    'prefects' => $prefects,
    'entries' => $entries,
    'deductions' => $deductions,
    'attendance' => $attendance,
    'cycles' => $cycles,
    'config' => $config,
    'currentUser' => $currentUser,
]);
