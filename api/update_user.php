<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = isset($_POST['uid']) ? trim($_POST['uid']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';

    if ($uid !== '' && $name !== '') {
        $tables = ['students', 'faculty', 'staff', 'visitors'];
        $ok = false;
        foreach ($tables as $table) {
            $stmt = $mysqli->prepare("UPDATE {$table} SET name = ? WHERE uid = ?");
            $stmt->bind_param('ss', $name, $uid);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $ok = true;
                $stmt->close();
                break;
            }
            $stmt->close();
        }

        echo json_encode(['ok' => $ok]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Invalid data']);
