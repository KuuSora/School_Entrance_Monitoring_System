<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

function get_reset_hour(mysqli $mysqli): int {
    $resetHour = 0;
    $resSetting = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'daily_reset_hour' LIMIT 1");
    if ($resSetting) {
        $settingRow = $resSetting->fetch_assoc();
        $resSetting->free();
        if ($settingRow && is_numeric($settingRow['setting_value'])) {
            $resetHour = (int) $settingRow['setting_value'];
        }
    }
    return $resetHour;
}

function resolve_daily_range(?string $dateValue, int $resetHour): array {
    $dateValue = is_string($dateValue) ? trim($dateValue) : '';
    $baseDate = $dateValue !== '' ? DateTime::createFromFormat('Y-m-d', $dateValue) : new DateTime('now');
    if (!$baseDate) {
        $baseDate = new DateTime('now');
    }

    $dayStart = clone $baseDate;
    $dayStart->setTime($resetHour, 0, 0);
    $dayEnd = clone $dayStart;
    $dayEnd->modify('+1 day');

    return [$dayStart, $dayEnd];
}

$dateValue = isset($_GET['date']) ? trim($_GET['date']) : '';
$adminUidFilter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : '';
$suspiciousOnly = isset($_GET['suspicious']) && (int) $_GET['suspicious'] === 1;

$resetHour = get_reset_hour($mysqli);
[$dayStart, $dayEnd] = resolve_daily_range($dateValue, $resetHour);
$dayStartStr = $dayStart->format('Y-m-d H:i:s');
$dayEndStr = $dayEnd->format('Y-m-d H:i:s');
$reportLabel = $dayStart->format('Y-m-d');

$whereParts = ["s.created_at >= '{$dayStartStr}'", "s.created_at < '{$dayEndStr}'"];
if ($adminUidFilter !== '') {
    $whereParts[] = "s.admin_uid = '" . $mysqli->real_escape_string($adminUidFilter) . "'";
}
if ($suspiciousOnly) {
    $whereParts[] = 'ss.cur_id IS NOT NULL';
}
$whereClause = 'WHERE ' . implode(' AND ', $whereParts);

$sql = "SELECT s.id, s.uid, s.direction, s.created_at, s.admin_uid,
               COALESCE(p.name, 'New User') AS name,
               COALESCE(p.role, 'unknown') AS role,
               COALESCE(a.name, '') AS admin_name,
               CASE WHEN ss.cur_id IS NULL THEN 0 ELSE 1 END AS suspicious
        FROM scans s
        LEFT JOIN (
            SELECT uid, name, 'student' AS role FROM students
            UNION ALL SELECT uid, name, 'faculty' AS role FROM faculty
            UNION ALL SELECT uid, name, 'staff' AS role FROM staff
            UNION ALL SELECT uid, name, 'visitor' AS role FROM visitors
            UNION ALL SELECT uid, name, 'admin' AS role FROM admins
        ) p ON s.uid = p.uid
        LEFT JOIN admins a ON s.admin_uid = a.uid
        LEFT JOIN (
            SELECT s2.id AS cur_id
            FROM scans s1
            JOIN scans s2 ON s2.uid = s1.uid AND s2.id = (
                SELECT MIN(x.id) FROM scans x WHERE x.uid = s1.uid AND x.id > s1.id
            )
            WHERE s1.direction = s2.direction
        ) ss ON ss.cur_id = s.id
        {$whereClause}
        ORDER BY s.id ASC";

$result = $mysqli->query($sql);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}

$summary = [
    'total' => count($rows),
    'in' => 0,
    'out' => 0,
    'suspicious' => 0,
    'unregistered' => 0,
];
foreach ($rows as $row) {
    if (strtoupper((string) $row['direction']) === 'IN') {
        $summary['in']++;
    } else if (strtoupper((string) $row['direction']) === 'OUT') {
        $summary['out']++;
    }
    if ((int) $row['suspicious'] === 1) {
        $summary['suspicious']++;
    }
    if ((string) $row['name'] === 'New User') {
        $summary['unregistered']++;
    }
}

echo json_encode([
    'ok' => true,
    'report_date' => $reportLabel,
    'reset_hour' => $resetHour,
    'summary' => $summary,
    'logs' => $rows
]);
