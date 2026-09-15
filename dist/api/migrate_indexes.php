<?php
declare(strict_types=1);

/**
 * Migration: Add missing indexes for performance optimization
 * Run once: php migrate_indexes.php
 * Safe to re-run (uses IF NOT EXISTS pattern via try/catch)
 */

require __DIR__ . '/db.php';
$pdo = db();

$migrations = [
    'ALTER TABLE prefects ADD INDEX idx_deleted (deleted_at)',
    'ALTER TABLE entries ADD INDEX idx_deleted (deleted_at)',
    'ALTER TABLE deductions ADD INDEX idx_deleted (deleted_at)',
    'ALTER TABLE entries ADD INDEX idx_active_date (deleted_at, entry_date)',
    'ALTER TABLE deductions ADD INDEX idx_active_date (deleted_at, ded_date)',
    'ALTER TABLE prefects ADD COLUMN deleted_by VARCHAR(60) DEFAULT NULL AFTER deleted_at',
];

foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: {$sql}\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "SKIP (already exists): {$sql}\n";
        } else {
            echo "ERR: {$sql} — " . $e->getMessage() . "\n";
        }
    }
}

echo "\nMigration complete.\n";
