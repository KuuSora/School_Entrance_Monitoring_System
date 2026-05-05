<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/emailer.php';

// allow scanner to provide `admin_uid`, but prefer the logged-in admin session when present
session_start();

$uid = '';
$_direction = '';
$_gpio = '';
$posted_admin_uid = null;
$admin_uid = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = isset($_POST['uid']) ? trim($_POST['uid']) : '';
    $_direction = isset($_POST['direction']) ? trim($_POST['direction']) : '';
    $_gpio = isset($_POST['gpio']) ? trim($_POST['gpio']) : '';
    $posted_admin_uid = isset($_POST['admin_uid']) ? trim($_POST['admin_uid']) : null;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';
    $_direction = isset($_GET['direction']) ? trim($_GET['direction']) : '';
    $_gpio = isset($_GET['gpio']) ? trim($_GET['gpio']) : '';
    $posted_admin_uid = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : null;
}
if ($posted_admin_uid === '') {
    $posted_admin_uid = null;
}

// session admin takes precedence (browser login). Otherwise accept posted admin UID only if it exists in `admins`.
$session_admin_uid = isset($_SESSION['admin_uid']) ? trim($_SESSION['admin_uid']) : null;
if ($session_admin_uid) {
    $admin_uid = $session_admin_uid;
} elseif ($posted_admin_uid) {
    if (preg_match('/^[0-9A-Fa-f:]+$/', $posted_admin_uid)) {
        $posted_admin_uid_norm = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $posted_admin_uid));
        $stmt_admin = $mysqli->prepare("SELECT uid FROM admins WHERE REPLACE(UPPER(uid), ':', '') = ? LIMIT 1");
        if ($stmt_admin) {
            $stmt_admin->bind_param('s', $posted_admin_uid_norm);
            $stmt_admin->execute();
            $res_admin = $stmt_admin->get_result();
            $found_admin = $res_admin ? $res_admin->fetch_assoc() : null;
            $stmt_admin->close();
            if ($found_admin) {
                $admin_uid = $found_admin['uid'];
            }
        }
    }
}

if ($uid === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing uid']);
    exit;
}

// Determine direction from explicit direction or GPIO pin mapping.
$direction = strtoupper($_direction);
if ($direction === '') {
    if ($_gpio === '5') {
        $direction = 'OUT';
    } else if ($_gpio === '25') {
        $direction = 'IN';
    }
}

if ($direction !== 'IN' && $direction !== 'OUT') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing or invalid direction (use IN/OUT or gpio 5/25)']);
    exit;
}

// Basic UID validation (hex + colons allowed).
if (!preg_match('/^[0-9A-Fa-f:]+$/', $uid)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid uid format']);
    exit;
}

// Normalized UID (uppercase, no separators) for reliable DB matching
$uid_norm = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $uid));

// admin_uid is optional; when provided it's either the logged-in admin or a validated admin UID posted by the scanner

// If a scanner posted an `admin_uid` but it was not accepted (invalid format or not found), log the attempt.
if ($posted_admin_uid !== null && ($admin_uid === null || $admin_uid !== $posted_admin_uid)) {
    $reason = !preg_match('/^[0-9A-Fa-f:]+$/', $posted_admin_uid) ? 'invalid_format' : 'not_found';
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $stmt_rej = $mysqli->prepare("INSERT INTO admin_uid_rejections (posted_admin_uid, scanned_uid, direction, reason, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt_rej) {
        $stmt_rej->bind_param('ssssss', $posted_admin_uid, $uid, $direction, $reason, $ip, $ua);
        $stmt_rej->execute();
        $stmt_rej->close();
    }
}

// Check if user exists across all groups (include admins). Compare normalized UIDs
$stmt = $mysqli->prepare(
    "SELECT name, NULL AS email, 'admin' AS role FROM admins WHERE REPLACE(UPPER(uid), ':', '') = ?
     UNION ALL
     SELECT name, email, 'student' AS role FROM students WHERE REPLACE(UPPER(uid), ':', '') = ?
     UNION ALL
     SELECT name, email, 'faculty' AS role FROM faculty WHERE REPLACE(UPPER(uid), ':', '') = ?
     UNION ALL
     SELECT name, email, 'staff' AS role FROM staff WHERE REPLACE(UPPER(uid), ':', '') = ?
     UNION ALL
     SELECT name, email, 'visitor' AS role FROM visitors WHERE REPLACE(UPPER(uid), ':', '') = ?
     LIMIT 1"
);
$stmt->bind_param('sssss', $uid_norm, $uid_norm, $uid_norm, $uid_norm, $uid_norm);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

$message = '';
$email = '';
if ($user) {
    // User already exists. Give access.
    $name = $user['name'];
    $email = isset($user['email']) ? trim($user['email']) : '';
    $message = "Access Granted: Welcome, " . $name;
} else {
    // New user. Needs registration.
    $name = 'New User';
    $message = "Unregistered card detected.";
}

// Log the scan history with direction (IN/OUT). Include admin_uid when provided.
if ($admin_uid !== null) {
    $stmt = $mysqli->prepare('INSERT INTO scans (uid, direction, admin_uid) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $uid, $direction, $admin_uid);
} else {
    $stmt = $mysqli->prepare('INSERT INTO scans (uid, direction) VALUES (?, ?)');
    $stmt->bind_param('ss', $uid, $direction);
}
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Log failed']);
    exit;
}

echo json_encode(['ok' => true, 'message' => $message, 'name' => $name, 'direction' => $direction]);

if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $scanTime = date('Y-m-d H:i:s');
    send_scan_email($email, $name, $direction, $uid, $scanTime);
}
