<?php
header('Content-Type: application/json');

$signal_file = __DIR__ . '/../state/admin_scan_signal.json';
$consume = isset($_REQUEST['consume']) && (string)$_REQUEST['consume'] === '1';

if (!file_exists($signal_file)) {
    echo json_encode(['ok' => true, 'data' => null]);
    exit;
}

$data = json_decode(@file_get_contents($signal_file), true);
if (!is_array($data)) {
    $data = null;
}

if ($consume) {
    @unlink($signal_file);
}

echo json_encode(['ok' => true, 'data' => $data]);
