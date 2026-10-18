<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';
require_once __DIR__ . '/../../bin/textbee_utils.php';

$id = (int)($_POST['id'] ?? 0);
$ok = filter_var($_POST['ok'] ?? false, FILTER_VALIDATE_BOOLEAN);
$error = trim((string)($_POST['error'] ?? 'SIM900A send failed'));
if ($id < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing notification id']);
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM notifications WHERE id=? AND provider='sim900a' AND status='queued' LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$notification = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$notification) {
    echo json_encode(['ok' => true, 'ignored' => true]);
    exit;
}

if ($ok) {
    $stmt = $mysqli->prepare("UPDATE notifications SET status='sent', sim_attempts=sim_attempts+1,
        sent_at=NOW(), last_attempt_at=NOW() WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['ok' => true, 'status' => 'sent']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE notifications SET sim_attempts=sim_attempts+1,
    last_error=?, last_attempt_at=NOW() WHERE id=?");
$stmt->bind_param('si', $error, $id);
$stmt->execute();
$stmt->close();
echo json_encode(['ok' => true, 'status' => 'retrying']);
