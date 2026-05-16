<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$result = $mysqli->query("SELECT uid, name FROM admins ORDER BY name ASC");
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}

echo json_encode(['ok' => true, 'data' => $rows]);
