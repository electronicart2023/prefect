<?php
declare(strict_types=1);

/**
 * Sri Kalyani Dhamma School — Prefect Guild Merit Register
 * Executive Cycle Evaluation & Report Generator
 * Restricted to: Principal Thero, Head Prefect, Top Board, Developer
 * Compatible with PHP 7.4.33 - PHP 8.3+
 */

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$pdo = db();

// 1. Strict RBAC Enforcement
if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['authenticated'])) {
    fail('Authentication required.', 401);
}

$userType = $_SESSION['user_type'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';

$allowedRoles = ['principal', 'head_prefect', 'top_board', 'developer'];
if ($userType !== 'admin' || !in_array($userRole, $allowedRoles, true)) {
    fail('Access forbidden. Executive reports are strictly restricted to Principal Thero, Head Prefect, and Top Board.', 403);
}

// 2. Query Parameters
$cycleId = clean_string($_GET['cycle_id'] ?? null, 40);
$tierFilter = clean_string($_GET['tier'] ?? null, 30);
$format = strtolower((string)($_GET['format'] ?? 'json'));

// 3. Resolve Cycle
$cycles = $pdo->query('SELECT id, label, start_date AS start, end_date AS end FROM cycles ORDER BY start_date')->fetchAll();
$selectedCycle = null;
if ($cycleId) {
    foreach ($cycles as $c) {
        if ($c['id'] === $cycleId) {
            $selectedCycle = $c;
            break;
        }
    }
}
if (!$selectedCycle) {
    $today = date('Y-m-d');
    foreach ($cycles as $c) {
        if ($today >= $c['start'] && $today <= $c['end']) {
            $selectedCycle = $c;
            break;
        }
    }
    if (!$selectedCycle && !empty($cycles)) {
        $selectedCycle = $cycles[count($cycles) - 1];
    }
}

// 4. Fetch Prefects, Entries, Deductions
$prefectsStmt = $pdo->query(
    'SELECT id, name, tier, grade, status, contact_primary, contact_secondary, contact_emergency, email, registered_at 
     FROM prefects 
     WHERE deleted_at IS NULL AND status = "approved" 
     ORDER BY tier, name'
);
$allPrefects = $prefectsStmt->fetchAll();

$entriesQuery = 'SELECT prefect_id, type, entry_date, punctuality, uniform, execution, initiative, buddhist_values, team_synergy, event_attendance, task_ownership, problem_solving FROM entries WHERE deleted_at IS NULL';
$entriesParams = [];
if ($selectedCycle) {
    $entriesQuery .= ' AND entry_date >= :cstart AND entry_date <= :cend';
    $entriesParams['cstart'] = $selectedCycle['start'];
    $entriesParams['cend'] = $selectedCycle['end'];
}
$entriesStmt = $pdo->prepare($entriesQuery);
$entriesStmt->execute($entriesParams);
$allEntries = $entriesStmt->fetchAll();

$dedQuery = 'SELECT prefect_id, type, points, ded_date FROM deductions WHERE deleted_at IS NULL';
$dedParams = [];
if ($selectedCycle) {
    $dedQuery .= ' AND ded_date >= :cstart AND ded_date <= :cend';
    $dedParams['cstart'] = $selectedCycle['start'];
    $dedParams['cend'] = $selectedCycle['end'];
}
$dedStmt = $pdo->prepare($dedQuery);
$dedStmt->execute($dedParams);
$allDeductions = $dedStmt->fetchAll();

// Group entries and deductions by prefect_id
$entriesByPrefect = [];
foreach ($allEntries as $e) {
    $entriesByPrefect[$e['prefect_id']][] = $e;
}

$dedByPrefect = [];
foreach ($allDeductions as $d) {
    $dedByPrefect[$d['prefect_id']][] = $d;
}

// 5. Compute Merit Scores
$records = [];
foreach ($allPrefects as $p) {
    if ($tierFilter && $tierFilter !== 'all' && $p['tier'] !== $tierFilter) {
        continue;
    }

    $pEntries = $entriesByPrefect[$p['id']] ?? [];
    $pDeductions = $dedByPrefect[$p['id']] ?? [];

    $dutyEntries = [];
    $eventEntries = [];
    $conductScores = [];

    foreach ($pEntries as $pe) {
        if ($pe['type'] === 'duty') {
            $dutySum = (int)$pe['punctuality'] + (int)$pe['uniform'] + (int)$pe['execution'] + (int)$pe['initiative'];
            $dutyEntries[] = $dutySum;
            $conductScores[] = (int)$pe['buddhist_values'] + (int)$pe['team_synergy'];
        } elseif ($pe['type'] === 'event') {
            $eventSum = (int)$pe['event_attendance'] + (int)$pe['task_ownership'] + (int)$pe['problem_solving'];
            $eventEntries[] = $eventSum;
        }
    }

    $dutyAvg = count($dutyEntries) > 0 ? round(array_sum($dutyEntries) / count($dutyEntries), 1) : 0.0;
    $eventAvg = count($eventEntries) > 0 ? round(array_sum($eventEntries) / count($eventEntries), 1) : 0.0;
    $conductAvg = count($conductScores) > 0 ? round(array_sum($conductScores) / count($conductScores), 1) : 0.0;

    $dedTotal = 0;
    foreach ($pDeductions as $pd) {
        $dedTotal += (int)$pd['points'];
    }

    $finalScore = max(0, min(100, round($dutyAvg + $eventAvg + $conductAvg + $dedTotal, 1)));

    $records[] = [
        'id'             => $p['id'],
        'name'           => $p['name'],
        'tier'           => $p['tier'],
        'grade'          => $p['grade'] ?? '',
        'status'         => $p['status'],
        'dutyCount'      => count($dutyEntries),
        'dutyAvg'        => $dutyAvg,
        'eventCount'     => count($eventEntries),
        'eventAvg'       => $eventAvg,
        'conductAvg'     => $conductAvg,
        'dedCount'       => count($pDeductions),
        'dedTotal'       => $dedTotal,
        'finalScore'     => $finalScore,
        'contactPrimary' => $p['contact_primary'] ?? '',
        'email'          => $p['email'] ?? '',
    ];
}

// 6. Rank within Tiers
$tierGroups = [];
foreach ($records as &$rec) {
    $tierGroups[$rec['tier']][] = &$rec;
}
unset($rec);

foreach ($tierGroups as $tierName => &$group) {
    usort($group, function($a, $b) {
        if ($b['finalScore'] == $a['finalScore']) {
            return strcmp($a['name'], $b['name']);
        }
        return ($b['finalScore'] > $a['finalScore']) ? 1 : -1;
    });
    $rank = 1;
    foreach ($group as &$item) {
        $item['rank'] = $rank++;
    }
    unset($item);
}
unset($group);

// 7. Output Handling
if ($format === 'csv') {
    $cycleLabelClean = $selectedCycle ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $selectedCycle['label']) : 'All_Cycles';
    $filename = "SKDS_Prefect_Merit_Report_{$cycleLabelClean}_" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // Output UTF-8 Byte Order Mark for Excel compatibility
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'Rank',
        'Prefect ID',
        'Full Name',
        'Tier',
        'Class / Grade',
        'Sunday Duty Avg (/50)',
        'Extra Events Avg (/35)',
        'Conduct Avg (/15)',
        'Total Deductions',
        'Final Net Score (/100)',
        'Sunday Shifts Count',
        'Events Count',
        'Deductions Count',
        'Primary Contact',
        'Status'
    ]);

    $cleanCell = function($val): string {
        $s = (string)$val;
        if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $s;
        }
        return $s;
    };

    foreach ($records as $r) {
        fputcsv($out, [
            $r['rank'] ?? '-',
            $cleanCell($r['id']),
            $cleanCell($r['name']),
            ucfirst((string)$r['tier']),
            $cleanCell($r['grade'] ?? ''),
            number_format((float)$r['dutyAvg'], 1),
            number_format((float)$r['eventAvg'], 1),
            number_format((float)$r['conductAvg'], 1),
            $r['dedTotal'],
            number_format((float)$r['finalScore'], 1),
            $r['dutyCount'],
            $r['eventCount'],
            $r['dedCount'],
            $cleanCell($r['contactPrimary'] ?? ''),
            ucfirst((string)$r['status'])
        ]);
    }

    fclose($out);
    exit;
}

// Default JSON Response
$totalPrefects = count($records);
$scoresOnly = array_column($records, 'finalScore');
$avgScore = $totalPrefects > 0 ? round(array_sum($scoresOnly) / $totalPrefects, 1) : 0;
$totalDuties = array_sum(array_column($records, 'dutyCount'));
$totalEvents = array_sum(array_column($records, 'eventCount'));
$totalDeductions = array_sum(array_column($records, 'dedCount'));

respond([
    'ok'       => true,
    'cycle'    => $selectedCycle,
    'cycles'   => $cycles,
    'summary'  => [
        'totalPrefects'   => $totalPrefects,
        'averageScore'    => $avgScore,
        'totalDuties'     => $totalDuties,
        'totalEvents'     => $totalEvents,
        'totalDeductions' => $totalDeductions,
    ],
    'records'  => $records,
]);