<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';
require_once __DIR__ . '/../system/auth.php';
require_admin_session();

$notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
if ($notificationId !== null && $notificationId !== false) {
    $statement = $mysqli->prepare(
        "UPDATE dashboard_notifications SET is_read=1 WHERE id=? AND is_read=0"
    );
    if (!$statement) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Unable to prepare notification update']);
        exit;
    }
    $statement->bind_param('i', $notificationId);
    $updated = $statement->execute();
    $statement->close();
} else {
    $updated = $mysqli->query("UPDATE dashboard_notifications SET is_read=1 WHERE is_read=0");
}

if (!$updated) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to mark notification as read']);
    exit;
}

echo json_encode(['ok' => true]);
