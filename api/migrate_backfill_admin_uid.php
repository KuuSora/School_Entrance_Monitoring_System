<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$MASTER_UID = '97:2A:59:06';
$target = isset($_REQUEST['target']) ? trim($_REQUEST['target']) : $MASTER_UID;
$doApply = isset($_REQUEST['apply']) && ($_REQUEST['apply'] === '1' || $_REQUEST['apply'] === 'true');

// Count rows without admin_uid
$res = $mysqli->query("SELECT COUNT(*) AS c FROM scans WHERE admin_uid IS NULL OR admin_uid = ''");
$countRow = $res ? $res->fetch_assoc() : null;
$count = $countRow ? (int)$countRow['c'] : 0;

if (!$doApply) {
    echo json_encode(['ok' => true, 'preview' => true, 'count' => $count, 'target' => $target, 'message' => 'Send POST or ?apply=1 to apply backfill.']);
    exit;
}

// Apply: create backup table and update
$ts = time();
$backupTable = 'scans_backfill_backup_' . $ts;

$createBackupSql = "CREATE TABLE `" . $backupTable . "` LIKE scans";
if (!$mysqli->query($createBackupSql)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed creating backup table: ' . $mysqli->error]);
    exit;
}

$copySql = "INSERT INTO `" . $backupTable . "` SELECT * FROM scans WHERE admin_uid IS NULL OR admin_uid = ''";
if (!$mysqli->query($copySql)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed copying rows to backup: ' . $mysqli->error]);
    exit;
}

$updateStmt = $mysqli->prepare("UPDATE scans SET admin_uid = ? WHERE admin_uid IS NULL OR admin_uid = ''");
if (!$updateStmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Prepare failed: ' . $mysqli->error]);
    exit;
}
$updateStmt->bind_param('s', $target);
if (!$updateStmt->execute()) {
    $updateStmt->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Update failed: ' . $mysqli->error]);
    exit;
}
$affected = $updateStmt->affected_rows;
$updateStmt->close();

echo json_encode(['ok' => true, 'applied' => true, 'target' => $target, 'updated_rows' => $affected, 'backup_table' => $backupTable]);
