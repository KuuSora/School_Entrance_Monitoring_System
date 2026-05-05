<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function column_exists($mysqli, $table, $column) {
    $stmt = $mysqli->prepare(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ? ((int)$row['c'] > 0) : false;
}

$changes = [];
$tables = ['students', 'faculty', 'staff', 'visitors'];
foreach ($tables as $table) {
    if (!column_exists($mysqli, $table, 'email')) {
        if ($mysqli->query("ALTER TABLE {$table} ADD COLUMN email VARCHAR(255) NULL")) {
            $changes[] = "added email to {$table}";
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $mysqli->error]);
            exit;
        }
    }
}

if (!column_exists($mysqli, 'scans', 'admin_uid')) {
    if ($mysqli->query("ALTER TABLE scans ADD COLUMN admin_uid VARCHAR(64) NULL")) {
        $changes[] = 'added admin_uid to scans';
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $mysqli->error]);
        exit;
    }
}

echo json_encode(['ok' => true, 'changes' => $changes]);
