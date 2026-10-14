<?php
declare(strict_types=1);

/** Automatically upgrade existing databases, including MariaDB 5.5 installations. */
function vehicle_log_upgrade(PDO $pdo): void
{
    // MySQL advisory lock prevents simultaneous requests from racing the ALTER TABLE.
    $lockName = 'vehicle_log_schema_' . substr(sha1((string)cfg()['db']['name']), 0, 16);
    $stmt = $pdo->prepare('SELECT GET_LOCK(?, 15)');
    $stmt->execute([$lockName]);
    if ((int)$stmt->fetchColumn() !== 1) {
        throw new RuntimeException('Another database upgrade is running. Please try again shortly.');
    }
    try {
        // The original V1 database has no schema_version setting. Check the actual
        // column so previously upgraded databases are handled without duplicate ALTERs.
        $columns = $pdo->query("SHOW COLUMNS FROM readings LIKE 'trip_description'")->fetchAll();
        if (!$columns) {
            $pdo->exec("ALTER TABLE readings ADD COLUMN trip_description VARCHAR(255) NOT NULL DEFAULT ''");
        }
        // Retain legacy readings unchanged; new journeys use a separate trips table.
        $pdo->exec(file_get_contents(APP_ROOT.'/migrations/003_trips.sql'));
        $stmt = $pdo->prepare('UPDATE settings SET value = ? WHERE name = ?');
        $stmt->execute(['3', 'schema_version']);
        if ($stmt->rowCount() === 0) {
            // UPDATE can return zero if the value is already 3; REPLACE handles both cases.
            $stmt = $pdo->prepare('REPLACE INTO settings (name, value) VALUES (?, ?)');
            $stmt->execute(['schema_version', '3']);
        }
    } finally {
        $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $stmt->execute([$lockName]);
    }
}
