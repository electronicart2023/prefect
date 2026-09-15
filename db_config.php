<?php
// Fill these in with the database details your hosting provider gives you
// (cPanel: "MySQL Databases". Usually the host is "localhost").
// Keep this file OUTSIDE your public web folder if your host allows it —
// if not, its being unreadable-as-PHP-source by visitors is what matters,
// which any normal Apache/Nginx + PHP setup already guarantees.
return [
    'host'   => '127.0.0.1',
    'dbname' => 'prefect_db',
    'user'   => 'root',
    'pass'   => '',

    // SECURITY: Generate a unique random secret for JWT token signing.
    // On production, replace with output of: php -r "echo bin2hex(random_bytes(32));"
    'jwt_secret'     => '',

    // SECURITY: Salt for additional hashing operations.
    // On production, replace with output of: php -r "echo bin2hex(random_bytes(32));"
    'passcode_salt'  => 'SKDS_PREFECT_GUILD_2026_JWT_SALT_',
];
