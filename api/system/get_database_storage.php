<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

session_start();
if (!isset($_SESSION['admin_uid'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$result = $mysqli->query("
    SELECT TABLE_NAME AS table_name,
           ENGINE AS engine,
           COALESCE(TABLE_ROWS, 0) AS table_rows,
           COALESCE(DATA_LENGTH, 0) AS data_bytes,
           COALESCE(INDEX_LENGTH, 0) AS index_bytes,
           COALESCE(DATA_LENGTH, 0) + COALESCE(INDEX_LENGTH, 0) AS total_bytes
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY total_bytes DESC, TABLE_NAME ASC
");

if (!$result) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to read database storage']);
    exit;
}

$tables = [];
while ($row = $result->fetch_assoc()) {
    $engine = $row['engine'] ?? '';
    $tables[] = [
        'table_name' => $row['table_name'],
        'engine' => $engine,
        'rows' => (int) $row['table_rows'],
        'data_bytes' => (int) $row['data_bytes'],
        'index_bytes' => (int) $row['index_bytes'],
        'total_bytes' => (int) $row['total_bytes'],
        'rows_are_estimated' => strtolower($engine) === 'innodb'
    ];
}
$result->free();

$total_rows = 0;
$total_data_bytes = 0;
$total_index_bytes = 0;
$total_bytes = 0;
foreach ($tables as $table) {
    $total_rows += $table['rows'];
    $total_data_bytes += $table['data_bytes'];
    $total_index_bytes += $table['index_bytes'];
    $total_bytes += $table['total_bytes'];
}

$databaseResult = $mysqli->query('SELECT DATABASE() AS database_name');
$databaseName = null;
if ($databaseResult) {
    $databaseRow = $databaseResult->fetch_assoc();
    $databaseName = $databaseRow['database_name'] ?? null;
    $databaseResult->free();
}

echo json_encode([
    'ok' => true,
    'database_name' => $databaseName,
    'generated_at' => date('c'),
    'table_count' => count($tables),
    'total_rows' => $total_rows,
    'data_bytes' => $total_data_bytes,
    'index_bytes' => $total_index_bytes,
    'total_bytes' => $total_bytes,
    'tables' => $tables
]);
