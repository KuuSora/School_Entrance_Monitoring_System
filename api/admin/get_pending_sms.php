<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';
require_once __DIR__ . '/../../bin/textbee_utils.php';
require_once __DIR__ . '/ensure_sms_schema.php';
$config = require __DIR__ . '/../system/config.php';

if (!ensure_sms_queue_schema($mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'SMS queue schema is unavailable']);
    exit;
}

if (strtolower((string)($config['sms_provider'] ?? 'sim900a')) === 'textbee') {
    $textbeeResult = $mysqli->query(
        "SELECT * FROM notifications
         WHERE provider = 'textbee' AND status = 'queued' AND textbee_attempts < 1
         ORDER BY id ASC LIMIT 1"
    );
    $textbeeRow = $textbeeResult ? $textbeeResult->fetch_assoc() : null;
    if ($textbeeRow) {
        $sendResult = !empty($config['textbee_enabled'])
            ? send_via_textbee($textbeeRow['phone'], $textbeeRow['message'], $config)
            : ['ok' => false, 'error' => 'TextBee is not configured'];
        if ($sendResult['ok']) {
            $stmt = $mysqli->prepare("UPDATE notifications SET status='sent',
                textbee_attempts=textbee_attempts+1, sent_at=NOW(), last_attempt_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $textbeeRow['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            $error = $sendResult['error'] ?? 'TextBee failed';
            $stmt = $mysqli->prepare("UPDATE notifications SET status='failed',
                textbee_attempts=textbee_attempts+1, last_error=?, last_attempt_at=NOW() WHERE id=?");
            $stmt->bind_param('si', $error, $textbeeRow['id']);
            $stmt->execute();
            $stmt->close();
            create_sms_failure_notification($mysqli, $textbeeRow, $error);
        }
    }
}

// The ESP32 polls this endpoint. Do not mark a message sent until the modem
// confirms it; this is what makes retries and the one-minute fallback reliable.
$mysqli->query(
    "UPDATE notifications SET status = 'failed', last_error = 'SIM900A retry limit reached',
     last_attempt_at = NOW()
     WHERE provider = 'sim900a' AND status = 'queued' AND sim_attempts >= 2
       AND (fallback_at IS NULL OR fallback_at <= NOW())"
);

$fallback = $mysqli->query(
    "SELECT * FROM notifications
     WHERE status = 'failed'
       AND ((provider = 'sim900a' AND sim_attempts >= 2)
            OR (provider = 'textbee' AND textbee_attempts < 1))
       AND (fallback_at IS NULL OR fallback_at <= NOW())
     ORDER BY id ASC LIMIT 1"
);
$fallbackRow = $fallback ? $fallback->fetch_assoc() : null;
if ($fallbackRow) {
    $result = !empty($config['textbee_enabled'])
        ? send_via_textbee($fallbackRow['phone'], $fallbackRow['message'], $config)
        : ['ok' => false, 'error' => 'TextBee is disabled'];
    if ($result['ok']) {
        $stmt = $mysqli->prepare("UPDATE notifications SET provider='textbee', status='sent',
            textbee_attempts=textbee_attempts+1, sent_at=NOW(), last_attempt_at=NOW() WHERE id=?");
        $stmt->bind_param('i', $fallbackRow['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $mysqli->prepare("UPDATE notifications SET provider='textbee',
            textbee_attempts=textbee_attempts+1, last_error=?, last_attempt_at=NOW() WHERE id=?");
        $error = $result['error'] ?? 'TextBee failed';
        $stmt->bind_param('si', $error, $fallbackRow['id']);
        $stmt->execute();
        $stmt->close();
        create_sms_failure_notification($mysqli, $fallbackRow, $error);
        $mysqli->query("UPDATE notifications SET status='failed' WHERE id=" . (int)$fallbackRow['id']);
    }
}

$result = $mysqli->query(
    "SELECT id, phone, message, sim_attempts FROM notifications
     WHERE provider = 'sim900a' AND status = 'queued' AND sim_attempts < 2
     ORDER BY id ASC LIMIT 1"
);
$row = $result ? $result->fetch_assoc() : null;
if (!$row) {
    echo json_encode(['ok' => true, 'data' => null]);
    exit;
}

$id = (int)$row['id'];
echo json_encode(['ok' => true, 'data' => [
    'id' => $id,
    'phone' => $row['phone'],
    'message' => $row['message'],
    'attempt' => (int)$row['sim_attempts'] + 1,
]]);
