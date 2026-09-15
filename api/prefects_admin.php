<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = ($method === 'POST') ? json_input() : $_GET;

// Only Admin roles can access
$adminRoles = ['developer', 'principal', 'head_prefect', 'top_board', 'senior_prefect'];
$auth = require_passcode($pdo, $body, $adminRoles);
$currentRole = $auth['role'] ?? ($_SESSION['user_role'] ?? '');

if ($method === 'GET') {
    // List all prefects (including pending, approved, suspended)
    $stmt = $pdo->query(
        'SELECT id, name, tier, grade, email, contact_primary, contact_secondary, contact_emergency, qr_token, status, approved_by, approved_at, suspended_by, suspended_at, suspension_reason, registered_at 
         FROM prefects 
         WHERE deleted_at IS NULL 
         ORDER BY registered_at DESC'
    );
    respond(['prefects' => $stmt->fetchAll(), 'role' => $currentRole]);
}

if ($method === 'POST') {
    require_csrf($body);
    $action = (string)($body['action'] ?? '');
    $prefectId = (string)($body['prefectId'] ?? '');

    if ($action === 'approve') {
        // Only head_prefect, principal, or developer can approve
        if (!in_array($currentRole, ['head_prefect', 'principal', 'developer'], true)) {
            fail('Only the Head Prefect or Principal Thero can approve registrations.', 403);
        }
        if ($prefectId === '') fail('Missing prefect ID.');
        $actor = $_SESSION['user_name'] ?? 'Head Prefect';
        $stmt = $pdo->prepare('UPDATE prefects SET status = "approved", approved_by = :actor, approved_at = NOW() WHERE id = :id');
        $stmt->execute(['actor' => $actor, 'id' => $prefectId]);
        respond(['ok' => true, 'message' => 'Prefect registration approved successfully.']);
    }

    if ($action === 'reject') {
        // Only head_prefect, principal, or developer can reject
        if (!in_array($currentRole, ['head_prefect', 'principal', 'developer'], true)) {
            fail('Only the Head Prefect or Principal Thero can reject registrations.', 403);
        }
        if ($prefectId === '') fail('Missing prefect ID.');
        $stmt = $pdo->prepare('UPDATE prefects SET status = "rejected" WHERE id = :id');
        $stmt->execute(['id' => $prefectId]);
        respond(['ok' => true, 'message' => 'Prefect registration rejected.']);
    }

    if ($action === 'suspend') {
        // Strictly Principal Thero or developer
        if (!in_array($currentRole, ['principal', 'developer'], true)) {
            fail('Only Principal Thero has the executive authority to suspend prefects.', 403);
        }
        if ($prefectId === '') fail('Missing prefect ID.');
        $reason = clean_string($body['reason'] ?? 'Suspended by Principal Thero', 255);
        $actor = $_SESSION['user_name'] ?? 'Principal Thero';
        $stmt = $pdo->prepare('UPDATE prefects SET status = "suspended", suspended_by = :actor, suspended_at = NOW(), suspension_reason = :reason WHERE id = :id');
        $stmt->execute(['actor' => $actor, 'reason' => $reason, 'id' => $prefectId]);
        respond(['ok' => true, 'message' => 'Prefect has been suspended.']);
    }

    if ($action === 'reactivate') {
        // Strictly Principal Thero or developer
        if (!in_array($currentRole, ['principal', 'developer'], true)) {
            fail('Only Principal Thero has the executive authority to reactivate prefects.', 403);
        }
        if ($prefectId === '') fail('Missing prefect ID.');
        $stmt = $pdo->prepare('UPDATE prefects SET status = "approved", suspended_by = NULL, suspended_at = NULL, suspension_reason = NULL WHERE id = :id');
        $stmt->execute(['id' => $prefectId]);
        respond(['ok' => true, 'message' => 'Prefect has been reactivated.']);
    }

    if ($action === 'create_admin') {
        // Strictly Head Prefect or developer
        if (!in_array($currentRole, ['head_prefect', 'developer'], true)) {
            fail('Only the Head Prefect can register administrative roles.', 403);
        }
        $newRole = (string)($body['role'] ?? '');
        if (!in_array($newRole, ['principal', 'head_prefect', 'top_board'], true)) {
            fail('Head Prefect can only create Principal, Head Prefect, or Top Board accounts.', 400);
        }
        $username = clean_string($body['username'] ?? '', 60);
        $name = clean_string($body['name'] ?? '', 120);
        $passcode = (string)($body['passcode'] ?? '');
        if (!$username || !$name || strlen($passcode) < 4) {
            fail('Valid username, name, and passcode (at least 4 chars) are required.');
        }
        $newId = new_id('ADM');
        $stmt = $pdo->prepare('INSERT INTO admin_users (id, username, name, role, passcode_hash) VALUES (:id, :u, :name, :role, :hash)');
        try {
            $stmt->execute([
                'id' => $newId,
                'u' => $username,
                'name' => $name,
                'role' => $newRole,
                'hash' => hash_passcode($passcode)
            ]);
            respond(['ok' => true, 'message' => 'Admin account created successfully.']);
        } catch (PDOException $e) {
            fail('Username already taken.', 400);
        }
    }

    if ($action === 'purge_contacts') {
        // Strictly Principal Thero, Head Prefect, Top Board, or developer
        if (!in_array($currentRole, ['principal', 'head_prefect', 'top_board', 'developer'], true)) {
            fail('Only Principal Thero, Head Prefect, or Top Board can execute child data privacy purge.', 403);
        }
        if ($prefectId === '') fail('Missing prefect ID.');
        // Wipe personal contact numbers and email to respect minor privacy & data retention
        $stmt = $pdo->prepare('UPDATE prefects SET contact = NULL, contact_primary = NULL, contact_secondary = NULL, contact_emergency = NULL, email = NULL WHERE id = :id');
        $stmt->execute(['id' => $prefectId]);
        respond(['ok' => true, 'message' => 'Personal contact details permanently purged for student privacy.']);
    }

    if ($action === 'purge_archive') {
        // Strictly Principal Thero or developer
        if (!in_array($currentRole, ['principal', 'developer'], true)) {
            fail('Only Principal Thero has authority to purge archived historical data.', 403);
        }
        // Permanently purge soft-deleted records older than 2 years (data retention limit)
        $p1 = $pdo->exec('DELETE FROM entries WHERE deleted_at IS NOT NULL AND deleted_at < NOW() - INTERVAL 2 YEAR');
        $p2 = $pdo->exec('DELETE FROM deductions WHERE deleted_at IS NOT NULL AND deleted_at < NOW() - INTERVAL 2 YEAR');
        $p3 = $pdo->exec('DELETE FROM prefects WHERE deleted_at IS NOT NULL AND deleted_at < NOW() - INTERVAL 2 YEAR');
        $totalPurged = (int)$p1 + (int)$p2 + (int)$p3;
        respond(['ok' => true, 'message' => "Archived records older than 2 years permanently purged ({$totalPurged} records removed)."]);
    }

    fail('Invalid action.', 400);
}