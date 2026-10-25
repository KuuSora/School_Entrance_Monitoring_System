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

function report_summary(array $rows): array {
    $summary = ['total' => count($rows), 'in' => 0, 'out' => 0, 'suspicious' => 0, 'unregistered' => 0];
    foreach ($rows as $row) {
        if (strtoupper((string) ($row['direction'] ?? '')) === 'IN') {
            $summary['in']++;
        } elseif (strtoupper((string) ($row['direction'] ?? '')) === 'OUT') {
            $summary['out']++;
        }
        if ((int) ($row['suspicious'] ?? 0) === 1) {
            $summary['suspicious']++;
        }
        if ((string) ($row['name'] ?? '') === 'New User') {
            $summary['unregistered']++;
        }
    }
    return $summary;
}

function xml_value(string $value): string {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$dateValue = isset($_GET['date']) ? trim($_GET['date']) : '';
$dateFromValue = isset($_GET['date_from']) ? trim($_GET['date_from']) : $dateValue;
$dateToValue = isset($_GET['date_to']) ? trim($_GET['date_to']) : $dateFromValue;
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'print';
$isPreview = isset($_GET['preview']) && (int) $_GET['preview'] === 1;
if (!in_array($format, ['print', 'csv', 'xls'], true)) {
    $format = 'print';
}
$adminUidFilter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : '';
$departmentFilter = isset($_GET['department']) ? trim($_GET['department']) : '';
$roleFilter = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';
$personNameFilter = isset($_GET['person_name']) ? trim($_GET['person_name']) : '';
$sort = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : 'department';
$suspiciousOnly = isset($_GET['suspicious']) && (int) $_GET['suspicious'] === 1;

$resetHour = get_reset_hour($mysqli);
$dateFrom = DateTime::createFromFormat('!Y-m-d', $dateFromValue ?: date('Y-m-d'));
$dateTo = DateTime::createFromFormat('!Y-m-d', $dateToValue ?: $dateFromValue);
if (!$dateFrom || !$dateTo) {
    http_response_code(400);
    exit('Invalid report date');
}
if ($dateTo < $dateFrom) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}
[$dayStart] = resolve_daily_range($dateFrom->format('Y-m-d'), $resetHour);
[, $rangeEnd] = resolve_daily_range($dateTo->format('Y-m-d'), $resetHour);
$dayStartStr = $dayStart->format('Y-m-d H:i:s');
$dayEndStr = $rangeEnd->format('Y-m-d H:i:s');
$reportLabel = $dateFrom->format('Y-m-d') === $dateTo->format('Y-m-d')
    ? $dateFrom->format('Y-m-d')
    : $dateFrom->format('Y-m-d') . ' to ' . $dateTo->format('Y-m-d');

$whereParts = ["s.created_at >= '{$dayStartStr}'", "s.created_at < '{$dayEndStr}'"];
if ($adminUidFilter !== '') {
    $whereParts[] = "s.admin_uid = '" . $mysqli->real_escape_string($adminUidFilter) . "'";
}
if ($departmentFilter !== '') {
    $whereParts[] = "COALESCE(p.department, '') LIKE '%" . $mysqli->real_escape_string($departmentFilter) . "%'";
}
if (in_array($roleFilter, ['student', 'faculty', 'staff', 'visitor', 'admin'], true)) {
    $whereParts[] = "p.role = '" . $mysqli->real_escape_string($roleFilter) . "'";
}
if ($personNameFilter !== '') {
    $whereParts[] = "COALESCE(p.name, 'New User') LIKE '%" . $mysqli->real_escape_string($personNameFilter) . "%'";
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
        {$whereClause}";
$orderBy = [
    'department' => "COALESCE(p.department, ''), p.role, COALESCE(p.name, 'New User'), s.id",
    'role' => "p.role, COALESCE(p.department, ''), COALESCE(p.name, 'New User'), s.id",
    'name' => "COALESCE(p.name, 'New User'), s.id",
    'time' => 's.id',
][$sort] ?? "COALESCE(p.department, ''), p.role, COALESCE(p.name, 'New User'), s.id";
$sql .= " ORDER BY {$orderBy}";

$result = $mysqli->query($sql);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}

$summary = report_summary($rows);
$downloadNameBase = 'daily_scan_log_' . $dateFrom->format('Y-m-d') . '_to_' . $dateTo->format('Y-m-d');
$groupedRows = grouped_report_rows($rows);

