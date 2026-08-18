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

$defaults = [
    'theme' => 'light',
    'font_size' => 'medium',
    'refresh_today_scan' => '5',
    'refresh_inside_now' => '5',
    'refresh_suspicious_alerts' => '10',
    'daily_reset_hour' => '0',
];

$settings = $defaults;
if (ensure_settings_table($mysqli)) {
    $result = $mysqli->query("SELECT setting_key, setting_value FROM settings");
} else {
    $result = false;
}
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $result->free();
}

echo json_encode(['ok' => true, 'data' => $settings]);
