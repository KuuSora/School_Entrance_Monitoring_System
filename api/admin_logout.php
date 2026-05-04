<?php
header('Content-Type: application/json');
session_start();

// Clear session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Create a short auto-login block so background scanner activity doesn't immediately re-login
$block_file = __DIR__ . '/auto_login_block.json';
$blocked_until = time() + 60; // seconds to block auto-login after logout
@file_put_contents($block_file, json_encode(['blocked_until' => $blocked_until]));

echo json_encode(['ok' => true, 'message' => 'Logged out']);
