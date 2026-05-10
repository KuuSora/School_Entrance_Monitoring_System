<?php
header('Content-Type: application/json');
// simple diagnostics endpoint
$now = date('c');
echo json_encode(['ok' => true, 'time' => $now, 'path' => __FILE__]);
