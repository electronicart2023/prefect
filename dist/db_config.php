<?php
// Sri Kalyani Dhamma School — Prefect Guild Merit Register
// Production Database Configuration Template (db_config.example.php)
//
// INSTRUCTIONS:
// 1. Copy this file to 'db_config.php'.
// 2. Fill in your live MySQL/MariaDB database credentials provided by your hosting (cPanel).
// 3. Keep db_config.php secure (protected by .htaccess).
// 4. Generate unique 64-hex-char keys for jwt_secret and passcode_salt using:
//    php -r "echo bin2hex(random_bytes(32));"

return [
    'host'   => 'localhost',
    'dbname' => 'skds_prefect_db',
    'user'   => 'skds_db_user',
    'pass'   => 'YourStrongSecurePasswordHere',

    // SECURITY: Generate a unique random secret for JWT token signing.
    'jwt_secret'     => 'GENERATE_A_64_CHAR_HEX_SECRET_FOR_PRODUCTION_JWT',

    // SECURITY: Salt for additional cryptographic operations.
    'passcode_salt'  => 'SKDS_PREFECT_GUILD_2026_JWT_SALT_',
];
