<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$device = strtolower(trim((string)($_POST['device'] ?? $_GET['device'] ?? '')));
$allowed = ['main_system', 'portable', 'sms_module'];
if (!in_array($device, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid device']);
    exit;
}

$stateFile = __DIR__ . '/../state/device_heartbeats.json';
$state = [];
if (is_file($stateFile)) {
    $decoded = json_decode((string)@file_get_contents($stateFile), true);
    if (is_array($decoded)) {
        $state = $decoded;
    }
}
$state[$device] = [
    'last_seen' => time(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
];
@file_put_contents($stateFile, json_encode($state), LOCK_EX);
echo json_encode(['ok' => true]);
