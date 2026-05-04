<?php
header('Content-Type: application/json');
$block_file = __DIR__ . '/auto_login_block.json';
$blocked_until = 0;
if (file_exists($block_file)) {
    $data = json_decode(@file_get_contents($block_file), true);
    if (isset($data['blocked_until'])) {
        $blocked_until = (int)$data['blocked_until'];
    }
}

echo json_encode(['ok' => true, 'blocked_until' => $blocked_until]);
