<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$MASTER_UID = '97:2A:59:06';
$MASTER_NAME = 'Master Card';

// Check if exists
$stmt = $mysqli->prepare('SELECT uid FROM admins WHERE uid = ? LIMIT 1');
$stmt->bind_param('s', $MASTER_UID);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    echo json_encode(['ok' => true, 'message' => 'Master admin already exists']);
    exit;
}
$stmt->close();

$stmt = $mysqli->prepare('INSERT INTO admins (uid, name) VALUES (?, ?)');
$stmt->bind_param('ss', $MASTER_UID, $MASTER_NAME);
if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'message' => 'Master admin inserted']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $mysqli->error]);
}
$stmt->close();
