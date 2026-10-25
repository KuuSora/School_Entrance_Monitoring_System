<?php

/**
 * Keep the live database compatible with the SMS retry queue.
 *
 * Older installations may have the notifications table without the retry
 * columns even though the current SQL schema defines them.
 */
function ensure_sms_queue_schema(mysqli $mysqli): bool {
    $columns = [
        'attempt_count' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'sim_attempts' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'textbee_attempts' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'fallback_at' => 'DATETIME NULL',
    ];

    foreach ($columns as $name => $definition) {
        $columnName = $mysqli->real_escape_string($name);
        $result = $mysqli->query("SHOW COLUMNS FROM notifications LIKE '{$columnName}'");
        if ($result === false) {
            return false;
        }
        $exists = $result->num_rows > 0;
        $result->free();
        if (!$exists && !$mysqli->query("ALTER TABLE notifications ADD COLUMN `{$columnName}` {$definition}")) {
            return false;
        }
    }

    return true;
}
