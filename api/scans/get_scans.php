<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
if ($limit <= 0 || $limit > 500) {
    $limit = 200;
}

$resetHour = 0;
$resSetting = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'daily_reset_hour' LIMIT 1");
if ($resSetting) {
    $settingRow = $resSetting->fetch_assoc();
    $resSetting->free();
    if ($settingRow && is_numeric($settingRow['setting_value'])) {
        $resetHour = (int)$settingRow['setting_value'];
    }
}

$now = new DateTime('now');
$currentHour = (int)$now->format('G');
if ($currentHour >= $resetHour) {
    $dayStart = clone $now;
    $dayStart->setTime($resetHour, 0, 0);
} else {
    $dayStart = clone $now;
    $dayStart->modify('-1 day')->setTime($resetHour, 0, 0);
}
$dayStartStr = $dayStart->format('Y-m-d H:i:s');
$dayEnd = clone $dayStart;
$dayEnd->modify('+1 day');
$dayEndStr = $dayEnd->format('Y-m-d H:i:s');

// Add admin_uid filter if provided
$admin_uid_filter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : null;
$suspicious_filter = isset($_GET['suspicious']) ? (int)$_GET['suspicious'] : 0;

$whereParts = [];
if ($admin_uid_filter !== null && $admin_uid_filter !== '') {
    $whereParts[] = "s.admin_uid = '" . $mysqli->real_escape_string($admin_uid_filter) . "'";
}
// suspicious filter will be applied after the suspicious subquery join by checking ss.cur_id IS NOT NULL
if ($suspicious_filter) {
    $whereParts[] = 'ss.cur_id IS NOT NULL';
}
$where_clause = '';
if (count($whereParts) > 0) {
    $where_clause = 'WHERE ' . implode(' AND ', $whereParts);
}

$result = $mysqli->query(
    'SELECT s.id, s.uid, s.direction, s.created_at, s.admin_uid,
            COALESCE(p.name, "New User") AS name, p.role,
            COALESCE(p.department, "") AS department,
            COALESCE(a.name, "") AS admin_name,
            CASE WHEN ss.cur_id IS NULL THEN 0 ELSE 1 END AS suspicious
     FROM scans s
     LEFT JOIN (
          SELECT uid, name, "student" AS role, NULL AS department FROM students
          UNION ALL
          SELECT uid, name, "faculty" AS role, department FROM faculty
          UNION ALL
          SELECT uid, name, "staff" AS role, department FROM staff
          UNION ALL
          SELECT uid, name, "visitor" AS role, NULL AS department FROM visitors
          UNION ALL
          SELECT uid, name, "admin" AS role, NULL AS department FROM admins
      ) p ON s.uid = p.uid
     LEFT JOIN admins a ON s.admin_uid = a.uid
     LEFT JOIN (
         SELECT s2.id AS cur_id
         FROM scans s1
         JOIN scans s2 ON s2.uid = s1.uid AND s2.id = (
             SELECT MIN(x.id) FROM scans x WHERE x.uid = s1.uid AND x.id > s1.id
         )
         WHERE s1.direction = s2.direction
     ) ss ON ss.cur_id = s.id ' . $where_clause . '
     ORDER BY s.id DESC
     LIMIT ' . (int)$limit
);

$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}

$stats = [
    'today' => ['total' => 0, 'in' => 0, 'out' => 0],
    'week' => ['total' => 0, 'in' => 0, 'out' => 0, 'avg_per_day' => 0],
    'month' => ['total' => 0, 'in' => 0, 'out' => 0, 'best_day' => 0],
    'active_students_7d' => 0,
    'inside' => ['total' => 0, 'students' => 0, 'faculty' => 0],
    'peak_hours_today' => [],
    'alerts' => [
        'unregistered_cards' => 0,
        'scans_last_10_min' => 0,
        'unique_today' => 0,
        'consecutive_in' => 0,
        'consecutive_out' => 0
    ]
];

function fetchCount($mysqli, $sql) {
    $res = $mysqli->query($sql);
    if (!$res) {
        return 0;
    }
    $row = $res->fetch_assoc();
    $res->free();
    return $row ? (int)$row['c'] : 0;
}

function fetchStatsRow($mysqli, $sql) {
    $res = $mysqli->query($sql);
    if (!$res) {
        return ['total' => 0, 'in' => 0, 'out' => 0];
    }
    $row = $res->fetch_assoc();
    $res->free();
    if (!$row) {
        return ['total' => 0, 'in' => 0, 'out' => 0];
    }
    return [
        'total' => (int)$row['total'],
        'in' => (int)$row['in_count'],
        'out' => (int)$row['out_count']
    ];
}

$stats['today'] = fetchStatsRow(
    $mysqli,
    "SELECT COUNT(*) AS total, SUM(direction = 'IN') AS in_count, SUM(direction = 'OUT') AS out_count
     FROM scans
     WHERE created_at >= '{$dayStartStr}' AND created_at < '{$dayEndStr}'"
);

