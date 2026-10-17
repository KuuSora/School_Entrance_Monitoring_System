<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$result = $mysqli->query(
    "SELECT id, phone, message FROM notifications
     WHERE provider = 'sim900a' AND status = 'queued'
     ORDER BY id ASC LIMIT 1"
);
$row = $result ? $result->fetch_assoc() : null;
if (!$row) {
    echo json_encode(['ok' => true, 'data' => null]);
    exit;
}

$id = (int)$row['id'];
$stmt = $mysqli->prepare(
    "UPDATE notifications SET status = 'sent', sent_at = NOW(), last_attempt_at = NOW()
     WHERE id = ? AND status = 'queued'"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$claimed = $stmt->affected_rows === 1;
$stmt->close();

echo json_encode(['ok' => true, 'data' => $claimed ? [
    'id' => $id,
    'phone' => $row['phone'],
    'message' => $row['message'],
] : null]);
