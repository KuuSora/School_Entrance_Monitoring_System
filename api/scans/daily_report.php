<?php
require_once __DIR__ . '/../system/db.php';

session_start();
if (!isset($_SESSION['admin_uid'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

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

function format_datetime_for_report(string $value): string {
    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d h:i A', $timestamp) : $value;
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$dateValue = isset($_GET['date']) ? trim($_GET['date']) : '';
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'print';
if (!in_array($format, ['print', 'csv', 'xls'], true)) {
    $format = 'print';
}
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

$downloadNameBase = 'daily_scan_log_' . $dayStart->format('Y-m-d');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $downloadNameBase . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Report Date', $reportLabel]);
    fputcsv($output, ['Total Scans', $summary['total']]);
    fputcsv($output, ['IN', $summary['in']]);
    fputcsv($output, ['OUT', $summary['out']]);
    fputcsv($output, ['Suspicious', $summary['suspicious']]);
    fputcsv($output, ['Unregistered', $summary['unregistered']]);
    fputcsv($output, []);
    fputcsv($output, ['ID', 'Name', 'Role', 'Direction', 'UID', 'Admin', 'Time', 'Suspicious']);
    foreach ($rows as $row) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['role'],
            $row['direction'],
            $row['uid'],
            $row['admin_name'] !== '' ? $row['admin_name'] : ($row['admin_uid'] ?: ''),
            $row['created_at'],
            (int) $row['suspicious'] === 1 ? 'Yes' : 'No'
        ]);
    }
    fclose($output);
    exit;
}

