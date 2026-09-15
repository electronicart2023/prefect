<?php
declare(strict_types=1);

// Lightweight, pure PHP 7.4 HS256 JWT implementation with client anti-hijacking binding
class JWT {
    private static function getSecret(): string {
        $cfg = require __DIR__ . '/../db_config.php';
        // Prefer dedicated JWT secret; fall back to salted DB password for backward compatibility
        $dedicated = isset($cfg['jwt_secret']) ? trim((string)$cfg['jwt_secret']) : '';
        if ($dedicated !== '') {
            return hash('sha256', $dedicated);
        }
        return hash('sha256', ($cfg['passcode_salt'] ?? 'SKDS_PREFECT_GUILD_2026_JWT_SALT_') . ($cfg['pass'] ?? ''));
    }

    public static function generateClientFingerprint(): string {
        if (function_exists('get_client_fingerprint')) {
            return get_client_fingerprint();
        }
        $rawIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ipSubnet = $rawIp;
        if (filter_var($rawIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $rawIp);
            $ipSubnet = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN_AGENT';
        return hash('sha256', $ipSubnet . '|' . $ua);
    }

    public static function encode(array $payload, ?int $expiresIn = 604800): string {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now = time();
        $payload['iat'] = $now;
        if ($expiresIn !== null) {
            $payload['exp'] = $now + $expiresIn;
        }
        $payload['fgp'] = self::generateClientFingerprint();

        $b64Header = self::base64UrlEncode((string)json_encode($header));
        $b64Payload = self::base64UrlEncode((string)json_encode($payload));

        $signature = hash_hmac('sha256', $b64Header . '.' . $b64Payload, self::getSecret(), true);
        $b64Sig = self::base64UrlEncode($signature);

        return $b64Header . '.' . $b64Payload . '.' . $b64Sig;
    }

    public static function decode(string $jwt): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $b64Header = $parts[0];
        $b64Payload = $parts[1];
        $b64Sig = $parts[2];

        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', $b64Header . '.' . $b64Payload, self::getSecret(), true)
        );

        if (!hash_equals($expectedSig, $b64Sig)) {
            return null; // Invalid cryptographic signature
        }

        $payload = json_decode(self::base64UrlDecode($b64Payload), true);
        if (!is_array($payload)) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && time() > (int)$payload['exp']) {
            return null; // Token expired
        }

        // Anti-Hijacking: Check client IP + User-Agent fingerprint
        if (isset($payload['fgp'])) {
            $currentFingerprint = self::generateClientFingerprint();
            if (!hash_equals((string)$payload['fgp'], $currentFingerprint)) {
                return null; // Token hijacked (used from different IP or browser)
            }
        }

        return $payload;
    }

    public static function getBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if (!$header && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $k => $v) {
                if (strtolower($k) === 'authorization') {
                    $header = $v;
                    break;
                }
            }
        }
        if ($header && preg_match('/Bearer\s+(\S+)/i', (string)$header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
