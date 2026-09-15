<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';

$body = json_input();
$action = $body['action'] ?? ($_GET['action'] ?? ($_POST['action'] ?? ''));

if ($action === 'logout') {
    destroy_user_session();
    respond(['ok' => true, 'message' => 'Signed out successfully. All session data destroyed.']);
}

// Return current session info
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['authenticated'])) {
    respond([
        'authenticated' => true,
        'user' => [
            'id' => $_SESSION['user_id'] ?? '',
            'name' => $_SESSION['user_name'] ?? '',
            'role' => $_SESSION['user_role'] ?? '',
            'type' => $_SESSION['user_type'] ?? '',
            'qrToken' => $_SESSION['user_qr_token'] ?? '',
        ]
    ]);
}

respond(['authenticated' => false]);