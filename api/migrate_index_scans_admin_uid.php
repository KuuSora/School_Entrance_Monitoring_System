<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$indexName = 'idx_scans_admin_uid_uid';

$stmt = $mysqli->prepare(
    'SELECT COUNT(*) AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Prepare failed']);
    exit;
}
$table = 'scans';
$stmt->bind_param('ss', $table, $indexName);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

$exists = $row ? ((int)$row['c'] > 0) : false;
if ($exists) {
    echo json_encode(['ok' => true, 'message' => 'Index already exists']);
    exit;
}

$sql = "ALTER TABLE scans ADD INDEX " . $indexName . " (admin_uid, uid)";
if ($mysqli->query($sql)) {
    echo json_encode(['ok' => true, 'message' => 'Index created']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $mysqli->error]);
}
