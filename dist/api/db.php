<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Colombo');

// ============================================================
// 1. HONEYPOT & AUTOMATED BOT SCANNER TRAP
// ============================================================
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$malicious_signatures = [
    'wp-admin', 'wp-login.php', '.env', 'phpunit', 'phpmyadmin',
    'eval-stdin.php', 'setup.php', 'install.php', 'composer.json',
    'xmlrpc.php', '.git', 'alfa.php', 'shell.php', 'wso.php'
];
foreach ($malicious_signatures as $sig) {
    if (stripos($request_uri, "/{$sig}") !== false || stripos($request_uri, "={$sig}") !== false) {
        http_response_code(403);
        die(json_encode(['error' => 'Forbidden']));
    }
}

// ============================================================
// 2. HARDENED SESSION CONFIGURATION & IDLE TIMEOUT
// ============================================================
const SESSION_IDLE_TIMEOUT = 1800; // 30 minutes

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    @session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'gc_maxlifetime'  => SESSION_IDLE_TIMEOUT,
    ]);
}

/**
 * Standardized Client Fingerprint (Subnet /24 + User-Agent HMAC)
 * Accommodates mobile cellular carrier handovers while locking to the network subnet & browser.
 */
function get_client_fingerprint(): string {
    $rawIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ipSubnet = $rawIp;
    if (filter_var($rawIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $rawIp);
        $ipSubnet = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    return hash('sha256', $ipSubnet . '|' . $ua);
}

// Anti-Hijacking Check: verify client fingerprint & idle timeout
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['authenticated'])) {
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT)) {
        $_SESSION = [];
        @session_destroy();
        http_response_code(401);
        die(json_encode(['error' => 'Session expired due to 30 minutes of inactivity. Please re-authenticate.', 'code' => 'SESSION_EXPIRED']));
    }
    $_SESSION['last_activity'] = $now;

    $fingerprint = get_client_fingerprint();
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = $fingerprint;
    } elseif (!hash_equals((string)$_SESSION['fingerprint'], $fingerprint)) {
        $_SESSION = [];
        @session_destroy();
        http_response_code(401);
        die(json_encode(['error' => 'Session security validation failed. Please sign in again.', 'code' => 'SESSION_SECURITY_INVALID']));
    }
}

/**
 * Completely purge session data, destroy storage, and invalidate all session cookies.
 */
function destroy_user_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 86400,
                $params['path'] ?? '/', $params['domain'] ?? '',
                $params['secure'] ?? false, $params['httponly'] ?? true
            );
        }
        setcookie('PREFECT_KIOSK_SESSION', '', time() - 86400, '/');
        @session_destroy();
    }
}

// ============================================================
// 3. DYNAMIC SAME-ORIGIN CORS
// ============================================================
$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$http_host = $_SERVER['HTTP_HOST'] ?? '';
if ($http_origin !== '') {
    $origin_host = parse_url($http_origin, PHP_URL_HOST);
    $current_host = preg_replace('/:\d+$/', '', $http_host);
    if ($origin_host === $current_host || $origin_host === 'localhost' || $origin_host === '127.0.0.1') {
        header('Access-Control-Allow-Origin: ' . $http_origin);
        header('Vary: Origin');
    }
}

if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false || strpos($_SERVER['PHP_SELF'] ?? '', '/api/') !== false) {
    header('Content-Type: application/json; charset=utf-8');
}
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================
// 4. CORE HELPERS & INPUT SCRIPT EXECUTION BLOCKER
// ============================================================
function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function fail(string $message, int $code = 400): void {
    respond(['error' => $message], $code);
}

// Maximum 64 KB JSON payload to prevent memory exhaustion DoS
function json_input(): array {
    $content_length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($content_length > 65536) {
        fail('Payload Too Large (max 64 KB allowed)', 413);
    }
    $raw = file_get_contents('php://input');
    if (strlen((string)$raw) > 65536) {
        fail('Payload Too Large (max 64 KB allowed)', 413);
    }
    $data = json_decode((string)$raw, true);
    return is_array($data) ? $data : [];
}

// Clean string: strips HTML tags, control characters, and script injection vectors
function clean_string($val, int $max_length = 255): ?string {
    if ($val === null) return null;
    $s = (string)$val;
    // Strip HTML/XML tags
    $s = strip_tags($s);
    // Remove non-printable ASCII control characters (keep tab, newline, return)
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
    // Neutralize dangerous pseudo-protocols
    $s = preg_replace('/javascript\s*:/i', '', $s);
    $s = preg_replace('/data\s*:\s*text\/html/i', '', $s);
    $s = trim($s);
    if (mb_strlen($s, 'UTF-8') > $max_length) {
        $s = mb_substr($s, 0, $max_length, 'UTF-8');
    }
    return $s !== '' ? $s : null;
}

