<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function ensure_settings_table(mysqli $mysqli): bool {
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(64) PRIMARY KEY,
        setting_value TEXT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    return (bool) $mysqli->query($sql);
}

session_start();
$admin_uid = $_SESSION['admin_uid'] ?? null;
if (!$admin_uid) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$allowed = [
    'theme',
    'font_size',
    'refresh_today_scan',
    'refresh_inside_now',
    'refresh_suspicious_alerts',
    'daily_reset_hour',
];

if (!ensure_settings_table($mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Settings table unavailable']);
    exit;
}

$updated = [];
$stmt = $mysqli->prepare("
    INSERT INTO settings (setting_key, setting_value)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB prepare failed']);
    exit;
}

foreach ($allowed as $key) {
    if (!array_key_exists($key, $data)) {
        continue;
    }
    $value = $data[$key];
    if ($value === null || $value === '') {
        $value = null;
    }
    if (!$stmt->bind_param('ss', $key, $value)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'DB bind failed']);
        $stmt->close();
        exit;
    }
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'DB update failed']);
        $stmt->close();
        exit;
    }
    $updated[] = $key;
}
$stmt->close();

echo json_encode(['ok' => true, 'updated' => $updated]);
