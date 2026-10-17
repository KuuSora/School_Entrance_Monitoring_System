<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';
require_once __DIR__ . '/../system/auth.php';
require_once __DIR__ . '/../../bin/textbee_utils.php';

require_admin_session();

$data = json_decode(file_get_contents('php://input'), true);
$uid = trim((string)($data['uid'] ?? ''));
$message = trim((string)($data['message'] ?? ''));
$uidNorm = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $uid));

if ($uidNorm === '' || !preg_match('/^[0-9A-F]+$/', $uidNorm) || $message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Select a student and enter a message']);
    exit;
}
if (mb_strlen($message) > 480) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Message must be 480 characters or less']);
    exit;
}

$stmt = $mysqli->prepare("SELECT uid, name, phone FROM students WHERE REPLACE(UPPER(uid), ':', '') = ? LIMIT 1");
$stmt->bind_param('s', $uidNorm);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$student || trim((string)$student['phone']) === '') {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Student or student phone number not found']);
    exit;
}

$phone = normalize_ph_number((string)$student['phone']);
if (!$phone) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid Philippines phone number']);
    exit;
}

$config = require __DIR__ . '/../system/config.php';
$provider = ($config['sms_provider'] ?? 'sim900a') === 'sim900a' ? 'sim900a' : 'textbee';
if ($provider === 'sim900a') {
    $eventType = 'admin_message';
    $stmt = $mysqli->prepare(
        "INSERT INTO notifications (uid, phone, message, event_type, provider, status)
         VALUES (?, ?, ?, ?, 'sim900a', 'queued')"
    );
    $stmt->bind_param('ssss', $uidNorm, $phone, $message, $eventType);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['ok' => true, 'action' => 'queued_for_sim900a', 'notification_id' => $id]);
        exit;
    }
    $stmt->close();
    $provider = 'textbee';
}

if ($provider === 'textbee' && !empty($config['textbee_enabled'])) {
    $result = send_via_textbee($phone, $message, $config);
    if ($result['ok']) {
        echo json_encode(['ok' => true, 'action' => 'sent_via_textbee']);
        exit;
    }
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'SMS sending failed']);
    exit;
}

http_response_code(503);
echo json_encode(['ok' => false, 'error' => 'SIM900A queue unavailable and TextBee fallback is not enabled']);