// Cryptographically secure identifier (16 hex chars = 64 bits entropy)
function new_id(string $prefix): string {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(8)));
}

function clamp_int($value, int $min, int $max): int {
    $v = (int)$value;
    if ($v < $min) return $min;
    if ($v > $max) return $max;
    return $v;
}

function check_rate_limit(string $action, int $max_attempts = 10, int $window_seconds = 300): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'localhost') {
        $max_attempts = max($max_attempts * 10, 100);
    }
    $sanitized_ip = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $ip);
    $cache_dir = sys_get_temp_dir() . '/prefect_ratelimit';
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0777, true);
    }
    $file = $cache_dir . '/' . md5($action . '_' . $sanitized_ip) . '.json';
    $now = time();
    $data = ['attempts' => 0, 'first_attempt' => $now];
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        $decoded = json_decode((string)$content, true);
        if (is_array($decoded) && isset($decoded['first_attempt'], $decoded['attempts'])) {
            if ($now - $decoded['first_attempt'] < $window_seconds) {
                $data = $decoded;
            }
        }
    }
    $data['attempts']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    if ($data['attempts'] > $max_attempts) {
        fail('Too many attempts. Please try again in a few minutes.', 429);
    }
}

// ============================================================
// 5. DATABASE CONNECTION & ARGON2ID HASHING
// ============================================================
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $cfg = require __DIR__ . '/../db_config.php';
    $dsn = 'mysql:host=' . $cfg['host'] . ';dbname=' . $cfg['dbname'] . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        respond(['error' => 'Database connection failed. Check db_config.php.'], 500);
    }
    ensure_defaults($pdo);
    return $pdo;
}

function hash_passcode(string $passcode): string {
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($passcode, PASSWORD_ARGON2ID, [
            'memory_cost' => defined('PASSWORD_ARGON2_DEFAULT_MEMORY_COST') ? PASSWORD_ARGON2_DEFAULT_MEMORY_COST : 65536,
            'time_cost'   => defined('PASSWORD_ARGON2_DEFAULT_TIME_COST')   ? PASSWORD_ARGON2_DEFAULT_TIME_COST   : 4,
            'threads'     => defined('PASSWORD_ARGON2_DEFAULT_THREADS')     ? PASSWORD_ARGON2_DEFAULT_THREADS     : 1,
        ]);
    }
    return password_hash($passcode, PASSWORD_DEFAULT);
}

function ensure_defaults(PDO $pdo): void {
    $row = $pdo->query('SELECT COUNT(*) AS c FROM settings')->fetch();
    if ((int)$row['c'] === 0) {
        $stmt = $pdo->prepare('INSERT INTO settings (id, passcode_hash, late_cutoff) VALUES (1, :hash, :cutoff)');
        $stmt->execute([
            'hash' => hash_passcode('GUILD2026'),
            'cutoff' => '06:15:00',
        ]);
    }
    $row2 = $pdo->query('SELECT COUNT(*) AS c FROM cycles')->fetch();
    if ((int)$row2['c'] === 0) {
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime($start . ' +2 months -1 day'));
        $stmt = $pdo->prepare('INSERT INTO cycles (id, label, start_date, end_date) VALUES (:id, :label, :start, :end)');
        $stmt->execute([
            'id' => new_id('CYC'),
            'label' => 'Cycle 1',
            'start' => $start,
            'end' => $end,
        ]);
    }
    $row3 = $pdo->query('SELECT COUNT(*) AS c FROM admin_users')->fetch();
    if ((int)$row3['c'] === 0) {
        $defaultAdmins = [
            ['id' => 'ADM-DEV01', 'username' => 'developer', 'name' => 'Developer (Super Admin)', 'role' => 'developer', 'pass' => 'DEV@2026'],
            ['id' => 'ADM-TH01', 'username' => 'principal', 'name' => 'Principal Thero', 'role' => 'principal', 'pass' => 'THERO2026'],
            ['id' => 'ADM-HP01', 'username' => 'headprefect', 'name' => 'Head Prefect', 'role' => 'head_prefect', 'pass' => 'HEAD2026'],
            ['id' => 'ADM-TB01', 'username' => 'topboard', 'name' => 'Top Board Operations', 'role' => 'top_board', 'pass' => 'GUILD2026'],
            ['id' => 'ADM-SR01', 'username' => 'senior', 'name' => 'Senior Prefect Desk', 'role' => 'senior_prefect', 'pass' => 'SENIOR2026'],
        ];
        $stmtAdm = $pdo->prepare('INSERT INTO admin_users (id, username, name, role, passcode_hash) VALUES (:id, :u, :name, :role, :hash)');
        foreach ($defaultAdmins as $adm) {
            $stmtAdm->execute([
                'id' => $adm['id'],
                'u' => $adm['username'],
                'name' => $adm['name'],
                'role' => $adm['role'],
                'hash' => hash_passcode($adm['pass']),
            ]);
        }
    }
}

