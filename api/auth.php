<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$pdo = db();
$body = json_input();
$action = $body['action'] ?? ($_GET['action'] ?? ($_POST['action'] ?? ''));

if ($action === 'logout') {
    destroy_user_session();
    respond(['ok' => true, 'message' => 'Signed out successfully. All session data destroyed.']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('Method Not Allowed', 405);
}

check_rate_limit('auth', 10, 300);

$pass = (string)($body['passcode'] ?? '');
$username = clean_string($body['username'] ?? '', 60);

$user = null;
$role = null;
$name = null;
$userId = null;

// 1. Check admin_users table (by username + passcode, or passcode matching)
if ($username !== null && $username !== '') {
    $stmt = $pdo->prepare('SELECT id, username, name, role, passcode_hash FROM admin_users WHERE LOWER(username) = LOWER(:u) LIMIT 1');
    $stmt->execute(['u' => $username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($pass, $admin['passcode_hash'])) {
        $user = $admin;
        $userId = $admin['id'];
        $role = $admin['role'];
        $name = $admin['name'];
    }
} else {
    // Check if passcode matches any admin user directly (for single-input unlock box)
    $admins = $pdo->query('SELECT id, username, name, role, passcode_hash FROM admin_users')->fetchAll();
    foreach ($admins as $adm) {
        if (password_verify($pass, $adm['passcode_hash'])) {
            $user = $adm;
            $userId = $adm['id'];
            $role = $adm['role'];
            $name = $adm['name'];
            break;
        }
    }
}

// 2. Fallback to settings master passcode if legacy passcode matches
if (!$user && check_passcode($pdo, $pass)) {
    $userId = 'ADM-TOP01';
    $role = 'top_board';
    $name = 'Top Board Member';
    $user = ['id' => $userId, 'role' => $role, 'name' => $name];
}

$ok = ($user !== null);

if ($ok) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        $_SESSION['last_activity'] = time();
        $_SESSION['fingerprint'] = get_client_fingerprint();
    }
    respond([
        'ok' => true,
        'user' => [
            'id' => $userId,
            'name' => $name,
            'role' => $role,
        ]
    ]);
}

fail('Incorrect credentials or passcode.', 401);