$stats['week'] = fetchStatsRow(
    $mysqli,
    "SELECT COUNT(*) AS total, SUM(direction = 'IN') AS in_count, SUM(direction = 'OUT') AS out_count
     FROM scans
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
);
if ($stats['week']['total'] > 0) {
    $stats['week']['avg_per_day'] = (int)ceil($stats['week']['total'] / 7);
}

$stats['month'] = fetchStatsRow(
    $mysqli,
    "SELECT COUNT(*) AS total, SUM(direction = 'IN') AS in_count, SUM(direction = 'OUT') AS out_count
     FROM scans
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)"
);

$bestDayRes = $mysqli->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS c
     FROM scans
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY day
     ORDER BY c DESC
     LIMIT 1"
);
if ($bestDayRes) {
    $bestDayRow = $bestDayRes->fetch_assoc();
    $bestDayRes->free();
    if ($bestDayRow) {
        $stats['month']['best_day'] = (int)$bestDayRow['c'];
    }
}

$stats['active_students_7d'] = fetchCount(
    $mysqli,
    "SELECT COUNT(DISTINCT s.uid) AS c
     FROM scans s
     JOIN students st ON s.uid = st.uid
     WHERE s.created_at >= DATE_SUB('{$dayStartStr}', INTERVAL 6 DAY)"
);

$peakRes = $mysqli->query(
    "SELECT HOUR(created_at) AS hour, COUNT(*) AS c
     FROM scans
     WHERE created_at >= '{$dayStartStr}' AND created_at < '{$dayEndStr}'
     GROUP BY hour
     ORDER BY c DESC
     LIMIT 3"
);
if ($peakRes) {
    while ($row = $peakRes->fetch_assoc()) {
        $stats['peak_hours_today'][] = [
            'hour' => (int)$row['hour'],
            'count' => (int)$row['c']
        ];
    }
    $peakRes->free();
}

$stats['alerts']['unregistered_cards'] = fetchCount(
        $mysqli,
        "SELECT COUNT(DISTINCT s.uid) AS c
         FROM scans s
         LEFT JOIN (
             SELECT uid FROM students
             UNION ALL SELECT uid FROM faculty
             UNION ALL SELECT uid FROM staff
             UNION ALL SELECT uid FROM visitors
         ) p ON s.uid = p.uid
         WHERE p.uid IS NULL"
);
$stats['alerts']['scans_last_10_min'] = fetchCount(
    $mysqli,
    "SELECT COUNT(*) AS c FROM scans WHERE created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
);
$stats['alerts']['unique_today'] = fetchCount(
    $mysqli,
    "SELECT COUNT(DISTINCT uid) AS c FROM scans WHERE created_at >= '{$dayStartStr}' AND created_at < '{$dayEndStr}'"
);

$insideRes = $mysqli->query(
        "SELECT
                SUM(s.direction = 'IN') AS total,
                SUM(s.direction = 'IN' AND p.role = 'student') AS students,
                SUM(s.direction = 'IN' AND p.role = 'faculty') AS faculty
         FROM scans s
         JOIN (SELECT uid, MAX(id) AS max_id FROM scans GROUP BY uid) last
             ON s.uid = last.uid AND s.id = last.max_id
         LEFT JOIN (
             SELECT uid, 'student' AS role FROM students
             UNION ALL SELECT uid, 'faculty' AS role FROM faculty
             UNION ALL SELECT uid, 'staff' AS role FROM staff
             UNION ALL SELECT uid, 'visitor' AS role FROM visitors
         ) p ON s.uid = p.uid"
);
if ($insideRes) {
    $insideRow = $insideRes->fetch_assoc();
    $insideRes->free();
    if ($insideRow) {
        $stats['inside']['total'] = (int)$insideRow['total'];
        $stats['inside']['students'] = (int)$insideRow['students'];
        $stats['inside']['faculty'] = (int)$insideRow['faculty'];
    }
}

$consecutiveRes = $mysqli->query(
    "SELECT
        SUM(CASE WHEN s1.direction = 'IN' AND s2.direction = 'IN' THEN 1 ELSE 0 END) AS consecutive_in,
        SUM(CASE WHEN s1.direction = 'OUT' AND s2.direction = 'OUT' THEN 1 ELSE 0 END) AS consecutive_out
     FROM scans s1
     JOIN (SELECT uid, MAX(id) AS max_id FROM scans GROUP BY uid) last
       ON s1.uid = last.uid AND s1.id = last.max_id
     LEFT JOIN scans s2
       ON s2.uid = s1.uid
      AND s2.id = (SELECT MAX(id) FROM scans s3 WHERE s3.uid = s1.uid AND s3.id < s1.id)
     WHERE s1.created_at >= '{$dayStartStr}'"
);
if ($consecutiveRes) {
    $consecutiveRow = $consecutiveRes->fetch_assoc();
    $consecutiveRes->free();
    if ($consecutiveRow) {
        $stats['alerts']['consecutive_in'] = (int)$consecutiveRow['consecutive_in'];
        $stats['alerts']['consecutive_out'] = (int)$consecutiveRow['consecutive_out'];
    }
}

echo json_encode(['ok' => true, 'data' => $rows, 'stats' => $stats]);