function check_passcode(PDO $pdo, string $passcode): bool {
    if ($passcode === '' || strlen($passcode) > 128) {
        return false;
    }
    // Check settings master passcode
    $row = $pdo->query('SELECT passcode_hash FROM settings WHERE id = 1')->fetch();
    if ($row && password_verify($passcode, (string)$row['passcode_hash'])) {
        return true;
    }
    // Check admin_users table
    $admins = $pdo->query('SELECT passcode_hash FROM admin_users LIMIT 50')->fetchAll();
    foreach ($admins as $adm) {
        if (password_verify($passcode, (string)$adm['passcode_hash'])) {
            return true;
        }
    }
    return false;
}

// Dual-Mode Passcode & RBAC Verification
function require_passcode(PDO $pdo, array $body, ?array $allowedRoles = null): array {
    // 1. Mobile App Bearer JWT Check
    require_once __DIR__ . '/jwt.php';
    $bearer = JWT::getBearerToken();
    if ($bearer !== null) {
        $decoded = JWT::decode($bearer);
        if ($decoded !== null) {
            $role = $decoded['role'] ?? '';
            if ($allowedRoles === null || in_array($role, $allowedRoles, true)) {
                return $decoded;
            }
        }
    }

    // 2. Active Web Session Check
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['authenticated'])) {
        $userRole = $_SESSION['user_role'] ?? 'top_board';
        if ($allowedRoles === null || in_array($userRole, $allowedRoles, true) || $userRole === 'developer') {
            return [
                'user_id' => $_SESSION['user_id'] ?? '',
                'user_name' => $_SESSION['user_name'] ?? '',
                'role' => $userRole
            ];
        }
    }

    // 3. Web Passcode in Body Check
    $pass = (string)($body['passcode'] ?? '');
    if ($pass !== '' && strlen($pass) <= 128) {
        // If username provided, query single user directly (avoids costly loop)
        $username = isset($body['username']) ? clean_string($body['username'], 60) : null;
        if ($username !== null && $username !== '') {
            $stmt = $pdo->prepare('SELECT id, username, name, role, passcode_hash FROM admin_users WHERE LOWER(username) = LOWER(:u) LIMIT 1');
            $stmt->execute(['u' => $username]);
            $adm = $stmt->fetch();
            if ($adm && password_verify($pass, (string)$adm['passcode_hash'])) {
                if ($allowedRoles === null || in_array($adm['role'], $allowedRoles, true) || $adm['role'] === 'developer') {
                    return [
                        'user_id' => $adm['id'],
                        'user_name' => $adm['name'],
                        'role' => $adm['role']
                    ];
                }
            }
        } else {
            // Check admin users
            $admins = $pdo->query('SELECT id, username, name, role, passcode_hash FROM admin_users LIMIT 50')->fetchAll();
            foreach ($admins as $adm) {
                if (password_verify($pass, (string)$adm['passcode_hash'])) {
                    if ($allowedRoles === null || in_array($adm['role'], $allowedRoles, true) || $adm['role'] === 'developer') {
                        return [
                            'user_id' => $adm['id'],
                            'user_name' => $adm['name'],
                            'role' => $adm['role']
                        ];
                    }
                }
            }
        }
        // Fallback to settings master passcode
        $row = $pdo->query('SELECT passcode_hash FROM settings WHERE id = 1')->fetch();
        if ($row && password_verify($pass, $row['passcode_hash'])) {
            if ($allowedRoles === null || in_array('top_board', $allowedRoles, true)) {
                return ['user_id' => 'ADM-TOP01', 'user_name' => 'Top Board Member', 'role' => 'top_board'];
            }
        }
    }

    fail('Access restricted or insufficient permissions.', 401);
    return [];
}
