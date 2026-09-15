<?php
declare(strict_types=1);

// CSRF Protection Helper for Sri Kalyani Dhamma School
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    @session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ]);
}

function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals((string)$_SESSION['csrf_token'], $token);
}

function require_csrf(array $body = []): void {
    // If request contains a valid Bearer JWT (mobile app), it is inherently immune to CSRF
    require_once __DIR__ . '/jwt.php';
    $bearer = JWT::getBearerToken();
    if ($bearer !== null && JWT::decode($bearer) !== null) {
        return; // Valid mobile app JWT token
    }

    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? null);
    if ($token === null || !verify_csrf_token((string)$token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid or missing CSRF security token. Please refresh the page.']);
        exit;
    }
}
