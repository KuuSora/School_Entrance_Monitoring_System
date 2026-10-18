<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';
require_once __DIR__ . '/../system/auth.php';
require_admin_session();
$mysqli->query("UPDATE dashboard_notifications SET is_read=1 WHERE is_read=0");
echo json_encode(['ok' => true]);