if ($format === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $downloadNameBase . '.xls"');
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1"><tr><th colspan="8">Daily Scan Log - ' . h($reportLabel) . '</th></tr>';
    echo '<tr><td><b>Total</b></td><td>' . (int) $summary['total'] . '</td><td><b>IN</b></td><td>' . (int) $summary['in'] . '</td><td><b>OUT</b></td><td>' . (int) $summary['out'] . '</td><td><b>Suspicious</b></td><td>' . (int) $summary['suspicious'] . '</td></tr>';
    echo '<tr><td colspan="8"><b>Detailed Records</b></td></tr>';
    echo '<tr><th>ID</th><th>Name</th><th>Role</th><th>Direction</th><th>UID</th><th>Admin</th><th>Time</th><th>Suspicious</th></tr>';
    foreach ($rows as $row) {
        $adminDisplay = $row['admin_name'] !== '' ? $row['admin_name'] : ($row['admin_uid'] ?: '');
        echo '<tr>';
        echo '<td>' . h((string) $row['id']) . '</td>';
        echo '<td>' . h((string) $row['name']) . '</td>';
        echo '<td>' . h((string) $row['role']) . '</td>';
        echo '<td>' . h((string) $row['direction']) . '</td>';
        echo '<td>' . h((string) $row['uid']) . '</td>';
        echo '<td>' . h((string) $adminDisplay) . '</td>';
        echo '<td>' . h(format_datetime_for_report((string) $row['created_at'])) . '</td>';
        echo '<td>' . ((int) $row['suspicious'] === 1 ? 'Yes' : 'No') . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daily Scan Log - <?php echo h($reportLabel); ?></title>
  <style>
    :root {
      color-scheme: dark;
      --bg: #242547;
      --card: #2c2d57;
      --ink: #f2f2fb;
      --muted: #b495a4;
      --stroke: #3b3d77;
      --accent: #882eca;
      --success: #61d29a;
      --danger: #ff8fa0;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background: var(--bg);
      color: var(--ink);
      padding: 24px;
    }
    .report {
      max-width: 1200px;
      margin: 0 auto;
    }
    .toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid var(--stroke);
      border-radius: 10px;
      padding: 10px 14px;
      color: var(--ink);
      background: #2c2d57;
      cursor: pointer;
      text-decoration: none;
      font-weight: 600;
    }
    .btn.primary {
      background: var(--accent);
      border-color: transparent;
    }
    .meta-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
      margin-bottom: 16px;
    }
    .meta-card, .table-card {
      background: var(--card);
      border: 1px solid var(--stroke);
      border-radius: 16px;
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
    }
    .meta-card {
      padding: 14px 16px;
    }
    .meta-label {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      margin-bottom: 6px;
    }
    .meta-value {
      font-size: 22px;
      font-weight: 700;
    }
    .table-card { overflow: hidden; }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 12px 14px;
      border-bottom: 1px solid var(--stroke);
      text-align: left;
      vertical-align: top;
    }
    th {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      background: rgba(255, 255, 255, 0.03);
      position: sticky;
      top: 0;
    }
    tr:hover td { background: rgba(136, 46, 202, 0.08); }
    .chip {
      display: inline-flex;
      align-items: center;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      background: rgba(97, 210, 154, 0.18);
      color: var(--success);
    }
    .chip.out {
      background: rgba(255, 143, 160, 0.14);
      color: var(--danger);
    }
    @media print {
      body { background: #fff; color: #000; padding: 0; }
      .toolbar { display: none; }
      .meta-card, .table-card { box-shadow: none; }
      .meta-card, .table-card { border-color: #ddd; }
      th { position: static; }
    }
  </style>
</head>
<body>
  <div class="report">
    <div class="toolbar">
      <div>
        <h1 style="margin:0; font-size:28px;">Daily Scan Log</h1>
        <div style="color:var(--muted); margin-top:6px;">Date: <?php echo h($reportLabel); ?> | Reset hour: <?php echo (int) $resetHour; ?>:00</div>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn primary" onclick="window.print()">Print</button>
        <a class="btn" href="?date=<?php echo urlencode($reportLabel); ?>&format=csv<?php echo $adminUidFilter !== '' ? '&admin_uid=' . urlencode($adminUidFilter) : ''; ?><?php echo $suspiciousOnly ? '&suspicious=1' : ''; ?>">Download CSV</a>
        <a class="btn" href="?date=<?php echo urlencode($reportLabel); ?>&format=xls<?php echo $adminUidFilter !== '' ? '&admin_uid=' . urlencode($adminUidFilter) : ''; ?><?php echo $suspiciousOnly ? '&suspicious=1' : ''; ?>">Download Excel</a>
      </div>
    </div>

    <div class="meta-grid">
      <div class="meta-card"><div class="meta-label">Total Scans</div><div class="meta-value"><?php echo (int) $summary['total']; ?></div></div>
      <div class="meta-card"><div class="meta-label">IN</div><div class="meta-value" style="color:var(--success);"><?php echo (int) $summary['in']; ?></div></div>
      <div class="meta-card"><div class="meta-label">OUT</div><div class="meta-value" style="color:var(--danger);"><?php echo (int) $summary['out']; ?></div></div>
      <div class="meta-card"><div class="meta-label">Suspicious</div><div class="meta-value" style="color:#ffbf5f;"><?php echo (int) $summary['suspicious']; ?></div></div>
      <div class="meta-card"><div class="meta-label">Unregistered</div><div class="meta-value"><?php echo (int) $summary['unregistered']; ?></div></div>
    </div>

    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Role</th>
            <th>Direction</th>
            <th>UID</th>
            <th>Admin</th>
            <th>Time</th>
            <th>Suspicious</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($rows) === 0): ?>
            <tr><td colspan="8" style="padding:16px; color:var(--muted);">No scan entries for this day.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <?php $adminDisplay = $row['admin_name'] !== '' ? $row['admin_name'] : ($row['admin_uid'] ?: ''); ?>
              <tr>
                <td><?php echo h((string) $row['id']); ?></td>
                <td><?php echo h((string) $row['name']); ?></td>
                <td><?php echo h((string) $row['role']); ?></td>
                <td><span class="chip <?php echo strtoupper((string) $row['direction']) === 'OUT' ? 'out' : ''; ?>"><?php echo h((string) $row['direction']); ?></span></td>
                <td><?php echo h((string) $row['uid']); ?></td>
                <td><?php echo h((string) $adminDisplay); ?></td>
                <td><?php echo h(format_datetime_for_report((string) $row['created_at'])); ?></td>
                <td><?php echo (int) $row['suspicious'] === 1 ? 'Yes' : 'No'; ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
