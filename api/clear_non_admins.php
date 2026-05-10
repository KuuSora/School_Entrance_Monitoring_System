<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$MASTER_UID = '97:2A:59:06';

// Safe preview if not confirmed
$confirm = isset($_GET['confirm']) && ($_GET['confirm'] === '1' || $_GET['confirm'] === 'true');
$clear_notifications = isset($_GET['clear_notifications']) && ($_GET['clear_notifications'] === '1');

$tables = [
    'students',
    'faculty',
    'staff',
    'visitors',
    'scans',
    'admin_uid_rejections',
];
if ($clear_notifications) {
    $tables[] = 'notifications';
}

// Helper to get counts
function table_count($mysqli, $table) {
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM `" . $mysqli->real_escape_string($table) . "`");
    if (!$res) return null;
    $row = $res->fetch_assoc();
    return (int)$row['c'];
}

$counts = [];
foreach ($tables as $t) {
    $counts[$t] = table_count($mysqli, $t);
}

if (!$confirm) {
    echo json_encode([
        'ok' => true,
        'message' => 'Preview: call this endpoint with ?confirm=1 to actually perform the deletion. Add &clear_notifications=1 to include the notifications table.',
        'counts' => $counts,
        'master_uid' => $MASTER_UID
    ]);
    exit;
}

// Perform deletion inside transaction
$mysqli->begin_transaction();
try {
    // Ensure master admin exists
    $stmt = $mysqli->prepare('INSERT IGNORE INTO admins (uid, name) VALUES (?, ?)');
    $master_name = 'Master Card';
    $stmt->bind_param('ss', $MASTER_UID, $master_name);
    $stmt->execute();
    $stmt->close();

    // Clear user tables
    $queries = [
        'DELETE FROM students',
        'DELETE FROM faculty',
        'DELETE FROM staff',
        'DELETE FROM visitors',
        'DELETE FROM scans',
        'DELETE FROM admin_uid_rejections',
    ];
    if ($clear_notifications) $queries[] = 'DELETE FROM notifications';

    $results = [];
    foreach ($queries as $q) {
        if (!$mysqli->query($q)) throw new Exception($mysqli->error);
        $results[] = ['query' => $q, 'affected_rows' => $mysqli->affected_rows];
    }

    $mysqli->commit();

    // Recompute counts
    $post_counts = [];
    foreach ($tables as $t) {
        $post_counts[$t] = table_count($mysqli, $t);
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Deletion completed. Admins preserved (master ensured).',
        'before_counts' => $counts,
        'after_counts' => $post_counts,
        'details' => $results
    ]);
} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

?>
