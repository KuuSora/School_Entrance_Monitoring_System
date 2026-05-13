<?php
/**
 * TextBee SMS gateway utilities
 */

function normalize_ph_number(string $num): ?string {
    $n = preg_replace('/[^0-9+]/', '', $num);

    if (preg_match('/^\+639[0-9]{9}$/', $n)) {
        return $n;
    }

    if (preg_match('/^09[0-9]{9}$/', $n)) {
        return '+63' . substr($n, 1);
    }

    if (preg_match('/^9[0-9]{9}$/', $n)) {
        return '+63' . $n;
    }

    return null;
}

function send_via_textbee(string $phone, string $message, array $config): array {
    if (empty($config['textbee_api_key']) || empty($config['textbee_device_id'])) {
        return ['ok' => false, 'error' => 'TextBee API key or device ID not configured'];
    }

    $apiUrl = rtrim($config['textbee_api_url'] ?? 'https://api.textbee.dev/api/v1', '/');
    $deviceId = trim($config['textbee_device_id']);
    $endpoint = $apiUrl . '/gateway/devices/' . rawurlencode($deviceId) . '/send-sms';

    $payload = [
        'recipients' => [$phone],
        'message' => $message,
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $config['textbee_api_key'],
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['ok' => false, 'error' => 'cURL error: ' . $curlError];
    }

    $json = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['ok' => false, 'error' => 'Invalid JSON response: ' . substr((string)$response, 0, 200)];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['ok' => true, 'response' => $json];
    }

    $error = $json['message'] ?? $json['error'] ?? 'Unknown error';
    return ['ok' => false, 'error' => 'HTTP ' . $httpCode . ': ' . $error, 'response' => $json];
}

function send_scan_sms(mysqli $mysqli, string $toPhone, string $toName, string $direction, string $uid, string $scanTime): array {
    $config = require __DIR__ . '/../api/config.php';

    if (empty($config['textbee_enabled'])) {
        return ['ok' => false, 'error' => 'TextBee disabled'];
    }

    $direction = strtoupper($direction);
    if ($direction !== 'IN') {
        return ['ok' => true, 'action' => 'suppressed', 'reason' => 'Direction not IN'];
    }

    $normalizedPhone = normalize_ph_number($toPhone);
    if (!$normalizedPhone) {
        return ['ok' => false, 'error' => 'Invalid Philippines phone number'];
    }

    $eventType = 'scan_in';
    $stmt = $mysqli->prepare(
        "SELECT COUNT(*) AS cnt FROM notifications WHERE uid = ? AND event_type = ? AND status = 'sent' AND DATE(sent_at) = CURDATE()"
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => 'Failed to prepare SMS limit check'];
    }
    $stmt->bind_param('ss', $uid, $eventType);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)($row['cnt'] ?? 0) > 0) {
        return ['ok' => true, 'action' => 'suppressed', 'reason' => 'Already sent one SMS to this user today'];
    }

    $message = "Hello " . ($toName !== '' ? $toName : 'there') . ", your RFID card was scanned. " .
        "Direction: " . $direction . ", UID: " . $uid . ", Time: " . $scanTime . ".";

    $stmt = $mysqli->prepare(
        "INSERT INTO notifications (uid, phone, message, event_type, provider, status) VALUES (?, ?, ?, ?, 'textbee', 'queued')"
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => 'Failed to queue SMS'];
    }
    $stmt->bind_param('ssss', $uid, $normalizedPhone, $message, $eventType);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['ok' => false, 'error' => 'Failed to queue SMS'];
    }
    $notificationId = $stmt->insert_id;
    $stmt->close();

    $sendResult = send_via_textbee($normalizedPhone, $message, $config);
    if (!$sendResult['ok']) {
        $mysqli->query(
            "UPDATE notifications SET status = 'failed', last_error = '" . $mysqli->real_escape_string($sendResult['error']) . "', last_attempt_at = NOW() WHERE id = " . (int)$notificationId
        );
        return ['ok' => false, 'error' => 'TextBee send failed: ' . $sendResult['error']];
    }

    $mysqli->query(
        "UPDATE notifications SET status = 'sent', sent_at = NOW(), last_attempt_at = NOW() WHERE id = " . (int)$notificationId
    );

    return [
        'ok' => true,
        'action' => 'sent',
        'notification_id' => $notificationId,
        'response' => $sendResult['response'],
    ];
}