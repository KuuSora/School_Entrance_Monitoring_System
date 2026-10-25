<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$stateFile = __DIR__ . '/../state/device_heartbeats.json';
$state = [];
if (is_file($stateFile)) {
    $decoded = json_decode((string)@file_get_contents($stateFile), true);
    if (is_array($decoded)) {
        $state = $decoded;
    }
}
$now = time();
$devices = [];
foreach (['main_system', 'portable', 'sms_module'] as $device) {
    $lastSeen = (int)($state[$device]['last_seen'] ?? 0);
    $devices[$device] = [
        'online' => $lastSeen > 0 && ($now - $lastSeen) <= 90,
        'last_seen' => $lastSeen > 0 ? date('c', $lastSeen) : null,
    ];
}
echo json_encode(['ok' => true, 'data' => $devices]);
