<?php
require_once __DIR__ . '/../system/db.php';
require_once __DIR__ . '/../system/auth.php';
require_admin_session();

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

function grouped_report_rows(array $rows): array {
    $grouped = [];
    foreach ($rows as $row) {
        $uid = (string) ($row['uid'] ?? '');
        $key = $uid !== '' ? $uid : 'row-' . (string) ($row['id'] ?? count($grouped));
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'name' => (string) ($row['name'] ?? 'New User'),
                'role' => (string) ($row['role'] ?? 'unknown'),
                'department' => (string) ($row['department'] ?? ''),
                'uid' => $uid,
                'in_times' => [],
                'out_times' => [],
                'events' => [],
                'in_count' => 0,
                'out_count' => 0,
                'suspicious' => false,
                'admins' => [],
            ];
        }

        $direction = strtoupper((string) ($row['direction'] ?? ''));
        $createdAt = (string) ($row['created_at'] ?? '');
        $time = format_datetime_for_report($createdAt);
        $grouped[$key]['events'][] = [
            'direction' => $direction,
            'time' => $time,
            'timestamp' => strtotime($createdAt) ?: null,
        ];
        if ($direction === 'IN') {
            $grouped[$key]['in_times'][] = $time;
            $grouped[$key]['in_count']++;
        } elseif ($direction === 'OUT') {
            $grouped[$key]['out_times'][] = $time;
            $grouped[$key]['out_count']++;
        }
        if ((int) ($row['suspicious'] ?? 0) === 1) {
            $grouped[$key]['suspicious'] = true;
        }

        $adminDisplay = (string) ($row['admin_name'] ?? '') !== ''
            ? (string) $row['admin_name']
            : (string) ($row['admin_uid'] ?? '');
        if ($adminDisplay !== '' && !in_array($adminDisplay, $grouped[$key]['admins'], true)) {
            $grouped[$key]['admins'][] = $adminDisplay;
        }
    }

    foreach ($grouped as &$person) {
        $openIn = null;
        $sessions = [];
        $totalSeconds = 0;
        foreach ($person['events'] as $event) {
            if ($event['direction'] === 'IN') {
                if ($openIn !== null) {
                    $sessions[] = $openIn['time'] . ' - No OUT recorded';
                }
                $openIn = $event;
            } elseif ($event['direction'] === 'OUT') {
                if ($openIn !== null) {
                    $duration = '';
                    if ($openIn['timestamp'] !== null && $event['timestamp'] !== null && $event['timestamp'] >= $openIn['timestamp']) {
                        $seconds = $event['timestamp'] - $openIn['timestamp'];
                        $totalSeconds += $seconds;
                        $duration = sprintf(' (%dh %02dm)', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
                    }
                    $sessions[] = $openIn['time'] . ' - ' . $event['time'] . $duration;
                    $openIn = null;
                } else {
                    $sessions[] = 'No IN recorded - ' . $event['time'];
                }
            }
        }
        if ($openIn !== null) {
            $sessions[] = $openIn['time'] . ' - No OUT recorded';
        }
        $person['first_in'] = $person['in_times'] !== [] ? $person['in_times'][0] : '';
        $person['last_out'] = $person['out_times'] !== [] ? $person['out_times'][count($person['out_times']) - 1] : '';
        $person['sessions'] = implode("\n", $sessions);
        $person['total_duration'] = sprintf('%dh %02dm', intdiv($totalSeconds, 3600), intdiv($totalSeconds % 3600, 60));
        $person['scan_count'] = $person['in_count'] + $person['out_count'];
        $person['in_times'] = implode("\n", $person['in_times']);
        $person['out_times'] = implode("\n", $person['out_times']);
        $person['admins'] = implode(', ', $person['admins']);
        $person['suspicious'] = $person['suspicious'] ? 'Yes' : 'No';
        unset($person['events']);
    }
    unset($person);
    return array_values($grouped);
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
               COALESCE(p.department, '') AS department,
               COALESCE(a.name, '') AS admin_name,
               CASE WHEN ss.cur_id IS NULL THEN 0 ELSE 1 END AS suspicious
        FROM scans s
        LEFT JOIN (
            SELECT uid, name, 'student' AS role, NULL AS department FROM students
            UNION ALL SELECT uid, name, 'faculty' AS role, department FROM faculty
            UNION ALL SELECT uid, name, 'staff' AS role, department FROM staff
            UNION ALL SELECT uid, name, 'visitor' AS role, NULL AS department FROM visitors
            UNION ALL SELECT uid, name, 'admin' AS role, NULL AS department FROM admins
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
$groupedRows = grouped_report_rows($rows);

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
    fputcsv($output, ['Name', 'Role', 'Department', 'UID', 'First IN', 'Last OUT', 'Sessions (IN - OUT)', 'Total Time Inside', 'IN Count', 'OUT Count', 'Total Scans', 'Suspicious', 'Admin(s)']);
    foreach ($groupedRows as $row) {
        fputcsv($output, [
            $row['name'],
            $row['role'],
            $row['department'],
            $row['uid'],
            $row['first_in'],
            $row['last_out'],
            $row['sessions'],
            $row['total_duration'],
            $row['in_count'],
            $row['out_count'],
            $row['scan_count'],
            $row['suspicious'],
            $row['admins']
        ]);
    }
    fclose($output);
    exit;
}

if ($format === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $downloadNameBase . '.xls"');
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<style>td, th { vertical-align: top; } .times { white-space: pre-line; mso-data-placement: same-cell; }</style>';
    echo '<table border="1"><tr><th colspan="14">Daily Attendance Summary - ' . h($reportLabel) . '</th></tr>';
    echo '<tr><td><b>Total scans</b></td><td>' . (int) $summary['total'] . '</td><td><b>Overall IN</b></td><td>' . (int) $summary['in'] . '</td><td><b>Overall OUT</b></td><td>' . (int) $summary['out'] . '</td><td><b>People</b></td><td>' . count($groupedRows) . '</td><td><b>Suspicious</b></td><td>' . (int) $summary['suspicious'] . '</td><td><b>Unregistered</b></td><td>' . (int) $summary['unregistered'] . '</td><td><b>Reset hour</b></td><td>' . (int) $resetHour . ':00</td></tr>';
    echo '<tr><td colspan="14"><b>One row per person. Sessions are paired in chronological order; unmatched scans are identified.</b></td></tr>';
    echo '<tr><th>Name</th><th>Role</th><th>Department</th><th>UID</th><th>First IN</th><th>Last OUT</th><th>Sessions (IN - OUT)</th><th>Total Time Inside</th><th>IN Count</th><th>OUT Count</th><th>Total Scans</th><th>Suspicious</th><th>Admin(s)</th></tr>';
    foreach ($groupedRows as $row) {
        echo '<tr>';
        echo '<td>' . h((string) $row['name']) . '</td>';
        echo '<td>' . h((string) $row['role']) . '</td>';
        echo '<td>' . h((string) $row['department']) . '</td>';
        echo '<td>' . h((string) $row['uid']) . '</td>';
        echo '<td>' . h((string) $row['first_in']) . '</td>';
        echo '<td>' . h((string) $row['last_out']) . '</td>';
        echo '<td class="times">' . h((string) $row['sessions']) . '</td>';
        echo '<td>' . h((string) $row['total_duration']) . '</td>';
        echo '<td>' . (int) $row['in_count'] . '</td>';
        echo '<td>' . (int) $row['out_count'] . '</td>';
        echo '<td>' . (int) $row['scan_count'] . '</td>';
        echo '<td>' . h((string) $row['suspicious']) . '</td>';
        echo '<td>' . h((string) $row['admins']) . '</td>';
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