$rowsByDay = [];
$cursor = clone $dateFrom;
while ($cursor <= $dateTo) {
    $rowsByDay[$cursor->format('Y-m-d')] = [];
    $cursor->modify('+1 day');
}
foreach ($rows as $row) {
    $timestamp = strtotime((string) ($row['created_at'] ?? ''));
    if (!$timestamp) {
        continue;
    }
    $reportDay = new DateTime('@' . $timestamp);
    $reportDay->setTimezone(new DateTimeZone(date_default_timezone_get()));
    if ($resetHour > 0 && (int) $reportDay->format('H') < $resetHour) {
        $reportDay->modify('-1 day');
    }
    $dayKey = $reportDay->format('Y-m-d');
    if (isset($rowsByDay[$dayKey])) {
        $rowsByDay[$dayKey][] = $row;
    }
}

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
    echo '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?>';
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
    echo '<Styles>';
    echo '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14"/><Alignment ss:Horizontal="Center"/></Style>';
    echo '<Style ss:ID="Header"><Font ss:Bold="1"/><Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#7F8C8D"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#7F8C8D"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#7F8C8D"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#7F8C8D"/></Borders><Alignment ss:Vertical="Top" ss:WrapText="1"/></Style>';
    echo '<Style ss:ID="Cell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/></Borders><Alignment ss:Vertical="Top"/></Style>';
    echo '<Style ss:ID="Wrap"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/></Borders><Alignment ss:Vertical="Top" ss:WrapText="1"/></Style>';
    echo '<Style ss:ID="SummaryLabel"><Font ss:Bold="1"/><Interior ss:Color="#F2F6F8" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/></Borders></Style>';
    echo '<Style ss:ID="SummaryValue"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7C3CC"/></Borders></Style>';
    echo '</Styles>';
    foreach ($rowsByDay as $dayKey => $dayRows) {
        $daySummary = report_summary($dayRows);
        $dayGroupedRows = grouped_report_rows($dayRows);
        echo '<Worksheet ss:Name="' . xml_value(substr($dayKey, 0, 31)) . '"><Table ss:ExpandedColumnCount="14" ss:DefaultRowHeight="18">';
        foreach ([150, 70, 120, 105, 130, 130, 260, 110, 70, 70, 80, 85, 150, 90] as $width) {
            echo '<Column ss:Width="' . $width . '"/>';
        }
        echo '<Row ss:AutoFitHeight="0" ss:Height="24"><Cell ss:MergeAcross="12" ss:StyleID="Title"><Data ss:Type="String">Daily Attendance Summary - ' . xml_value($dayKey) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Total scans</Data></Cell><Cell ss:StyleID="SummaryValue"><Data ss:Type="Number">' . $daySummary['total'] . '</Data></Cell><Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Overall IN</Data></Cell><Cell ss:StyleID="SummaryValue"><Data ss:Type="Number">' . $daySummary['in'] . '</Data></Cell><Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Overall OUT</Data></Cell><Cell ss:StyleID="SummaryValue"><Data ss:Type="Number">' . $daySummary['out'] . '</Data></Cell><Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">People</Data></Cell><Cell ss:StyleID="SummaryValue"><Data ss:Type="Number">' . count($dayGroupedRows) . '</Data></Cell><Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Suspicious</Data></Cell><Cell ss:StyleID="SummaryValue"><Data ss:Type="Number">' . $daySummary['suspicious'] . '</Data></Cell><Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Unregistered</Data></Cell><Cell ss:StyleID="SummaryValue"><Data ss:Type="Number">' . $daySummary['unregistered'] . '</Data></Cell><Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Reset hour</Data></Cell><Cell ss:StyleID="SummaryValue"><Data ss:Type="String">' . (int) $resetHour . ':00</Data></Cell></Row>';
        echo '<Row><Cell ss:MergeAcross="12" ss:StyleID="Wrap"><Data ss:Type="String">One row per person. Sessions are paired in chronological order; unmatched scans are identified.</Data></Cell></Row>';
        echo '<Row ss:StyleID="Header">';
        foreach (['Name', 'Role', 'Department', 'UID', 'First IN', 'Last OUT', 'Sessions (IN - OUT)', 'Total Time Inside', 'IN Count', 'OUT Count', 'Total Scans', 'Suspicious', 'Admin(s)'] as $heading) {
            echo '<Cell><Data ss:Type="String">' . xml_value($heading) . '</Data></Cell>';
        }
        echo '</Row>';
        foreach ($dayGroupedRows as $row) {
            echo '<Row>';
            foreach ([(string) $row['name'], (string) $row['role'], (string) $row['department'], (string) $row['uid'], (string) $row['first_in'], (string) $row['last_out'], (string) $row['sessions'], (string) $row['total_duration']] as $value) {
                echo '<Cell ss:StyleID="Wrap"><Data ss:Type="String">' . xml_value($value) . '</Data></Cell>';
            }
            foreach ([(int) $row['in_count'], (int) $row['out_count'], (int) $row['scan_count']] as $value) {
                echo '<Cell ss:StyleID="Cell"><Data ss:Type="Number">' . $value . '</Data></Cell>';
            }
            echo '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . xml_value((string) $row['suspicious']) . '</Data></Cell><Cell ss:StyleID="Wrap"><Data ss:Type="String">' . xml_value((string) $row['admins']) . '</Data></Cell></Row>';
        }
        echo '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>4</SplitHorizontal><TopRowBottomPane>4</TopRowBottomPane></WorksheetOptions></Worksheet>';
    }
    echo '</Workbook>';
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
      color-scheme: light;
      --bg: #f8fbff;
      --card: #ffffff;
      --ink: #0f172a;
      --muted: #64748b;
      --stroke: #dbe5f0;
      --accent: #1d4ed8;
      --success: #059669;
      --danger: #dc2626;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background: var(--bg);
      color: var(--ink);
      padding: 18px;
    }
    .report {
      max-width: 1280px;
      margin: 0 auto;
    }
    .report-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }
    .report-title {
      margin: 0;
      font-size: 24px;
      line-height: 1.2;
    }
    .report-subtitle {
      margin-top: 5px;
      color: var(--muted);
      font-size: 12px;
    }
    .report-actions {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--stroke);
      border-radius: 7px;
      padding: 7px 10px;
      color: var(--ink);
      background: #ffffff;
      cursor: pointer;
      text-decoration: none;
      font-size: 11px;
      font-weight: 600;
    }
    .btn.primary {
      background: var(--accent);
      border-color: transparent;
    }
    .meta-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(120px, 1fr));
      gap: 8px;
      margin-bottom: 12px;
    }
    .meta-card, .table-card {
      background: var(--card);
      border: 1px solid var(--stroke);
      border-radius: 10px;
      box-shadow: 0 5px 14px rgba(15, 23, 42, 0.07);
    }
    .meta-card {
      padding: 10px 12px;
    }
    .meta-label {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      margin-bottom: 3px;
    }
    .meta-value {
      font-size: 20px;
      font-weight: 700;
    }
    .table-card { overflow: auto; }
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 760px;
    }
    th, td {
      padding: 8px 10px;
      border-bottom: 1px solid var(--stroke);
      text-align: left;
      vertical-align: top;
      font-size: 12px;
    }
    th {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      background: #f8fbff;
      position: sticky;
      top: 0;
      white-space: nowrap;
    }
    tr:hover td { background: #eff6ff; }
    .chip {
      display: inline-flex;
      align-items: center;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      background: #d1fae5;
      color: var(--success);
    }
    .chip.out {
      background: #fee2e2;
      color: var(--danger);
    }
    .report-empty {
      padding: 24px !important;
      color: var(--muted);
      text-align: center;
    }
    @media (max-width: 760px) {
      .meta-grid { grid-template-columns: repeat(2, minmax(120px, 1fr)); }
      .report-title { font-size: 21px; }
    }
    @media print {
      body { background: #fff; color: #000; padding: 0; }
      .report-header .report-actions { display: none; }
      .meta-card, .table-card { box-shadow: none; }
      .meta-card, .table-card { border-color: #ddd; }
      th { position: static; }
      .report-subtitle, .meta-label { color: #555; }
      .table-card { overflow: visible; }
      table { min-width: 0; }
      .chip { border: 1px solid #aaa; color: #000 !important; background: #fff !important; }
    }
  </style>
</head>
<body>
  <div class="report">
    <div class="report-header">
      <div>
        <h1 class="report-title">Daily Scan Log</h1>
        <div class="report-subtitle">Date: <?php echo h($reportLabel); ?> &middot; Reset hour: <?php echo (int) $resetHour; ?>:00</div>
      </div>
      <?php if (!$isPreview): ?>
      <div class="report-actions">
        <button class="btn primary" onclick="window.print()">Print</button>
        <a class="btn" href="?date_from=<?php echo urlencode($dateFrom->format('Y-m-d')); ?>&date_to=<?php echo urlencode($dateTo->format('Y-m-d')); ?>&format=csv<?php echo $adminUidFilter !== '' ? '&admin_uid=' . urlencode($adminUidFilter) : ''; ?><?php echo $suspiciousOnly ? '&suspicious=1' : ''; ?>">Download CSV</a>
        <a class="btn" href="?date_from=<?php echo urlencode($dateFrom->format('Y-m-d')); ?>&date_to=<?php echo urlencode($dateTo->format('Y-m-d')); ?>&format=xls<?php echo $adminUidFilter !== '' ? '&admin_uid=' . urlencode($adminUidFilter) : ''; ?><?php echo $suspiciousOnly ? '&suspicious=1' : ''; ?>">Download Excel</a>
      </div>
      <?php endif; ?>
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
            <tr><td colspan="8" class="report-empty">No scan entries match the selected filters.</td></tr>
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
