<?php

function ensure_admin_roles(mysqli $mysqli): bool {
    $columnResult = $mysqli->query("SHOW COLUMNS FROM admins LIKE 'admin_role'");
    if (!$columnResult) {
        return false;
    }

    $hasRoleColumn = $columnResult->num_rows > 0;
    $columnResult->free();

    if (!$hasRoleColumn && !$mysqli->query(
        "ALTER TABLE admins ADD COLUMN admin_role VARCHAR(16) NOT NULL DEFAULT 'admin'"
    )) {
        return false;
    }

    $masterColumnResult = $mysqli->query("SHOW COLUMNS FROM admins LIKE 'is_master'");
    if (!$masterColumnResult) {
        return false;
    }
    $hasMasterColumn = $masterColumnResult->num_rows > 0;
    $masterColumnResult->free();

    // Keep the newer role column aligned when upgrading a database that has
    // the legacy master-card flag. Newer schemas use admin_role directly.
    if ($hasMasterColumn && !$mysqli->query(
        "UPDATE admins SET admin_role = CASE WHEN is_master = 1 THEN 'master' ELSE 'admin' END"
    )) {
        return false;
    }

    // Preserve the single-master-card rule when upgrading an existing database.
    $masterResult = $mysqli->query(
        "SELECT uid FROM admins WHERE admin_role = 'master' LIMIT 1"
    );
    if (!$masterResult) {
        return false;
    }
    $hasMaster = $masterResult->num_rows > 0;
    $masterResult->free();

    if (!$hasMaster) {
        $firstAdmin = $mysqli->query("SELECT uid FROM admins ORDER BY uid ASC LIMIT 1");
        if ($firstAdmin && ($row = $firstAdmin->fetch_assoc())) {
            $uid = $mysqli->real_escape_string($row['uid']);
            if (!$mysqli->query("UPDATE admins SET admin_role = 'master' WHERE uid = '{$uid}'")) {
                $firstAdmin->free();
                return false;
            }
        }
        if ($firstAdmin) {
            $firstAdmin->free();
        }
    }

    return true;
}

function start_admin_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function require_admin_session(): void {
    start_admin_session();
    if (!isset($_SESSION['admin_uid'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }
    // API requests only read the session. Release PHP's session file lock so
    // concurrent dashboard requests do not wait on one another.
    session_write_close();
}

function is_master_admin(): bool {
    return ($_SESSION['admin_role'] ?? 'admin') === 'master';
}

function require_master_admin(): void {
    require_admin_session();
    if (!is_master_admin()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Master card required']);
        exit;
    }
}
