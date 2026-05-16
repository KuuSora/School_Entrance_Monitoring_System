<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$active_admin_file = __DIR__ . '/../state/active_admin.json';

function normalize_uid_value(string $uid): string {
    return strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $uid));
}

function load_active_admin(string $path): ?array {
    if (!file_exists($path)) {
        return null;
    }
    $data = json_decode(@file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function is_same_admin(string $uid_norm, ?array $active): bool {
    if (!$active) {
        return false;
    }
    $active_uid = isset($active['uid']) ? (string)$active['uid'] : '';
    $active_norm = isset($active['uid_norm']) ? (string)$active['uid_norm'] : normalize_uid_value($active_uid);
    return $active_norm !== '' && $active_norm === $uid_norm;
}

function save_active_admin(string $path, string $uid, string $name): void {
    $data = [
        'uid' => $uid,
        'uid_norm' => normalize_uid_value($uid),
        'name' => $name,
        'started_at' => date('Y-m-d H:i:s'),
        'ts' => time()
    ];
    @file_put_contents($path, json_encode($data));
}

$uid = '';
$MASTER_UID = '97:2A:59:06';
$MASTER_UID_NORM = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $MASTER_UID));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = isset($_POST['uid']) ? trim($_POST['uid']) : '';
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';
}

if ($uid === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing uid']);
    exit;
}

if (!preg_match('/^[0-9A-Fa-f:]+$/', $uid)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid uid format']);
    exit;
}

$uid_norm = normalize_uid_value($uid);
$active_admin = load_active_admin($active_admin_file);
if ($active_admin && !is_same_admin($uid_norm, $active_admin)) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'Another admin is already active. Please logout first.']);
    exit;
}

if ($uid_norm === $MASTER_UID_NORM) {
    session_start();
    $_SESSION['admin_uid'] = $uid;
    $_SESSION['admin_name'] = 'Master Card';
    save_active_admin($active_admin_file, $uid, 'Master Card');
    // clear any auto-login block
    $block_file = __DIR__ . '/../state/auto_login_block.json';
    if (file_exists($block_file)) @unlink($block_file);

    echo json_encode(['ok' => true, 'name' => 'Master Card']);
    exit;
}

$stmt = $mysqli->prepare("SELECT uid, name FROM admins WHERE REPLACE(UPPER(uid), ':', '') = ? LIMIT 1");
$stmt->bind_param('s', $uid_norm);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if (!$admin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not authorized']);
    exit;
}

session_start();
$_SESSION['admin_uid'] = $admin['uid'];
$_SESSION['admin_name'] = $admin['name'];
save_active_admin($active_admin_file, $admin['uid'], $admin['name']);

// clear any auto-login block
$block_file = __DIR__ . '/../state/auto_login_block.json';
if (file_exists($block_file)) @unlink($block_file);

echo json_encode(['ok' => true, 'name' => $admin['name']]);
