<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../bin/textbee_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$config = require __DIR__ . '/config.php';
if (empty($config['textbee_enabled'])) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'TextBee notifications not enabled']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$uid = isset($input['uid']) ? trim($input['uid']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$eventType = isset($input['event_type']) ? trim($input['event_type']) : 'scan_alert';

if ($uid === '' || $phone === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing uid, phone, or message']);
    exit;
}

$phone = normalize_ph_number($phone);
if (!$phone) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid Philippines phone number']);
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT COUNT(*) AS cnt FROM notifications WHERE uid = ? AND status = 'sent' AND DATE(sent_at) = CURDATE()"
);
$stmt->bind_param('s', $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ((int)($row['cnt'] ?? 0) > 0) {
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'action' => 'suppressed',
        'reason' => 'Already sent one SMS to this user today'
    ]);
    exit;
}

$stmt = $mysqli->prepare(
    "INSERT INTO notifications (uid, phone, message, event_type, provider, status) VALUES (?, ?, ?, ?, 'textbee', 'queued')"
);
$stmt->bind_param('ssss', $uid, $phone, $message, $eventType);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to queue SMS']);
    exit;
}
$notificationId = $stmt->insert_id;
$stmt->close();

$sendResult = send_via_textbee($phone, $message, $config);

if (!$sendResult['ok']) {
    $mysqli->query(
        "UPDATE notifications SET status = 'failed', last_error = '" . $mysqli->real_escape_string($sendResult['error']) . "', last_attempt_at = NOW() WHERE id = " . (int)$notificationId
    );
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'TextBee send failed: ' . $sendResult['error']]);
    exit;
}

$mysqli->query(
    "UPDATE notifications SET status = 'sent', sent_at = NOW(), last_attempt_at = NOW() WHERE id = " . (int)$notificationId
);

echo json_encode([
    'ok' => true,
    'action' => 'sent',
    'notification_id' => $notificationId,
    'provider' => 'textbee',
    'response' => $sendResult['response']
]);