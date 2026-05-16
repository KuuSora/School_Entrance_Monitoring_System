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

// Remove any existing auto-login block so re-login is immediate
$block_file = __DIR__ . '/../state/auto_login_block.json';
if (file_exists($block_file)) {
    @unlink($block_file);
}

// Clear active admin marker
$active_admin_file = __DIR__ . '/../state/active_admin.json';
if (file_exists($active_admin_file)) {
    @unlink($active_admin_file);
}

echo json_encode(['ok' => true, 'message' => 'Logged out']);
