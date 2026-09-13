<?php
session_start();
if (!isset($_SESSION['admin_uid'])) {
  header('Location: login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RFID Dashboard</title>
  <link rel="stylesheet" href="style.css?v=5">
  <style>
    :root {
      --bg-color: #f8fbff;
      --bg-gradient: linear-gradient(135deg, #f8fbff 0%, #1d4ed8 100%));
      --card-bg: #ffffff;
      --text-color: #0f172a;
      --muted-color: #64748b;
      --border-color: #cbd5e1;
      --accent-color: #1d4ed8;
      --accent-color-light: #dbeafe;
      --shadow-color: rgba(56, 189, 248, 0.35);
      --danger-color: #dc2626;
      --danger-bg: #fef2f2;
      --sidebar-bg: #e0f2fe;
      --sidebar-accent: #1d4ed8;
      --sidebar-ink: #0f172a;
      --sidebar-hover: rgba(29, 78, 216, 0.08);
      --sidebar-active-bg: #dbeafe;
      --sidebar-icon-bg: #eff6ff;
      --yellow-accent: #f59e0b;
    }

    .dark-mode {
      --bg-color: #242547;
      --bg-gradient: #242547;
      --card-bg: #2c2d57;
      --text-color: #f2f2fb;
      --muted-color: #b495a4;
      --border-color: #3b3d77;
      --accent-color: #882eca;
      --accent-color-light: rgba(136, 46, 202, 0.16);
      --shadow-color: rgba(0, 0, 0, 0.34);
      --danger-color: #ff8fa0;
      --danger-bg: rgba(255, 143, 160, 0.14);
      --sidebar-bg: #242547;
      --sidebar-accent: #61d29a;
      --sidebar-ink: #f2f2fb;
      --sidebar-hover: rgba(136, 46, 202, 0.12);
      --sidebar-active-bg: rgba(97, 210, 154, 0.18);
      --sidebar-icon-bg: #36136e;
    }

    body {
      background: var(--bg-gradient);
      background-color: var(--bg-color);
      color: var(--text-color);
      transition: background-color 0.3s, color 0.3s;
    }

    .topbar {
      background: linear-gradient(132deg, #dbfef3 0%,#1d4ed8 100%);
      border-bottom: 1px solid var(--border-color);
      box-shadow: 0 2px 8px var(--shadow-color);
    }

    .sidebar {
      background-color: var(--sidebar-bg);
      border-right: 1px solid var(--border-color);
    }

    .sidebar .tab-btn {
      color: var(--sidebar-ink);
      opacity: 0.92;
    }

    .sidebar .tab-btn:hover {
      background: var(--sidebar-hover);
      opacity: 1;
    }

    .sidebar .tab-btn.active {
      background: var(--sidebar-active-bg);
      color: var(--sidebar-accent);
      opacity: 1;
      border-right: none;
      box-shadow: inset 3px 0 0 var(--sidebar-accent);
    }

    .sidebar .tab-icon {
      background: var(--sidebar-icon-bg);
      color: var(--sidebar-accent);
      border: 1.5px solid transparent;
    }

    .sidebar .tab-btn:hover .tab-icon {
      background: var(--sidebar-active-bg);
      color: var(--sidebar-accent);
      border-color: var(--sidebar-accent);
    }

    .sidebar .tab-btn.active .tab-icon {
      background: var(--sidebar-accent);
      color: #ffffff;
      border-color: transparent;
    }

    .sidebar .tab-icon svg path {
      fill: currentColor;
    }

    .sidebar .tab-btn:hover .tab-icon svg path {
      fill: var(--sidebar-accent);
    }

    .sidebar .tab-btn.active .tab-icon svg path {
      fill: #ffffff;
    }

    .sidebar .brand-dot {
      background: var(--sidebar-accent);
      color: #ffffff;
    }

    .sidebar .nav-brand {
      background: #f8fbfc;
    }

    .card, .panel-fixed {
      background-color: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      box-shadow: 0 4px 12px var(--shadow-color);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 24px rgba(56, 189, 248, 0.4);
    }

    .card .value {
      color: var(--sidebar-accent);
      font-size: 2.5rem;
      font-weight: 700;
    }

    .alert-card .value {
      color: var(--danger-color);
    }

    .btn {
      border-radius: 8px;
    }

    .panel-fixed {
      box-shadow: none;
    }

    .theme-switcher {
      cursor: pointer;
      padding: 8px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      transition: background-color 0.2s;
    }
    .theme-switcher:hover {
      background-color: var(--accent-color-light);
    }

    .id-card-display {
      position: fixed;
      top: 80px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 1000;
      perspective: 1000px;
      transition: opacity 0.5s ease, transform 0.5s ease;
      opacity: 0;
      transform: translateX(-50%) translateY(-20px) rotateX(-10deg);
    }
    .id-card-display.visible {
      opacity: 1;
      transform: translateX(-50%) translateY(0) rotateX(0deg);
    }
    .id-card {
      width: 280px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(56, 189, 248, 0.35);
      overflow: hidden;
      border: 1px solid #e0e0e0;
      font-family: "IBM Plex Sans", sans-serif;
    }
    .id-card-header {
      background-image: url('../image/idcapsu.png');
      background-size: cover;
      background-position: center;
      height: 60px;
      color: transparent;
    }
    .id-card-body {
      padding: 20px;
      text-align: center;
    }
    .id-card-photo {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin: 0 auto 25px auto;
      overflow: hidden;
      border: 4px solid #eef6ff;
    }
    .id-card-photo img { width: 100%; height: 100%; object-fit: cover; }
    .id-card-name {
      font-size: 25px;
      font-weight: 700;
      color: #000000;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .id-card-info { font-size: 14px; color: #555; margin-bottom: 2px; }
  </style>
  <style>
    /* Dashboard Preview Layout */
    :root {
      --space-xs: 6px;
      --space-sm: 12px;
      --space-md: 18px;
      --space-lg: 24px;
      --space-xl: 32px;
      --topbar-height: 64px;
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-brand">
      <img src="/server/School_Entrance_Monitoring_System/image/Capiz_State_University.png" alt="CAPSU" class="topbar-logo" />
      <div class="topbar-title">Capiz State University Pilar Satallite College</div>
    </div>
    <div class="topbar-actions">
      <div class="theme-switcher" id="themeSwitcher" title="Toggle Theme">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
      </div>
      <div class="admin-name"><?php echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : ''; ?></div>
      <button id="logoutBtn" class="btn secondary" style="margin-left:12px;">Logout</button>
    </div>
  </header>
  <div class="layout">
    <nav class="sidebar">
      <div class="nav-brand">
        <span class="brand-dot"><img src="/server/School_Entrance_Monitoring_System/image/Capiz_State_University.png" alt="CAPSU"></span>
        <span class="brand-text">CAPSU GATE</span>
      </div>
      <button class="tab-btn active" data-tab="dashboardTab" title="Live Preview">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M4 13h6V4H4v9zm0 7h6v-5H4v5zm10 0h6v-9h-6v9zm0-16v5h6V4h-6z" />
          </svg>
        </span>
        <span class="tab-label">Live Preview</span>
      </button>
      <button class="tab-btn" data-tab="registerTab" title="Register">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M7 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-6-6H7zm6 1.5L18.5 9H13V3.5zM8 13h4a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2zm0 4h8a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2zm7-7h2a1 1 0 1 1 0 2h-2a1 1 0 1 1 0-2z" />
          </svg>
        </span>
        <span class="tab-label">Register</span>
      </button>
      <button class="tab-btn" data-tab="personalActivityTab" title="Personal Activity">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M12 2a5 5 0 0 1 5 5v1h1.5A2.5 2.5 0 0 1 21 10.5v8A2.5 2.5 0 0 1 18.5 21h-13A2.5 2.5 0 0 1 3 18.5v-8A2.5 2.5 0 0 1 5.5 8H7V7a5 5 0 0 1 5-5zm3 6V7a3 3 0 0 0-6 0v1h6zm-3 5a2 2 0 0 0-1 3.732V17a1 1 0 0 0 2 0v-.268A2 2 0 0 0 12 13z" />
          </svg>
        </span>
        <span class="tab-label">Personal Activity</span>
      </button>
      <button class="tab-btn" data-tab="dailyLogsTab" title="Daily Logs">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M4 5a2 2 0 0 1 2-2h8l6 6v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5zm10-1.5V9h5.5L14 3.5zM8 13h8a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2zM8 17h6a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2z" />
          </svg>
        </span>
        <span class="tab-label">Daily Logs</span>
      </button>
      <button class="tab-btn" data-tab="reportsTab" title="Analytics">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M4 19a1 1 0 0 1 1-1h14a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1zm2-3V9a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0zm5 0V6a1 1 0 1 1 2 0v10a1 1 0 1 1-2 0zm5 0V11a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0z" />
          </svg>
        </span>
        <span class="tab-label">Reports</span>
      </button>
      <button class="tab-btn" data-tab="settingsTab" title="Settings">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
          </svg>
        </span>
        <span class="tab-label">Settings</span>
      </button>
    </nav>
    <main class="main">
      <!-- Visual ID Card Display -->
      <div id="dashboardTab" class="tab-content active">
        <section class="sectionlivedashboard">
          <div class="preview-layout">
            <div class="top-row">
              <aside class="panel-fixed" id="scanLogPanel">
                <div class="panel-header">
                  <div>
                    <h2 class="panel-title"> LIVE S.E.M.S DASHBOARD</h2>
                    <p class="panel-note">Permanent view for in/out scans</p>
                  </div>
                </div>
                <div class="status" id="status">Loading...</div>
                <div class="panel-table">
                  <table>
                    <thead>
                      <tr>
                        <th>User</th>
                        <th>Dir</th>
                        <th>UID</th>
                        <th>Dept</th>
                        <th>Admin</th>
                        <th>Time</th>
                      </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                  </table>
                </div>
              </aside>
              <div class="id-card-container">
                <div id="idCardDisplay" class="id-card-display">
                  <div class="id-card">
                    <div class="id-card-base"></div>
                    <div class="id-card-content">
                      <div class="id-card-layout">
                        <div class="id-card-photo-area">
                          <img id="idCardPhoto" src="/server/School_Entrance_Monitoring_System/image/nophoto_s.png" alt="User Photo">
                        </div>
                        <div class="id-card-info-area">
                          <div id="idCardName" class="id-card-name">Sample Name</div>
                          <div id="idCardRole" class="id-card-role">Student</div>
                        </div>
                        <div class="id-card-id-area">
                          <div id="idCardId" class="id-card-id">ID: 123456</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div id="idCardPlaceholder" class="id-card-placeholder hidden">Waiting for next scan...</div>
              <div class="stats-column">
                <div class="stats-cards">
                  <div class="stat-item stat-today" data-tab="dailyLogsTab" role="button" tabindex="0">
                    <span class="stat-label">Today Scans</span>
                    <span class="stat-value" id="todayTotal">-</span>
                    <span class="stat-meta" id="todayMeta">In: - | Out: -</span>
                  </div>
                  <div class="stat-item stat-inside" data-tab="dashboardTab" role="button" tabindex="0">
                    <span class="stat-label">Inside Now</span>
                    <span class="stat-value" id="insideTotal">-</span>
                    <span class="stat-meta" id="insideMeta">Students: - | Faculty: -</span>
                  </div>
                  <div class="stat-item stat-item-alert stat-suspicious" data-tab="reportsTab" role="button" tabindex="0">
                    <span class="stat-label">Suspicious</span>
                    <span class="stat-value" id="suspiciousCount">-</span>
                    <span class="stat-meta" id="suspiciousMeta">24h</span>
                  </div>
                  <div class="overview-card card-week" data-tab="reportsTab" role="button" tabindex="0">
                    <h3>Week</h3>
                    <div class="value" id="weekTotal">-</div>
                    <div class="meta" id="weekMeta">Avg/day: -</div>
                  </div>
                  <div class="overview-card card-month" data-tab="reportsTab" role="button" tabindex="0">
                    <h3>Month</h3>
                    <div class="value" id="monthTotal">-</div>
                    <div class="meta" id="monthMeta">Best day: -</div>
                  </div>
                  <div class="overview-card card-active" data-tab="personalActivityTab" role="button" tabindex="0">
                    <h3>Active</h3>
                    <div class="value" id="activeStudents">-</div>
                    <div class="meta">7d</div>
                  </div>
                </div>
              </div>
            </div>
            </div>
          </div>
        </section>

      </div>

      <div id="reportsTab" class="tab-content">
        <section class="section">
          <h1>Reports</h1>
          <p class="sub">Operational alerts, peak scan windows, and admin activity.</p>
        </section>

        <section class="section">
          <h1>Historical Charts</h1>
          <p class="sub">Full history of student scans. Hover the line to see student, faculty, and staff counts.</p>
          <div class="chart-grid">
            <div class="chart-card wide">
              <div class="chart-header">
                <div class="chart-title-row">
                  <h3>Student In vs Out (All Time)</h3>
                  <div class="chart-controls">
                    <label for="historyMode">View</label>
                    <select id="historyMode">
                      <option value="day" selected>Date</option>
                      <option value="hour">Time</option>
                    </select>
                  </div>
                </div>
                <span class="chart-sub">Hover a point to see IN/OUT counts by role</span>
              </div>
              <div class="chart-canvas chart-tall">
                <canvas id="historyChart"></canvas>
              </div>
            </div>
            <div class="chart-card">
              <div class="chart-header">
                <h3>Role Share (All Time)</h3>
                <span class="chart-sub">Total scans by role</span>
              </div>
              <div class="chart-canvas">
                <canvas id="roleChart"></canvas>
              </div>
            </div>
            <div class="chart-card">
              <div class="chart-header">
                <h3>In vs Out (All Time)</h3>
                <span class="chart-sub">Overall totals</span>
              </div>
              <div class="chart-canvas">
                <canvas id="directionChart"></canvas>
              </div>
            </div>
          </div>
          <div class="status" id="chartStatus">Loading charts...</div>
        </section>

        <section class="section">
          <div class="storage-heading">
            <div>
              <h1>Database Storage</h1>
              <p class="sub">Monitor the space consumed by database tables, including data and indexes.</p>
            </div>
            <button type="button" class="btn secondary compact" id="refreshStorageBtn">Refresh</button>
          </div>

          <div class="storage-summary-grid">
            <div class="storage-summary-card">
              <span class="storage-summary-label">Total Used</span>
              <strong class="storage-summary-value" id="storageTotalBytes">-</strong>
              <span class="storage-summary-meta" id="storageTotalMeta">Loading...</span>
            </div>
            <div class="storage-summary-card">
              <span class="storage-summary-label">Data</span>
              <strong class="storage-summary-value storage-data-value" id="storageDataBytes">-</strong>
              <span class="storage-summary-meta" id="storageDataMeta">-</span>
            </div>
            <div class="storage-summary-card">
              <span class="storage-summary-label">Indexes</span>
              <strong class="storage-summary-value storage-index-value" id="storageIndexBytes">-</strong>
              <span class="storage-summary-meta" id="storageIndexMeta">-</span>
            </div>
            <div class="storage-summary-card">
              <span class="storage-summary-label">Tables</span>
              <strong class="storage-summary-value" id="storageTableCount">-</strong>
              <span class="storage-summary-meta" id="storageRowsMeta">-</span>
            </div>
          </div>

          <div class="storage-layout">
            <div class="chart-card storage-chart-card">
              <div class="chart-header">
                <h3>Storage by Table</h3>
                <span class="chart-sub">Data and indexes</span>
              </div>
              <div class="chart-canvas storage-chart-canvas">
                <canvas id="storageChart"></canvas>
              </div>
              <div class="status storage-status" id="storageChartStatus">Loading storage...</div>
            </div>

            <div class="chart-card storage-table-card">
              <div class="chart-header">
                <h3>Table Details</h3>
                <span class="chart-sub">Largest tables first</span>
              </div>
              <div class="storage-table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Table</th>
                      <th>Rows</th>
                      <th>Data</th>
                      <th>Indexes</th>
                      <th>Total</th>
                      <th>Share</th>
                    </tr>
                  </thead>
                  <tbody id="storageRows">
                    <tr><td colspan="6">Loading table storage...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <section class="section">
          <div style="margin-bottom:16px;">
            <label for="reportAdminFilter" style="font-size:13px; color:var(--muted); margin-right:6px;">Admin:</label>
            <select id="reportAdminFilter" style="padding:8px 10px; border-radius:8px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-color);">
              <option value="">All Admins</option>
            </select>
          </div>
          <div class="grid" id="adminStatsGrid">
            <div class="card">
              <h3>Loading Admin Stats...</h3>
              <div class="value">-</div>
            </div>
          </div>
          <div class="split">
            <div class="card">
              <h3>Peak Hours Today</h3>
              <ul class="list" id="peakTimes"></ul>
            </div>
            <div class="card alert-card">
              <h3>Alert Summary</h3>
              <ul class="list" id="alertList"></ul>
            </div>
          </div>
        </section>

        <section class="section">
          <h1>Suspicious Scans</h1>
          <p class="sub">Consecutive IN/IN or OUT/OUT activity for review.</p>
          <div class="panel-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User</th>
                  <th>Role</th>
                  <th>Dir</th>
                  <th>Admin</th>
                  <th>Prev Time</th>
                  <th>Time</th>
                </tr>
              </thead>
              <tbody id="suspiciousRows">
                <tr><td colspan="7">Loading suspicious activity...</td></tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <div id="registerTab" class="tab-content">
        <section class="section">
          <h1>Register Card</h1>
          <p class="sub">Scan a card first to view or register details.</p>
          <div id="scanPrompt" class="card scan-prompt">
            <div class="scan-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" role="img" focusable="false">
                <path d="M4 7a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7zm3-1a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H7zm2.5 3.5h5a1 1 0 1 1 0 2h-5a1 1 0 1 1 0-2zm0 4h3a1 1 0 1 1 0 2h-3a1 1 0 1 1 0-2z" />
              </svg>
            </div>
            <div class="scan-text">
              <h3>Scan an RFID Card</h3>
              <p>Hold the card near the reader to load person details.</p>
            </div>
          </div>

          <div id="registeredView" class="card hidden readonly" style="margin-top: 16px;">
            <h3>Registered Person</h3>
            <div class="form-grid">
              <div class="field">
                <label>UID</label>
                <input id="viewUid" disabled />
              </div>
              <div class="field">
                <label>Name</label>
                <input id="viewName" disabled />
              </div>
              <div class="field">
                <label>Photo</label>
                <img id="viewPhoto" src="/server/School_Entrance_Monitoring_System/image/nophoto_s.png" alt="User Photo" style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:2px solid #e0e0e0;" />
              </div>
              <div class="field role-field role-student">
                <label>Student ID</label>
                <input id="viewStudentId" disabled />
              </div>
              <div class="field role-field role-student">
                <label>Course</label>
                <input id="viewCourse" disabled />
              </div>
              <div class="field role-field role-student">
                <label>School Year</label>
                <input id="viewSchoolYear" disabled />
              </div>
              <div class="field role-field role-student">
                <label>Section</label>
                <input id="viewSection" disabled />
              </div>
              <div class="field role-field role-faculty">
                <label>Faculty ID</label>
                <input id="viewFacultyId" disabled />
              </div>
              <div class="field role-field role-staff">
                <label>Staff ID</label>
                <input id="viewStaffId" disabled />
              </div>
              <div class="field role-field role-faculty role-staff">
                <label>Department</label>
                <input id="viewDepartment" disabled />
              </div>
              <div class="field role-field role-visitor">
                <label>Purpose</label>
                <input id="viewPurpose" disabled />
              </div>
              <div class="field role-field role-visitor">
                <label>Valid Until</label>
                <input id="viewValidUntil" disabled />
              </div>
              <div class="field">
                <label>Phone</label>
                <input id="viewPhone" disabled />
              </div>
              <div class="field">
                <label>Email</label>
                <input id="viewEmail" disabled />
              </div>
              <div class="field">
                <label>Role</label>
                <input id="viewRole" disabled />
              </div>
            </div>
            <div class="form-actions">
              <button class="btn secondary" type="button" id="editRegistered">Edit Details</button>
            </div>
          </div>

          <div id="registerFormWrap" class="hidden" style="margin-top: 16px;">
            <form id="registerForm" enctype="multipart/form-data">
              <div class="form-grid">
                <div class="field">
                  <label for="regUid">UID</label>
                  <input id="regUid" name="uid" placeholder="Scan a card to autofill" />
                </div>
                <div class="field">
                  <label for="regName">Name</label>
                  <input id="regName" name="name" required />
                </div>
                <div class="field role-field role-student">
                  <label for="regStudentId">Student ID</label>
                  <input id="regStudentId" name="student_id" />
                </div>
                <div class="field role-field role-student">
                  <label for="regCourse">Course</label>
                  <input id="regCourse" name="course" />
                </div>
                <div class="field role-field role-student">
                  <label for="regSchoolYear">School Year</label>
                  <input id="regSchoolYear" name="school_year" />
                </div>
                <div class="field role-field role-student">
                  <label for="regSection">Section</label>
                  <input id="regSection" name="section" />
                </div>
                <div class="field role-field role-faculty">
                  <label for="regFacultyId">Faculty ID</label>
                  <input id="regFacultyId" name="faculty_id" />
                </div>
                <div class="field role-field role-staff">
                  <label for="regStaffId">Staff ID</label>
                  <input id="regStaffId" name="staff_id" />
                </div>
                <div class="field role-field role-faculty role-staff">
                  <label for="regDepartment">Department</label>
                  <input id="regDepartment" name="department" />
                </div>
                <div class="field role-field role-visitor">
                  <label for="regPurpose">Purpose</label>
                  <input id="regPurpose" name="purpose" />
                </div>
                <div class="field role-field role-visitor">
                  <label for="regValidUntil">Valid Until</label>
                  <input id="regValidUntil" name="valid_until" placeholder="YYYY-MM-DD" />
                </div>
                <div class="field">
                  <label for="regPhone">Phone</label>
                  <input id="regPhone" name="phone" />
                </div>
                <div class="field">
                  <label for="regEmail">Email</label>
                  <input id="regEmail" name="email" type="email" />
                </div>
                <div class="field">
                  <label for="regRole">Role</label>
                  <select id="regRole" name="role">
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                    <option value="staff">Staff</option>
                    <option value="visitor">Visitor</option>
                  </select>
                </div>
                <div class="field">
                  <label for="regPhoto">Photo</label>
                  <input id="regPhoto" name="photo" type="file" accept="image/*" />
                  <img id="regPhotoPreview" src="/server/School_Entrance_Monitoring_System/image/nophoto_s.png" alt="Preview" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #e0e0e0;margin-top:6px;display:block;" />
                </div>
              </div>
              <div class="form-actions">
                <button class="btn" type="submit">Save Registration</button>
                <button class="btn secondary" type="reset">Clear</button>
                <span class="status-pill" id="registerStatus"></span>
              </div>
            </form>
          </div>
        </section>

        
      </div>

      <div id="personalActivityTab" class="tab-content">
        <section class="section">
          <h1>Personal Activity</h1>
          <p class="sub">Browse scans by person, scoped by admin.</p>
          <div class="split">
            <div class="card">
              <h3>Person Logs</h3>
              <div class="field"><label for="personalAdminFilter">Admin filter</label><select id="personalAdminFilter"><option value="">All Admins</option></select></div>
              <div class="field"><label for="personalUserSelect">Select person</label><select id="personalUserSelect"></select></div>
              <div class="tag-row" id="personalUserAdminTags"></div>
              <div class="panel-table"><table class="table-compact"><thead><tr><th>ID</th><th>Dir</th><th>Admin</th><th>Time</th></tr></thead><tbody id="personalUserLogs"></tbody></table></div>
            </div>
            <div class="stack">
              <div class="card"><h3>Quick Note</h3><p class="sub">This isolates personal activity on the dashboard.</p></div>
              <div class="card"><h3>Back to Dashboard</h3><p class="sub">Return to the main overview.</p><button class="btn" type="button" data-tab="dashboardTab">Open Dashboard</button></div>
            </div>
          </div>
        </section>
      </div>

      <div id="dailyLogsTab" class="tab-content">
        <section class="section">
          <h1>Daily Logs</h1>
          <p class="sub">Generate daily scan outputs from the API.</p>
          <div class="daily-log-search-section">
            <div class="search-bar">
              <div class="search-input-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="dailyLogSearchInput" type="search" placeholder="Search by name, UID, Student ID, Faculty ID..." autocomplete="off" />
              </div>
              <select id="dailyLogSearchRole" style="padding:8px 10px; border-radius:8px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-color);">
                <option value="">All Types</option>
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
                <option value="staff">Staff</option>
                <option value="visitor">Visitor</option>
              </select>
              <button type="button" class="btn secondary compact" id="dailyLogSearchBtn">Search</button>
            </div>

            <div id="dailyLogSearchResults" class="search-results hidden"></div>
          </div>

          <div id="personPreviewPanel" class="person-preview-section hidden">
            <div class="person-preview-grid">
              <div class="person-preview-photo">
                <img id="previewPhoto" src="/server/School_Entrance_Monitoring_System/image/nophoto_s.png" alt="User Photo" />
              </div>
              <div class="person-preview-details">
                <h3 id="previewName">-</h3>
                <div class="detail-row">
                  <span class="detail-item"><strong>UID:</strong> <span id="previewUid">-</span></span>
                  <span class="detail-item"><strong>Role:</strong> <span id="previewRole">-</span></span>
                  <span class="detail-item" id="previewIdDetail"></span>
                </div>
                <div class="detail-row" id="previewExtraDetails"></div>
              </div>
              <button type="button" class="btn secondary compact" id="previewCloseBtn">Close</button>
            </div>
            <div class="person-preview-history">
              <h4>Scan History (Last 50 entries)</h4>
              <div class="panel-table" style="max-height:280px;">
                <table class="table-compact">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Direction</th>
                      <th>Admin</th>
                      <th>Status</th>
                      <th>Time</th>
                    </tr>
                  </thead>
                  <tbody id="previewHistoryRows">
                    <tr><td colspan="5">Loading...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <section class="section admin-history-section" aria-labelledby="adminHistoryTitle">
            <div class="admin-history-heading">
              <div>
                <h2 id="adminHistoryTitle">Admin History</h2>
                <p class="sub">Search your name or admin UID, then preview and export the scans you processed.</p>
              </div>
            </div>

            <div class="admin-history-controls">
              <div class="field admin-history-search-field">
                <label for="adminHistorySearch">Profile search</label>
                <input id="adminHistorySearch" type="search" value="<?php echo htmlspecialchars($_SESSION['admin_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your name or admin UID" autocomplete="off" />
              </div>
              <button type="button" class="btn secondary compact admin-history-search-btn" id="adminHistorySearchBtn">Search</button>
            </div>

            <div id="adminHistoryStatus" class="status" role="status" aria-live="polite"></div>

            <div id="adminHistoryPanel" class="admin-history-panel hidden">
              <div class="admin-history-profile">
                <div class="admin-history-avatar" aria-hidden="true">
                  <span id="adminHistoryInitials">A</span>
                </div>
                <div class="admin-history-profile-copy">
                  <span class="role-badge role-admin">Admin</span>
                  <h3 id="adminHistoryName">Admin Profile</h3>
                  <div id="adminHistoryUid" class="admin-history-uid">-</div>
                  <button type="button" class="btn secondary compact admin-history-profile-btn" id="adminHistoryProfileBtn">View Full Profile</button>
                </div>
                <div class="admin-history-summary" aria-label="History summary">
                  <div><span>Total</span><strong id="adminHistoryTotal">0</strong></div>
                  <div><span>IN</span><strong id="adminHistoryIn">0</strong></div>
                  <div><span>OUT</span><strong id="adminHistoryOut">0</strong></div>
                  <div><span>Suspicious</span><strong id="adminHistorySuspicious">0</strong></div>
                </div>
              </div>

              <div class="admin-history-range">
                <div class="field">
                  <label for="adminHistoryFrom">From</label>
                  <input id="adminHistoryFrom" class="daily-report-date" type="date" />
                </div>
                <div class="field">
                  <label for="adminHistoryTo">To</label>
                  <input id="adminHistoryTo" class="daily-report-date" type="date" />
                </div>
                <div class="daily-report-actions admin-history-actions">
                  <button type="button" class="btn secondary compact" id="adminHistoryPrintBtn">Print</button>
                  <button type="button" class="btn secondary compact" id="adminHistoryCsvBtn">Download CSV</button>
                  <button type="button" class="btn secondary compact" id="adminHistoryXlsBtn">Download Excel</button>
                </div>
              </div>

              <div class="panel-table admin-history-table-panel">
                <table class="table-compact">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>User</th>
                      <th>Role</th>
                      <th>Direction</th>
                      <th>UID</th>
                      <th>Status</th>
                      <th>Time</th>
                    </tr>
                  </thead>
                  <tbody id="adminHistoryRows">
                    <tr><td colspan="7">No scans found for this date range.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </section>

          <div style="display:flex; gap:8px; align-items:center; margin:14px 0 10px 0; flex-wrap:wrap;">
            <div class="daily-report-row">
              <label for="dailyReportDate">Daily log</label>
              <input id="dailyReportDate" class="daily-report-date" type="date" />
            </div>
            <label for="dailyAdminFilter" style="font-size:13px; color:var(--muted); margin-left:6px;">Admin:</label>
            <select id="dailyAdminFilter" style="padding:8px 10px; border-radius:8px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-color);">
              <option value="">All Admins</option>
            </select>
            <label style="font-size:13px; color:var(--muted); margin-left:8px; display:flex; align-items:center; gap:6px;">
              <input type="checkbox" id="dailySuspiciousOnly" />
              <span>Only suspicious</span>
            </label>
          </div>

          <div class="daily-report-actions" style="margin:14px 0 10px;">
            <button type="button" class="btn secondary compact" id="dailyPrintBtn">Print Daily</button>
            <button type="button" class="btn secondary compact" id="dailyCsvBtn">Download CSV</button>
            <button type="button" class="btn secondary compact" id="dailyXlsBtn">Download Excel</button>
          </div>
          <div class="panel-table" style="height:70vh; min-height:520px;">
            <iframe id="dailyPreviewFrame" title="Daily logs preview" style="width:100%; height:100%; border:0; background:#fff;"></iframe>
          </div>
        </section>
      </div>

      <div id="settingsTab" class="tab-content">
        <section class="section">
          <h1>Settings</h1>
          <p class="sub">Customize the dashboard behavior and appearance.</p>
          <form id="settingsForm" class="settings-list">
            <div class="settings-item">
              <div class="settings-item-main">
                <div class="settings-item-title">Theme</div>
                <div class="settings-item-desc">Switch between light and dark mode</div>
              </div>
              <div class="settings-item-control">
                <label class="toggle-switch">
                  <input type="checkbox" id="settingTheme" name="theme" value="dark" />
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
            <div class="settings-item">
              <div class="settings-item-main">
                <div class="settings-item-title">Font Size</div>
                <div class="settings-item-desc">Adjust the dashboard text size</div>
              </div>
              <div class="settings-item-control">
                <select id="settingFontSize" name="font_size" class="settings-select">
                  <option value="small">Small</option>
                  <option value="medium" selected>Medium</option>
                  <option value="large">Large</option>
                </select>
              </div>
            </div>
            <div class="settings-item">
              <div class="settings-item-main">
                <div class="settings-item-title">Day Reset Hour</div>
                <div class="settings-item-desc">When daily scan counts should reset</div>
              </div>
              <div class="settings-item-control">
                <select id="settingDailyResetHour" name="daily_reset_hour" class="settings-select">
                  <option value="0">12:00 AM</option>
                  <option value="1">1:00 AM</option>
                  <option value="2">2:00 AM</option>
                  <option value="3">3:00 AM</option>
                  <option value="4">4:00 AM</option>
                  <option value="5">5:00 AM</option>
                  <option value="6">6:00 AM</option>
                  <option value="7">7:00 AM</option>
                  <option value="8">8:00 AM</option>
                  <option value="9">9:00 AM</option>
                  <option value="10">10:00 AM</option>
                  <option value="11">11:00 AM</option>
                  <option value="12">12:00 PM</option>
                  <option value="13">1:00 PM</option>
                  <option value="14">2:00 PM</option>
                  <option value="15">3:00 PM</option>
                  <option value="16">4:00 PM</option>
                  <option value="17">5:00 PM</option>
                  <option value="18">6:00 PM</option>
                  <option value="19">7:00 PM</option>
                  <option value="20">8:00 PM</option>
                  <option value="21">9:00 PM</option>
                  <option value="22">10:00 PM</option>
                  <option value="23">11:00 PM</option>
                </select>
              </div>
            </div>
            <div class="settings-item">
              <div class="settings-item-main">
                <div class="settings-item-title">Today's Scan Refresh</div>
                <div class="settings-item-desc">How often to refresh today's scan data (seconds)</div>
              </div>
              <div class="settings-item-control">
                <input id="settingRefreshToday" name="refresh_today_scan" type="number" min="1" max="300" class="settings-input" />
              </div>
            </div>
            <div class="settings-item">
              <div class="settings-item-main">
                <div class="settings-item-title">Inside Now Refresh</div>
                <div class="settings-item-desc">How often to refresh inside-now data (seconds)</div>
              </div>
              <div class="settings-item-control">
                <input id="settingRefreshInside" name="refresh_inside_now" type="number" min="1" max="300" class="settings-input" />
              </div>
            </div>
            <div class="settings-item">
              <div class="settings-item-main">
                <div class="settings-item-title">Suspicious Alerts Refresh</div>
                <div class="settings-item-desc">How often to refresh suspicious alerts (seconds)</div>
              </div>
              <div class="settings-item-control">
                <input id="settingRefreshSuspicious" name="refresh_suspicious_alerts" type="number" min="1" max="300" class="settings-input" />
              </div>
            </div>
            <div class="form-actions">
              <button type="button" class="btn" id="saveSettingsBtn">Save Settings</button>
              <span class="status-pill" id="settingsStatus"></span>
            </div>
          </form>
        </section>
      </div>
    </main>

    <div id="userProfileModal" class="profile-modal hidden" role="dialog" aria-modal="true" aria-labelledby="profileName">
      <div class="profile-backdrop" data-close-profile></div>
      <div class="profile-panel">
        <button type="button" class="profile-close" id="closeProfileBtn" aria-label="Close profile">&times;</button>
        <div class="profile-loading" id="profileLoading">Loading profile...</div>
        <div id="profileContent" class="hidden">
          <div class="profile-header">
            <div class="profile-avatar">
              <img id="profilePhoto" src="/server/School_Entrance_Monitoring_System/image/nophoto_s.png" alt="Profile photo" />
            </div>
            <div class="profile-heading">
              <span id="profileRole" class="role-badge role-unknown">Unknown</span>
              <h2 id="profileName">Profile</h2>
              <div id="profileIdentifier" class="profile-identifier">-</div>
            </div>
          </div>
          <div id="profileDetails" class="profile-details"></div>
          <div id="profileSummary" class="profile-summary">
            <div><span>Total Scans</span><strong id="profileTotalScans">-</strong></div>
            <div><span>IN</span><strong id="profileInScans">-</strong></div>
            <div><span>OUT</span><strong id="profileOutScans">-</strong></div>
          </div>
          <div class="profile-history-heading">
            <h3>Scan History</h3>
            <span id="profileHistoryCount" class="chart-sub">-</span>
          </div>
          <div class="profile-history-wrap">
            <table>
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Direction</th>
                  <th scope="col">Admin</th>
                  <th scope="col">Status</th>
                  <th scope="col">Time</th>
                </tr>
              </thead>
              <tbody id="profileHistoryRows"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

  <script src="vendor/chart.umd.min.js"></script>
  <script>
    const rowsEl = document.getElementById('rows');
    const statusEl = document.getElementById('status');
    const adminFilterEl = document.getElementById('adminFilter');
    const suspiciousOnlyEl = document.getElementById('suspiciousOnly');
    const todayTotalEl = document.getElementById('todayTotal');
    const todayMetaEl = document.getElementById('todayMeta');
    const weekTotalEl = document.getElementById('weekTotal');
    const weekMetaEl = document.getElementById('weekMeta');
    const monthTotalEl = document.getElementById('monthTotal');
    const monthMetaEl = document.getElementById('monthMeta');
    const activeStudentsEl = document.getElementById('activeStudents');
    const insideTotalEl = document.getElementById('insideTotal');
    const insideMetaEl = document.getElementById('insideMeta');
    const suspiciousCountEl = document.getElementById('suspiciousCount');
    const suspiciousMetaEl = document.getElementById('suspiciousMeta');
    const chartStatusEl = document.getElementById('chartStatus');
    const historyChartEl = document.getElementById('historyChart');
    const roleChartEl = document.getElementById('roleChart');
    const directionChartEl = document.getElementById('directionChart');
    const historyModeEl = document.getElementById('historyMode');
    const storageChartEl = document.getElementById('storageChart');
    const storageChartStatusEl = document.getElementById('storageChartStatus');
    const storageTotalBytesEl = document.getElementById('storageTotalBytes');
    const storageTotalMetaEl = document.getElementById('storageTotalMeta');
    const storageDataBytesEl = document.getElementById('storageDataBytes');
    const storageDataMetaEl = document.getElementById('storageDataMeta');
    const storageIndexBytesEl = document.getElementById('storageIndexBytes');
    const storageIndexMetaEl = document.getElementById('storageIndexMeta');
    const storageTableCountEl = document.getElementById('storageTableCount');
    const storageRowsMetaEl = document.getElementById('storageRowsMeta');
    const storageRowsEl = document.getElementById('storageRows');
    const refreshStorageBtn = document.getElementById('refreshStorageBtn');
    const peakTimesEl = document.getElementById('peakTimes');
    const alertListEl = document.getElementById('alertList');
    const reportAdminFilterEl = document.getElementById('reportAdminFilter');
    const adminStatsGridEl = document.getElementById('adminStatsGrid');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
const personalAdminFilterEl = document.getElementById('personalAdminFilter');
    const personalUserSelectEl = document.getElementById('personalUserSelect');
    const personalUserLogsEl = document.getElementById('personalUserLogs');
    const personalUserAdminTagsEl = document.getElementById('personalUserAdminTags');
    const registerFormEl = document.getElementById('registerForm');
    const registerStatusEl = document.getElementById('registerStatus');
    const scanPromptEl = document.getElementById('scanPrompt');
    const registeredViewEl = document.getElementById('registeredView');
    const registerFormWrapEl = document.getElementById('registerFormWrap');
    const editRegisteredEl = document.getElementById('editRegistered');
    const regUidEl = document.getElementById('regUid');
    const regStudentIdEl = document.getElementById('regStudentId');
    const regFacultyIdEl = document.getElementById('regFacultyId');
    const regStaffIdEl = document.getElementById('regStaffId');
    const regPhotoEl = document.getElementById('regPhoto');
    const regPhotoPreviewEl = document.getElementById('regPhotoPreview');
    const viewUidEl = document.getElementById('viewUid');
    const viewNameEl = document.getElementById('viewName');
    const viewStudentIdEl = document.getElementById('viewStudentId');
    const viewCourseEl = document.getElementById('viewCourse');
    const viewSchoolYearEl = document.getElementById('viewSchoolYear');
    const viewSectionEl = document.getElementById('viewSection');
    const viewPhoneEl = document.getElementById('viewPhone');
    const viewEmailEl = document.getElementById('viewEmail');
    const viewRoleEl = document.getElementById('viewRole');
    const viewFacultyIdEl = document.getElementById('viewFacultyId');
    const viewStaffIdEl = document.getElementById('viewStaffId');
    const viewDepartmentEl = document.getElementById('viewDepartment');
    const viewPurposeEl = document.getElementById('viewPurpose');
    const viewValidUntilEl = document.getElementById('viewValidUntil');
    const viewPhotoEl = document.getElementById('viewPhoto');
    viewPhotoEl.onerror = function() {
      this.src = '/server/School_Entrance_Monitoring_System/image/nophoto_s.png';
    };
    const idCardDisplayEl = document.getElementById('idCardDisplay');
    const idCardPhotoEl = document.getElementById('idCardPhoto');
    idCardPhotoEl.onerror = function() {
      this.src = '/server/School_Entrance_Monitoring_System/image/nophoto_s.png';
    };
    const idCardNameEl = document.getElementById('idCardName');
    const idCardRoleEl = document.getElementById('idCardRole');
    const idCardIdEl = document.getElementById('idCardId');
    const idCardPlaceholderEl = document.getElementById('idCardPlaceholder');
    const userProfileModalEl = document.getElementById('userProfileModal');
    const profileLoadingEl = document.getElementById('profileLoading');
    const profileContentEl = document.getElementById('profileContent');
    const profilePhotoEl = document.getElementById('profilePhoto');
    profilePhotoEl.onerror = function() {
      this.onerror = null;
      this.src = '/server/School_Entrance_Monitoring_System/image/nophoto_s.png';
    };
    const profileRoleEl = document.getElementById('profileRole');
    const profileNameEl = document.getElementById('profileName');
    const profileIdentifierEl = document.getElementById('profileIdentifier');
    const profileDetailsEl = document.getElementById('profileDetails');
    const profileSummaryEl = document.getElementById('profileSummary');
    const profileTotalScansEl = document.getElementById('profileTotalScans');
    const profileInScansEl = document.getElementById('profileInScans');
    const profileOutScansEl = document.getElementById('profileOutScans');
    const profileHistoryCountEl = document.getElementById('profileHistoryCount');
    const profileHistoryRowsEl = document.getElementById('profileHistoryRows');
    const closeProfileBtn = document.getElementById('closeProfileBtn');
    const settingsFormEl = document.getElementById('settingsForm');
    const settingsStatusEl = document.getElementById('settingsStatus');
    const settingThemeEl = document.getElementById('settingTheme');
    const settingFontSizeEl = document.getElementById('settingFontSize');
    const settingRefreshTodayEl = document.getElementById('settingRefreshToday');
    const settingRefreshInsideEl = document.getElementById('settingRefreshInside');
    const settingRefreshSuspiciousEl = document.getElementById('settingRefreshSuspicious');
    const settingDailyResetHourEl = document.getElementById('settingDailyResetHour');
    const saveSettingsBtn = document.getElementById('saveSettingsBtn');
    const dailyReportDateEl = document.getElementById('dailyReportDate');
    const dailyAdminFilterEl = document.getElementById('dailyAdminFilter');
    const dailySuspiciousOnlyEl = document.getElementById('dailySuspiciousOnly');
    const dailyPreviewFrameEl = document.getElementById('dailyPreviewFrame');
    const dailyPrintBtn = document.getElementById('dailyPrintBtn');
    const dailyCsvBtn = document.getElementById('dailyCsvBtn');
    const dailyXlsBtn = document.getElementById('dailyXlsBtn');
    let idCardTimeout = null;
    const registerSignalStartMs = Date.now();
    let lastScanId = 0;
    const themeSwitcher = document.getElementById('themeSwitcher');
    let currentRegisteredUser = null;
    let adminTagsByUid = {};
    let historyChart = null;
    let roleChart = null;
    let directionChart = null;
    let storageChart = null;
    let chartHistoryData = [];
    let profileRequestToken = 0;
    let resizedPhotoBlob = null;
    const chartColors = {
      studentIn: '#1d4ed8',
      studentOut: '#dc2626',
      roleStudent: '#1d4ed8',
      roleFaculty: '#0f766e',
      roleStaff: '#f59e0b',
      roleVisitor: '#0ea5e9',
      roleUnknown: '#94a3b8',
      inTotal: '#1d4ed8',
      outTotal: '#dc2626'
    };

    function resizePhotoTo360(file, callback) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
          const canvas = document.createElement('canvas');
          const ctx = canvas.getContext('2d');
          canvas.width = 360;
          canvas.height = 360;
          ctx.drawImage(img, 0, 0, 360, 360);
          canvas.toBlob(function(blob) {
            callback(blob);
          }, 'image/jpeg', 0.9);
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }

    if (window.Chart) {
      Chart.defaults.font.family = '"Space Grotesk", "Segoe UI", sans-serif';
      Chart.defaults.color = '#475569';
    }

    function parseSignalTime(value) {
      if (!value) {
        return NaN;
      }
      const normalized = value.includes('T') ? value : value.replace(' ', 'T');
      const parsed = Date.parse(normalized);
      return Number.isNaN(parsed) ? NaN : parsed;
    }

    function formatHourRange(hour) {
      const start = hour % 24;
      const end = (hour + 1) % 24;
      const format = (h) => {
        const suffix = h >= 12 ? 'PM' : 'AM';
        const hour12 = h % 12 === 0 ? 12 : h % 12;
        return `${hour12}:00 ${suffix}`;
      };
      return `${format(start)} - ${format(end)}`;
    }

    function focusScanLogPanel() {
      const panel = document.getElementById('scanLogPanel');
      if (!panel) {
        return;
      }
      panel.classList.remove('panel-focus');
      // Restart highlight animation for repeated clicks.
      void panel.offsetWidth;
      panel.classList.add('panel-focus');
      panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function formatLocalDateInput(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    function getSelectedDailyReportDate() {
      if (dailyReportDateEl && dailyReportDateEl.value) {
        return dailyReportDateEl.value;
      }
      return formatLocalDateInput(new Date());
    }

    function openDailyReport(format) {
      const url = new URL('../api/scans/daily_report.php', window.location.href);
      url.searchParams.set('date', getSelectedDailyReportDate());
      url.searchParams.set('format', format);
      const adminUid = dailyAdminFilterEl ? dailyAdminFilterEl.value : '';
      if (adminUid) {
        url.searchParams.set('admin_uid', adminUid);
      }
      if (dailySuspiciousOnlyEl && dailySuspiciousOnlyEl.checked) {
        url.searchParams.set('suspicious', '1');
      }

      if (format === 'print') {
        const win = window.open(url.toString(), '_blank', 'noopener');
        if (!win) {
          window.location.href = url.toString();
        }
        return;
      }

      window.location.href = url.toString();
    }

    function refreshDailyPreview() {
      if (!dailyPreviewFrameEl) {
        return;
      }
      const url = new URL('../api/scans/daily_report.php', window.location.href);
      url.searchParams.set('date', getSelectedDailyReportDate());
      url.searchParams.set('format', 'print');
      const adminUid = dailyAdminFilterEl ? dailyAdminFilterEl.value : '';
      if (adminUid) {
        url.searchParams.set('admin_uid', adminUid);
      }
      if (dailySuspiciousOnlyEl && dailySuspiciousOnlyEl.checked) {
        url.searchParams.set('suspicious', '1');
      }
      dailyPreviewFrameEl.src = url.toString();
    }

    function setStats(stats) {
      if (!stats) {
        return;
      }

      if (todayTotalEl) todayTotalEl.textContent = stats.today.total;
      if (todayMetaEl) todayMetaEl.textContent = `In: ${stats.today.in} | Out: ${stats.today.out}`;

      if (weekTotalEl) weekTotalEl.textContent = stats.week.total;
      if (weekMetaEl) weekMetaEl.textContent = `Avg/day: ${stats.week.avg_per_day}`;

      if (monthTotalEl) monthTotalEl.textContent = stats.month.total;
      if (monthMetaEl) monthMetaEl.textContent = `Best day: ${stats.month.best_day}`;

      if (activeStudentsEl) activeStudentsEl.textContent = stats.active_students_7d;
      if (insideTotalEl) insideTotalEl.textContent = stats.inside.total;
      if (insideMetaEl) insideMetaEl.textContent = `Students: ${stats.inside.students} | Faculty: ${stats.inside.faculty}`;

      if (peakTimesEl) {
        let peakHtml = '';
        if (stats.peak_hours_today.length === 0) {
          peakHtml = '<li><span>No scans today</span><span class="badge">0</span></li>';
        } else {
          stats.peak_hours_today.forEach(item => {
            peakHtml += `<li><span>${formatHourRange(item.hour)}</span><span class="badge">${item.count}</span></li>`;
          });
        }
        peakTimesEl.innerHTML = peakHtml;
      }

      const alerts = stats.alerts || {
        unregistered_cards: 0,
        scans_last_10_min: 0,
        unique_today: 0,
        consecutive_in: 0,
        consecutive_out: 0
      };
      if (alertListEl) {
        alertListEl.innerHTML = `
          <li><span>Unregistered cards</span><span class="badge">${alerts.unregistered_cards}</span></li>
          <li><span>Scans last 10 min</span><span class="badge">${alerts.scans_last_10_min}</span></li>
          <li><span>Unique today</span><span class="badge">${alerts.unique_today}</span></li>
          <li><span>Consecutive IN</span><span class="badge">${alerts.consecutive_in}</span></li>
          <li><span>Consecutive OUT</span><span class="badge">${alerts.consecutive_out}</span></li>
        `;
      }

      if (suspiciousCountEl) {
        suspiciousCountEl.textContent = alerts.consecutive_in + alerts.consecutive_out;
      }
      if (suspiciousMetaEl) {
        suspiciousMetaEl.textContent = 'Last 24 hours';
      }
    }

    function setChartStatus(message) {
      if (!chartStatusEl) {
        return;
      }
      chartStatusEl.textContent = message || '';
      chartStatusEl.style.display = message ? 'block' : 'none';
    }

    function formatCount(value) {
      return Number(value || 0).toLocaleString();
    }

    function formatBytes(value) {
      const bytes = Number(value || 0);
      if (bytes === 0) {
        return '0 B';
      }
      const units = ['B', 'KB', 'MB', 'GB', 'TB'];
      const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
      const unitValue = bytes / Math.pow(1024, unitIndex);
      return `${unitValue.toFixed(unitValue >= 10 || unitIndex === 0 ? 1 : 2)} ${units[unitIndex]}`;
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function renderStorageChart(tables, totalBytes) {
      if (!storageChartEl || !window.Chart) {
        return;
      }
      const labels = tables.map(table => table.table_name);
      const dataValues = tables.map(table => Number(table.data_bytes) || 0);
      const indexValues = tables.map(table => Number(table.index_bytes) || 0);
      const data = {
        labels,
        datasets: [
          {
            label: 'Data',
            data: dataValues,
            backgroundColor: '#1d4ed8',
            borderColor: '#1d4ed8',
            borderWidth: 0,
            stack: 'storage'
          },
          {
            label: 'Indexes',
            data: indexValues,
            backgroundColor: '#f59e0b',
            borderColor: '#f59e0b',
            borderWidth: 0,
            stack: 'storage'
          }
        ]
      };
      const options = {
        indexAxis: 'y',
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 10, boxHeight: 10 }
          },
          tooltip: {
            callbacks: {
              label: (context) => `${context.dataset.label}: ${formatBytes(context.parsed.x)}`,
              afterBody: (items) => {
                if (!items.length) {
                  return '';
                }
                const index = items[0].dataIndex;
                const total = (dataValues[index] || 0) + (indexValues[index] || 0);
                const share = totalBytes > 0 ? ((total / totalBytes) * 100).toFixed(1) : '0.0';
                return [`Total: ${formatBytes(total)}`, `Share: ${share}%`];
              }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            stacked: true,
            grid: { color: 'rgba(148, 163, 184, 0.25)' },
            border: { display: false },
            ticks: {
              precision: 0,
              callback: (value) => formatBytes(value)
            }
          },
          y: {
            stacked: true,
            grid: { display: false },
            border: { display: false },
            ticks: {
              autoSkip: false,
              font: { size: 11 }
            }
          }
        }
      };

      if (storageChart) {
        storageChart.data = data;
        storageChart.options = options;
        storageChart.update();
        return;
      }
      storageChart = new Chart(storageChartEl, { type: 'bar', data, options });
    }

    function renderStorageTable(tables, totalBytes) {
      if (!storageRowsEl) {
        return;
      }
      if (!tables.length) {
        storageRowsEl.innerHTML = '<tr><td colspan="6">No database tables found</td></tr>';
        return;
      }
      storageRowsEl.innerHTML = tables.map(table => {
        const total = Number(table.total_bytes) || 0;
        const share = totalBytes > 0 ? ((total / totalBytes) * 100).toFixed(1) : '0.0';
        const rowEstimate = table.rows_are_estimated ? ' title="InnoDB row counts are approximate"' : '';
        return `<tr>
          <td><strong>${escapeHtml(table.table_name)}</strong><span class="storage-engine">${escapeHtml(table.engine)}</span></td>
          <td${rowEstimate}>${formatCount(table.rows)}</td>
          <td>${formatBytes(table.data_bytes)}</td>
          <td>${formatBytes(table.index_bytes)}</td>
          <td><strong>${formatBytes(total)}</strong></td>
          <td>${share}%</td>
        </tr>`;
      }).join('');
    }

    async function loadStorageStats() {
      if (!storageChartStatusEl) {
        return;
      }
      storageChartStatusEl.textContent = 'Loading database storage...';
      try {
        const url = new URL('../api/system/get_database_storage.php', window.location.href);
        const res = await fetch(url.toString());
        if (!res.ok) {
          throw new Error(`Storage request failed with HTTP ${res.status}`);
        }
        const data = await res.json();
        if (!data.ok) {
          throw new Error(data.error || 'Storage data unavailable');
        }
        const tables = Array.isArray(data.tables) ? data.tables : [];
        const totalBytes = Number(data.total_bytes) || 0;
        const dataBytes = Number(data.data_bytes) || 0;
        const indexBytes = Number(data.index_bytes) || 0;
        const totalRows = Number(data.total_rows) || 0;
        const tableCount = Number(data.table_count) || 0;

        if (storageTotalBytesEl) storageTotalBytesEl.textContent = formatBytes(totalBytes);
        if (storageDataBytesEl) storageDataBytesEl.textContent = formatBytes(dataBytes);
        if (storageIndexBytesEl) storageIndexBytesEl.textContent = formatBytes(indexBytes);
        if (storageTableCountEl) storageTableCountEl.textContent = formatCount(tableCount);
        if (storageTotalMetaEl) storageTotalMetaEl.textContent = `${data.database_name || 'Database'} Â· ${formatCount(totalRows)} rows`;
        if (storageDataMetaEl) storageDataMetaEl.textContent = totalBytes > 0 ? `${((dataBytes / totalBytes) * 100).toFixed(1)}% of total` : '-';
        if (storageIndexMetaEl) storageIndexMetaEl.textContent = totalBytes > 0 ? `${((indexBytes / totalBytes) * 100).toFixed(1)}% of total` : '-';
        if (storageRowsMetaEl) storageRowsMetaEl.textContent = tableCount === 1 ? '1 table' : `${formatCount(tableCount)} tables`;

        renderStorageChart(tables, totalBytes);
        renderStorageTable(tables, totalBytes);
        storageChartStatusEl.textContent = `Updated ${new Date(data.generated_at || Date.now()).toLocaleString()}`;
      } catch (err) {
        storageChartStatusEl.textContent = 'Unable to load database storage';
        if (storageTotalBytesEl) storageTotalBytesEl.textContent = '-';
        if (storageDataBytesEl) storageDataBytesEl.textContent = '-';
        if (storageIndexBytesEl) storageIndexBytesEl.textContent = '-';
        if (storageTableCountEl) storageTableCountEl.textContent = '-';
        if (storageTotalMetaEl) storageTotalMetaEl.textContent = 'Unavailable';
        if (storageDataMetaEl) storageDataMetaEl.textContent = '-';
        if (storageIndexMetaEl) storageIndexMetaEl.textContent = '-';
        if (storageRowsMetaEl) storageRowsMetaEl.textContent = '-';
        if (storageRowsEl) storageRowsEl.innerHTML = '<tr><td colspan="6">Storage data unavailable</td></tr>';
        console.error('Failed to load database storage:', err);
      }
    }

    function parseBucket(value, mode) {
      if (!value) {
        return null;
      }
      if (mode === 'hour') {
        const parts = value.split(' ');
        if (parts.length < 2) {
          return null;
        }
        const dateParts = parts[0].split('-').map(Number);
        const timeParts = parts[1].split(':').map(Number);
        if (dateParts.length !== 3 || timeParts.length < 1) {
          return null;
        }
        return new Date(dateParts[0], dateParts[1] - 1, dateParts[2], timeParts[0]);
      }
      const parts = value.split('-').map(Number);
      if (parts.length !== 3) {
        return null;
      }
      return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function formatBucket(date, mode) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      if (mode === 'hour') {
        const hour = String(date.getHours()).padStart(2, '0');
        return `${year}-${month}-${day} ${hour}:00`;
      }
      return `${year}-${month}-${day}`;
    }

    function createEmptyHistoryRow(day) {
      return {
        day,
        total_in: 0,
        total_out: 0,
        student_in: 0,
        student_out: 0,
        faculty_in: 0,
        faculty_out: 0,
        staff_in: 0,
        staff_out: 0,
        visitor_in: 0,
        visitor_out: 0,
        unknown_in: 0,
        unknown_out: 0
      };
    }

    function normalizeHistoryRow(row) {
      return {
        day: row.day,
        total_in: Number(row.total_in) || 0,
        total_out: Number(row.total_out) || 0,
        student_in: Number(row.student_in) || 0,
        student_out: Number(row.student_out) || 0,
        faculty_in: Number(row.faculty_in) || 0,
        faculty_out: Number(row.faculty_out) || 0,
        staff_in: Number(row.staff_in) || 0,
        staff_out: Number(row.staff_out) || 0,
        visitor_in: Number(row.visitor_in) || 0,
        visitor_out: Number(row.visitor_out) || 0,
        unknown_in: Number(row.unknown_in) || 0,
        unknown_out: Number(row.unknown_out) || 0
      };
    }

    function fillHistory(history, mode) {
      const normalized = history.map(normalizeHistoryRow);
      if (normalized.length === 0) {
        return [];
      }
      const map = new Map(normalized.map(row => [row.day, row]));
      const start = parseBucket(normalized[0].day, mode);
      const end = parseBucket(normalized[normalized.length - 1].day, mode);
      if (!start || !end) {
        return normalized;
      }
      const filled = [];
      const stepMs = mode === 'hour' ? 60 * 60 * 1000 : 24 * 60 * 60 * 1000;
      let current = new Date(start.getTime());
      while (current <= end) {
        const key = formatBucket(current, mode);
        filled.push(map.get(key) || createEmptyHistoryRow(key));
        current = new Date(current.getTime() + stepMs);
      }
      return filled;
    }

    function renderHistoryChart(history, mode) {
      if (!historyChartEl || !window.Chart) {
        return;
      }
      chartHistoryData = history;
      const isHour = mode === 'hour';
      const labels = history.map(row => row.day);
      const studentIn = history.map(row => row.student_in);
      const studentOut = history.map(row => row.student_out);
      const tickStep = Math.max(1, Math.ceil(labels.length / (isHour ? 12 : 10)));

      const data = {
        labels,
        datasets: [
          {
            label: 'Students IN',
            data: studentIn,
            borderColor: chartColors.studentIn,
            backgroundColor: 'rgba(29, 78, 216, 0.12)',
            borderWidth: 2,
            tension: 0.32,
            pointRadius: 0,
            pointHoverRadius: 4,
            fill: false,
            spanGaps: true
          },
          {
            label: 'Students OUT',
            data: studentOut,
            borderColor: chartColors.studentOut,
            backgroundColor: 'rgba(220, 38, 38, 0.12)',
            borderWidth: 2,
            tension: 0.32,
            pointRadius: 0,
            pointHoverRadius: 4,
            fill: false,
            spanGaps: true
          }
        ]
      };

      const options = {
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 10, boxHeight: 10 }
          },
          tooltip: {
            callbacks: {
              title: (items) => {
                if (!items.length) {
                  return '';
                }
                const row = chartHistoryData[items[0].dataIndex];
                if (!row) {
                  return '';
                }
                return isHour ? `Time: ${row.day}` : `Date: ${row.day}`;
              },
              label: (context) => `${context.dataset.label}: ${formatCount(context.parsed.y)}`,
              afterBody: (items) => {
                if (!items.length) {
                  return '';
                }
                const row = chartHistoryData[items[0].dataIndex];
                if (!row) {
                  return '';
                }
                const otherIn = (row.visitor_in || 0) + (row.unknown_in || 0);
                const otherOut = (row.visitor_out || 0) + (row.unknown_out || 0);
                const lines = [
                  'In by role',
                  `Students: ${formatCount(row.student_in)}`,
                  `Faculty: ${formatCount(row.faculty_in)}`,
                  `Staff: ${formatCount(row.staff_in)}`
                ];
                if (otherIn > 0) {
                  lines.push(`Other: ${formatCount(otherIn)}`);
                }
                lines.push(
                  'Out by role',
                  `Students: ${formatCount(row.student_out)}`,
                  `Faculty: ${formatCount(row.faculty_out)}`,
                  `Staff: ${formatCount(row.staff_out)}`
                );
                if (otherOut > 0) {
                  lines.push(`Other: ${formatCount(otherOut)}`);
                }
                return lines;
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              callback: (value, index) => (index % tickStep === 0 ? labels[index] : '')
            },
            border: { display: false }
          },
          y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: 'rgba(148, 163, 184, 0.25)' },
            border: { display: false }
          }
        }
      };

      if (historyChart) {
        historyChart.data = data;
        historyChart.options = options;
        historyChart.update();
        return;
      }
      historyChart = new Chart(historyChartEl, { type: 'line', data, options });
    }

    function renderRoleChart(roleTotals) {
      if (!roleChartEl || !window.Chart) {
        return;
      }
      const roleOrder = ['student', 'faculty', 'staff', 'visitor', 'unknown'];
      const roleLabels = {
        student: 'Students',
        faculty: 'Faculty',
        staff: 'Staff',
        visitor: 'Visitors',
        unknown: 'Unregistered'
      };
      const roleColors = {
        student: chartColors.roleStudent,
        faculty: chartColors.roleFaculty,
        staff: chartColors.roleStaff,
        visitor: chartColors.roleVisitor,
        unknown: chartColors.roleUnknown
      };
      const labels = [];
      const values = [];
      const colors = [];
      roleOrder.forEach(role => {
        labels.push(roleLabels[role]);
        values.push(Number(roleTotals[role] || 0));
        colors.push(roleColors[role]);
      });

      const data = {
        labels,
        datasets: [
          {
            data: values,
            backgroundColor: colors,
            borderWidth: 0
          }
        ]
      };

      const options = {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 10, boxHeight: 10 }
          },
          tooltip: {
            callbacks: {
              label: (context) => `${context.label}: ${formatCount(context.parsed)}`
            }
          }
        },
        cutout: '62%'
      };

      if (roleChart) {
        roleChart.data = data;
        roleChart.options = options;
        roleChart.update();
        return;
      }
      roleChart = new Chart(roleChartEl, { type: 'doughnut', data, options });
    }

    function renderDirectionChart(directionTotals) {
      if (!directionChartEl || !window.Chart) {
        return;
      }
      const inTotal = Number(directionTotals.in || 0);
      const outTotal = Number(directionTotals.out || 0);

      const data = {
        labels: ['IN', 'OUT'],
        datasets: [
          {
            data: [inTotal, outTotal],
            backgroundColor: [chartColors.inTotal, chartColors.outTotal],
            borderWidth: 0
          }
        ]
      };

      const options = {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 10, boxHeight: 10 }
          },
          tooltip: {
            callbacks: {
              label: (context) => `${context.label}: ${formatCount(context.parsed)}`
            }
          }
        },
        cutout: '62%'
      };

      if (directionChart) {
        directionChart.data = data;
        directionChart.options = options;
        directionChart.update();
        return;
      }
      directionChart = new Chart(directionChartEl, { type: 'doughnut', data, options });
    }

    async function loadCharts() {
      if (!historyChartEl) {
        return;
      }
      if (!window.Chart) {
        setChartStatus('Chart library failed to load');
        return;
      }
      setChartStatus('Loading charts...');
      try {
        const mode = historyModeEl && historyModeEl.value === 'hour' ? 'hour' : 'day';
        const adminUid = getAdminFilterValue();
        const url = new URL('../api/scans/get_scan_history.php', window.location.href);
        url.searchParams.set('mode', mode);
        if (adminUid) {
          url.searchParams.set('admin_uid', adminUid);
        }
        const res = await fetch(url.toString());
        if (!res.ok) {
          setChartStatus('Unable to load chart data');
          return;
        }
        const data = await res.json();
        if (!data.ok) {
          setChartStatus('Unable to load chart data');
          return;
        }
        const filledHistory = fillHistory(Array.isArray(data.history) ? data.history : [], mode);
        if (filledHistory.length === 0) {
          setChartStatus('No scan history yet');
          return;
        }
        setChartStatus('');
        renderHistoryChart(filledHistory, mode);
        renderRoleChart(data.role_totals || {});
        renderDirectionChart(data.direction_totals || {});
      } catch (err) {
        setChartStatus('Unable to load chart data');
      }
    }

    function setActiveTab(tabId) {
      tabContents.forEach(tab => tab.classList.remove('active'));
      tabButtons.forEach(btn => btn.classList.remove('active'));
      const activeTab = document.getElementById(tabId);
      if (activeTab) {
        activeTab.classList.add('active');
      }
      tabButtons.forEach(btn => {
        if (btn.dataset.tab === tabId) {
          btn.classList.add('active');
        }
      });
      if (activeTab) {
        activeTab.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      if (tabId === 'reportsTab') {
        loadCharts();
        loadStorageStats();
      }
    }

    function getAdminFilterValue() {
      return adminFilterEl ? adminFilterEl.value : '';
    }

    function getPersonalAdminFilterValue() {
      return personalAdminFilterEl ? personalAdminFilterEl.value : '';
    }

    function getReportAdminFilterValue() {
      return reportAdminFilterEl ? reportAdminFilterEl.value : '';
    }

    function syncAdminFilters(value) {
      if (adminFilterEl && adminFilterEl.value !== value) {
        adminFilterEl.value = value;
      }
    }

    function renderUserAdminTags(tags) {
      if (!personalUserAdminTagsEl) {
        return;
      }
      personalUserAdminTagsEl.innerHTML = '';
      if (!tags || tags.length === 0) {
        const emptyTag = document.createElement('span');
        emptyTag.className = 'tag muted';
        emptyTag.textContent = 'No admin tags';
        personalUserAdminTagsEl.appendChild(emptyTag);
        return;
      }
      tags.forEach(tag => {
        const span = document.createElement('span');
        span.className = 'tag';
        span.textContent = tag;
        personalUserAdminTagsEl.appendChild(span);
      });
    }

    function openRegister(uid, scannedAt) {
      showUnregistered(uid, scannedAt);
    }

    function showPrompt() {
      scanPromptEl.classList.remove('hidden');
      registeredViewEl.classList.add('hidden');
      registerFormWrapEl.classList.add('hidden');
      registerStatusEl.textContent = '';
    }

    function showRegistered(user) {
      scanPromptEl.classList.add('hidden');
      registeredViewEl.classList.remove('hidden');
      registerFormWrapEl.classList.add('hidden');
      currentRegisteredUser = user;
      updateRoleFields(user.role || 'student');
      viewUidEl.value = user.uid || '';
      viewNameEl.value = user.name || '';
      viewStudentIdEl.value = user.student_id || '';
      viewCourseEl.value = user.course || '';
      viewSchoolYearEl.value = user.school_year || '';
      viewSectionEl.value = user.section || '';
      viewFacultyIdEl.value = user.faculty_id || '';
      viewStaffIdEl.value = user.staff_id || '';
      viewDepartmentEl.value = user.department || '';
      viewPurposeEl.value = user.purpose || '';
      viewValidUntilEl.value = user.valid_until || '';
      viewPhoneEl.value = user.phone || '';
      viewEmailEl.value = user.email || '';
      viewRoleEl.value = user.role || '';
      viewPhotoEl.src = getProfilePhotoSrc(user.photo);
      registerStatusEl.textContent = '';
    }

    function showUnregistered(uid, scannedAt) {
      scanPromptEl.classList.add('hidden');
      registeredViewEl.classList.add('hidden');
      registerFormWrapEl.classList.remove('hidden');
      currentRegisteredUser = null;
      regUidEl.value = uid || '';
      regUidEl.readOnly = true;
      updateRoleFields(document.getElementById('regRole').value || 'student');
      registerStatusEl.textContent = '';
      setActiveTab('registerTab');
    }

    function showEditFormFromUser(user) {
      if (!user) {
        return;
      }
      scanPromptEl.classList.add('hidden');
      registeredViewEl.classList.add('hidden');
      registerFormWrapEl.classList.remove('hidden');
      regUidEl.value = user.uid || '';
      regUidEl.readOnly = true;
      document.getElementById('regName').value = user.name || '';
      document.getElementById('regStudentId').value = user.student_id || '';
      document.getElementById('regCourse').value = user.course || '';
      document.getElementById('regSchoolYear').value = user.school_year || '';
      document.getElementById('regSection').value = user.section || '';
      document.getElementById('regFacultyId').value = user.faculty_id || '';
      document.getElementById('regStaffId').value = user.staff_id || '';
      document.getElementById('regDepartment').value = user.department || '';
      document.getElementById('regPurpose').value = user.purpose || '';
      document.getElementById('regValidUntil').value = user.valid_until || '';
      document.getElementById('regPhone').value = user.phone || '';
      document.getElementById('regEmail').value = user.email || '';
      document.getElementById('regRole').value = user.role || 'student';
      updateRoleFields(user.role || 'student');
      registerStatusEl.textContent = 'Editing registered student';
      regPhotoPreviewEl.src = getProfilePhotoSrc(user.photo);
      setActiveTab('registerTab');
    }

    function updateRoleFields(role) {
      const roles = ['student', 'faculty', 'staff', 'visitor'];
      roles.forEach(item => {
        document.querySelectorAll(`.role-${item}`).forEach(el => {
          el.classList.add('hidden');
        });
      });
      document.querySelectorAll(`.role-${role}`).forEach(el => {
        el.classList.remove('hidden');
      });
      regStudentIdEl.required = role === 'student';
      regFacultyIdEl.required = role === 'faculty';
      regStaffIdEl.required = role === 'staff';
    }

    async function loadUserDetails(uid) {
      if (!uid) {
        return;
      }
      try {
        const res = await fetch(`../api/users/get_user.php?uid=${encodeURIComponent(uid)}`);
        const data = await res.json();
        if (data.ok && data.data) {
          showRegistered(data.data);
        } else {
          showUnregistered(uid, '');
        }
      } catch (err) {
        showUnregistered(uid, '');
      }
    }

    async function loadUsers() {
      if (!personalUserSelectEl || !personalUserLogsEl) {
        return;
      }
      try {
        const adminUid = getPersonalAdminFilterValue();
        const usersUrl = new URL('../api/users/get_users.php', window.location.href);
        if (adminUid) usersUrl.searchParams.set('admin_uid', adminUid);

        const adminUsersUrl = new URL('../api/admin/get_admin_users.php', window.location.href);
        if (adminUid) adminUsersUrl.searchParams.set('admin_uid', adminUid);

        const [usersRes, adminUsersRes] = await Promise.all([
          fetch(usersUrl.toString()),
          fetch(adminUsersUrl.toString())
        ]);

        if (!usersRes.ok || !adminUsersRes.ok) {
          throw new Error('Failed to load user lists');
        }

        const data = await usersRes.json();
        const adminUsersData = await adminUsersRes.json();
        if (!data.ok) {
          return;
        }

        adminTagsByUid = {};
        if (adminUsersData.ok && Array.isArray(adminUsersData.data)) {
          adminUsersData.data.forEach(row => {
            if (!row.uid) {
              return;
            }
            if (!adminTagsByUid[row.uid]) {
              adminTagsByUid[row.uid] = new Set();
            }
            const adminLabel = row.admin_name ? row.admin_name : (row.admin_uid ? row.admin_uid : '');
            if (adminLabel) {
              adminTagsByUid[row.uid].add(adminLabel);
            }
          });
        }

        personalUserSelectEl.innerHTML = '';
        data.data.forEach(user => {
          let idLabel = '';
          if (user.role === 'student' && user.student_id) {
            idLabel = user.student_id;
          } else if (user.role === 'faculty' && user.faculty_id) {
            idLabel = user.faculty_id;
          } else if (user.role === 'staff' && user.staff_id) {
            idLabel = user.staff_id;
          } else if (user.role === 'visitor' && user.purpose) {
            idLabel = user.purpose;
          }
          let label = idLabel ? `${user.name} (${idLabel})` : user.name;
          if (adminTagsByUid[user.uid]) {
            const tags = Array.from(adminTagsByUid[user.uid]);
            if (tags.length > 0) {
              label += ` â€” ${tags.join(' Â· ')}`;
            }
          }
          const option = document.createElement('option');
          option.value = user.uid;
          option.textContent = label;
          personalUserSelectEl.appendChild(option);
        });
        if (data.data.length > 0) {
          loadUserLogs(personalUserSelectEl.value);
        } else {
          personalUserLogsEl.innerHTML = '<tr><td colspan="3">No registered people</td></tr>';
          renderUserAdminTags([]);
        }
      } catch (err) {
        personalUserLogsEl.innerHTML = '<tr><td colspan="3">Unable to load users</td></tr>';
        renderUserAdminTags([]);
      }
    }

    async function loadUserLogs(uid) {
      if (!personalUserLogsEl) {
        return;
      }
      if (!uid) {
        return;
      }
      try {
        const adminUid = getPersonalAdminFilterValue();
        const url = new URL('../api/users/get_personal_activity.php', window.location.href);
        url.searchParams.set('uid', uid);
        url.searchParams.set('limit', '200');
        if (adminUid) {
          url.searchParams.set('admin_uid', adminUid);
        }
        const res = await fetch(url.toString());
        if (!res.ok) {
          personalUserLogsEl.innerHTML = '<tr><td colspan="4">Server error</td></tr>';
          console.error('get_personal_activity.php returned HTTP', res.status, await res.text());
          renderUserAdminTags([]);
          return;
        }
        let data;
        try {
          data = await res.json();
        } catch (e) {
          personalUserLogsEl.innerHTML = '<tr><td colspan="4">Invalid server response</td></tr>';
          console.error('Failed to parse JSON from get_personal_activity.php:', e, await res.text());
          renderUserAdminTags([]);
          return;
        }
        if (!data.ok) {
          personalUserLogsEl.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
          renderUserAdminTags([]);
          return;
        }
        if (data.data.length === 0) {
          personalUserLogsEl.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
          renderUserAdminTags([]);
          return;
        }
        let html = '';
        const adminSet = new Set();
        data.data.forEach(row => {
          const adminDisplay = row.admin_name ? row.admin_name : (row.admin_uid ? row.admin_uid : '');
          if (adminDisplay) {
            adminSet.add(adminDisplay);
          }
          html += `<tr>
            <td>${row.id}</td>
            <td>${row.direction}</td>
            <td>${adminDisplay}</td>
            <td>${row.created_at}</td>
          </tr>`;
        });
        personalUserLogsEl.innerHTML = html;
        renderUserAdminTags(Array.from(adminSet));
      } catch (err) {
        personalUserLogsEl.innerHTML = '<tr><td colspan="4">Unable to load logs</td></tr>';
        renderUserAdminTags([]);
      }
    }

    function hideIdCard() {
      if (idCardDisplayEl) {
        idCardDisplayEl.classList.add('hidden');
        idCardDisplayEl.querySelector('.id-card').classList.remove('highlight');
      }
      if (idCardPlaceholderEl) {
        idCardPlaceholderEl.classList.remove('hidden');
      }
    }

    async function showIdCardForUser(uid) {
      if (!idCardDisplayEl || !idCardPlaceholderEl) return;

      try {
        const res = await fetch(`../api/users/get_user.php?uid=${encodeURIComponent(uid)}`);
        const data = await res.json();

        if (data.ok && data.data) {
          const user = data.data;
          
          idCardPhotoEl.src = getProfilePhotoSrc(user.photo);
          
           idCardNameEl.textContent = (user.name || 'Unknown').toUpperCase();

          idCardRoleEl.textContent = (user.role || '').charAt(0).toUpperCase() + (user.role || '').slice(1);

          let idString = '';
          if (user.role === 'student') idString = `ID: ${user.student_id || 'N/A'}`;
          else if (user.role === 'faculty') idString = `ID: ${user.faculty_id || 'N/A'}`;
          else if (user.role === 'staff') idString = `ID: ${user.staff_id || 'N/A'}`;
          idCardIdEl.textContent = idString;

          idCardPlaceholderEl.classList.add('hidden');
          idCardDisplayEl.classList.remove('hidden');
          const cardEl = idCardDisplayEl.querySelector('.id-card');
          cardEl.classList.add('highlight');

          cardEl.classList.remove('id-card-popup');
          void cardEl.offsetWidth;
          cardEl.classList.add('id-card-popup');
          cardEl.addEventListener('animationend', () => {
            cardEl.classList.remove('id-card-popup');
          }, { once: true });

          // TEMPORARY: disable auto-hide for layout editing
          // if (idCardTimeout) clearTimeout(idCardTimeout);
          // setTimeout(() => cardEl.classList.remove('highlight'), 4000);
          // idCardTimeout = setTimeout(hideIdCard, 12000);
        } else {
          // TEMPORARY: disable auto-hide for layout editing
          // hideIdCard();
        }
      } catch (err) {
        console.error("Failed to fetch user for ID card", err);
        // TEMPORARY: disable auto-hide for layout editing
        // hideIdCard();
      }
    }

    function closeUserProfile() {
      if (!userProfileModalEl) {
        return;
      }
      profileRequestToken += 1;
      userProfileModalEl.classList.add('hidden');
    }

    function getProfilePhotoSrc(photo) {
      if (!photo || !/^[A-Za-z0-9_\-\.]+\.(jpg|jpeg|png|gif|webp)$/i.test(photo)) {
        return '/server/School_Entrance_Monitoring_System/image/nophoto_s.png';
      }
      return '/server/School_Entrance_Monitoring_System/uploads/' + encodeURIComponent(photo);
    }

    function renderProfileDetails(user) {
      if (!profileDetailsEl) {
        return;
      }
      const role = String(user.role || 'unknown').toLowerCase();
      const details = [
        ['UID', user.uid],
        ['Name', user.name],
        ['Role', role],
      ];
      if (role === 'student') {
        details.push(['Student ID', user.student_id]);
        details.push(['Course', user.course]);
        details.push(['School Year', user.school_year]);
        details.push(['Section', user.section]);
      } else if (role === 'faculty') {
        details.push(['Faculty ID', user.faculty_id]);
        details.push(['Department', user.department]);
      } else if (role === 'staff') {
        details.push(['Staff ID', user.staff_id]);
        details.push(['Department', user.department]);
      } else if (role === 'visitor') {
        details.push(['Purpose', user.purpose]);
        details.push(['Valid Until', user.valid_until]);
      }
      details.push(['Phone', user.phone]);
      details.push(['Email', user.email]);
      details.push(['Notes', user.notes]);

      profileDetailsEl.innerHTML = '';
      details.forEach(([label, value]) => {
        if (!value) {
          return;
        }
        const item = document.createElement('div');
        item.className = 'profile-detail';
        const labelEl = document.createElement('span');
        labelEl.textContent = label;
        const valueEl = document.createElement('strong');
        valueEl.textContent = value;
        item.appendChild(labelEl);
        item.appendChild(valueEl);
        profileDetailsEl.appendChild(item);
      });
    }

    function renderProfileHistory(rows, historyError = false) {
      if (!profileHistoryRowsEl) {
        return;
      }
      const historyRows = Array.isArray(rows) ? rows : [];
      const inCount = historyRows.filter(row => String(row.direction).toUpperCase() === 'IN').length;
      const outCount = historyRows.filter(row => String(row.direction).toUpperCase() === 'OUT').length;
      if (profileTotalScansEl) profileTotalScansEl.textContent = formatCount(historyRows.length);
      if (profileInScansEl) profileInScansEl.textContent = formatCount(inCount);
      if (profileOutScansEl) profileOutScansEl.textContent = formatCount(outCount);
      if (profileHistoryCountEl) {
        profileHistoryCountEl.textContent = historyError ? 'History unavailable' : `${formatCount(historyRows.length)} scans`;
      }

      profileHistoryRowsEl.innerHTML = '';
      if (historyRows.length === 0) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 5;
        cell.textContent = historyError ? 'Unable to load scan history' : 'No scan history found';
        row.appendChild(cell);
        profileHistoryRowsEl.appendChild(row);
        return;
      }

      historyRows.forEach(row => {
        const tr = document.createElement('tr');
        const idCell = document.createElement('td');
        idCell.textContent = row.id || '-';
        const directionCell = document.createElement('td');
        const direction = String(row.direction || '-').toUpperCase();
        directionCell.textContent = direction;
        directionCell.className = direction === 'IN' ? 'profile-direction-in' : direction === 'OUT' ? 'profile-direction-out' : '';
        const adminCell = document.createElement('td');
        adminCell.textContent = row.admin_name || row.admin_uid || '-';
        const statusCell = document.createElement('td');
        const status = document.createElement('span');
        status.className = row.suspicious == 1 ? 'badge danger' : 'badge info';
        status.textContent = row.suspicious == 1 ? 'Suspicious' : 'Normal';
        statusCell.appendChild(status);
        const timeCell = document.createElement('td');
        timeCell.textContent = row.created_at || '-';
        tr.appendChild(idCell);
        tr.appendChild(directionCell);
        tr.appendChild(adminCell);
        tr.appendChild(statusCell);
        tr.appendChild(timeCell);
        profileHistoryRowsEl.appendChild(tr);
      });
    }

    async function openUserProfile(uid) {
      if (!uid || !userProfileModalEl || !profileLoadingEl || !profileContentEl) {
        return;
      }
      const requestToken = ++profileRequestToken;
      userProfileModalEl.classList.remove('hidden');
      profileContentEl.classList.add('hidden');
      profileLoadingEl.classList.remove('hidden');
      profileLoadingEl.textContent = 'Loading profile...';
      try {
        const userRes = await fetch(`../api/users/get_user.php?uid=${encodeURIComponent(uid)}`);
        if (requestToken !== profileRequestToken) {
          return;
        }
        if (!userRes.ok) {
          throw new Error(`User request failed with HTTP ${userRes.status}`);
        }
        const userData = await userRes.json();
        if (requestToken !== profileRequestToken) {
          return;
        }
        if (!userData.ok || !userData.data) {
          throw new Error(userData.error || 'User not found');
        }

        const user = userData.data;
        let historyRows = [];
        let historyError = false;
        try {
          const historyRes = await fetch(`../api/users/get_personal_activity.php?uid=${encodeURIComponent(uid)}&limit=500`);
          if (historyRes.ok) {
            const historyData = await historyRes.json();
            if (historyData.ok && Array.isArray(historyData.data)) {
              historyRows = historyData.data;
            } else {
              historyError = true;
            }
          } else {
            historyError = true;
          }
        } catch (err) {
          historyError = true;
          console.error('Failed to load profile history:', err);
        }
        if (requestToken !== profileRequestToken) {
          return;
        }
        const role = String(user.role || 'unknown').toLowerCase();
        const roleLabel = role.charAt(0).toUpperCase() + role.slice(1);
        const roleClass = ['student', 'faculty', 'staff', 'visitor', 'admin'].includes(role) ? role : 'unknown';
        const identifier = role === 'admin'
          ? `Admin UID: ${user.uid || '-'}`
          : role === 'student' && user.student_id
          ? `Student ID: ${user.student_id}`
          : role === 'faculty' && user.faculty_id
            ? `Faculty ID: ${user.faculty_id}`
            : role === 'staff' && user.staff_id
              ? `Staff ID: ${user.staff_id}`
              : role === 'visitor' && user.purpose
                ? user.purpose
                : `UID: ${user.uid || '-'}`;

        profilePhotoEl.src = getProfilePhotoSrc(user.photo);
        profilePhotoEl.alt = `${user.name || 'User'} profile photo`;
        profileRoleEl.className = `role-badge role-${roleClass}`;
        profileRoleEl.textContent = roleLabel;
        profileNameEl.textContent = user.name || 'Unknown';
        profileIdentifierEl.textContent = identifier;
        renderProfileDetails(user);
        renderProfileHistory(historyRows, historyError);
        profileLoadingEl.classList.add('hidden');
        profileContentEl.classList.remove('hidden');
      } catch (err) {
        if (requestToken !== profileRequestToken) {
          return;
        }
        profileLoadingEl.textContent = 'Unable to load profile';
        profileContentEl.classList.add('hidden');
        console.error('Failed to load user profile:', err);
      }
    }

    async function loadScans() {
      const adminUid = getAdminFilterValue();
      const suspiciousOnly = suspiciousOnlyEl && suspiciousOnlyEl.checked ? 1 : 0;
      try {
        const url = new URL('../api/scans/get_scans.php', window.location.href);
        url.searchParams.set('limit', '30');
        if (adminUid) url.searchParams.set('admin_uid', adminUid);
        if (suspiciousOnly) url.searchParams.set('suspicious', '1');
        const res = await fetch(url.toString());
        if (!res.ok) {
          statusEl.textContent = `Server error ${res.status}`;
          console.error('get_scans.php returned HTTP', res.status, await res.text());
          return;
        }
        let data;
        try {
          data = await res.json();
        } catch (e) {
          statusEl.textContent = 'Invalid response from server';
          console.error('Failed to parse JSON from get_scans.php:', e, await res.text());
          return;
        }
        if (!data.ok) {
          statusEl.textContent = 'Error loading data';
          console.error('get_scans.php responded with ok=false', data);
          return;
        }

        setStats(data.stats);

        let newHtml = '';
        let newestScan = null;
        data.data.forEach(row => {
          const name = row.name ? row.name : 'Unknown';
          const dir = row.direction ? row.direction : '-';
          if (lastScanId > 0 && row.id > lastScanId && !newestScan) {
            newestScan = row;
          }
          const uid = row.uid || '';
          const createdAt = row.created_at || '';
          const adminDisplay = row.admin_name ? row.admin_name : (row.admin_uid ? row.admin_uid : '');
          const deptDisplay = row.department ? row.department : '';
          const suspiciousBadge = row.suspicious == 1 ? '<span class="suspicious-badge">âš </span>' : '';
          const trClass = row.suspicious == 1 ? 'class="suspicious-row"' : '';
          const profileAttribute = name !== 'New User' && uid ? ` data-profile-uid="${escapeHtml(uid)}"` : '';
          let userDisplay = name === 'New User'
            ? `<button type="button" class="scan-register-btn" data-register-uid="${escapeHtml(uid)}" data-register-time="${escapeHtml(createdAt)}">Register</button>`
            : `<button type="button" class="scan-user-btn"${profileAttribute} title="View profile and history" aria-label="View ${escapeHtml(name)} profile">${escapeHtml(name)}</button>`;

          // add data attributes so we can target the newest scan row for animation
          newHtml += `<tr ${trClass}${profileAttribute} data-scan-id="${escapeHtml(row.id)}" data-direction="${escapeHtml(dir)}">
            <td>${userDisplay}${suspiciousBadge}</td>
            <td>${escapeHtml(dir)}</td>
            <td>${escapeHtml(uid)}</td>
            <td>${escapeHtml(deptDisplay)}</td>
            <td>${escapeHtml(adminDisplay)}</td>
            <td>${escapeHtml(createdAt)}</td>
          </tr>`;
        });

        // Update lastScanId to the highest ID we just loaded
        if (data.data.length > 0) {
          const maxId = Math.max(...data.data.map(r => parseInt(r.id)));
          if (maxId > lastScanId) {
            lastScanId = maxId;
          }
        }

        rowsEl.innerHTML = newHtml;
        statusEl.textContent = `Last update: ${new Date().toLocaleTimeString()}`;

        // highlight the newest scan row with a glow animation
        if (newestScan) {
          // show unregistered modal if needed
          if (newestScan.name === 'New User' || !newestScan.name) {
            hideIdCard();
            setTimeout(() => showUnregistered(newestScan.uid, newestScan.created_at), 100);
          } else {
            showIdCardForUser(newestScan.uid);
          }

          // animate the corresponding table row after DOM update
          setTimeout(() => {
            try {
              const selector = `tr[data-scan-id="${newestScan.id}"]`;
              const el = rowsEl.querySelector(selector);
              if (el) {
                const dir = (newestScan.direction || '').toLowerCase();
                const cls = dir === 'in' ? 'scan-highlight-in' : 'scan-highlight-out';
                el.classList.add(cls);
                // ensure the row is visible in the panel
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                el.addEventListener('animationend', () => el.classList.remove(cls), { once: true });
              }
            } catch (e) {
              // ignore
            }
          }, 80);
        }
      } catch (err) {
        statusEl.textContent = 'Network error';
      }
    }
    async function loadAdmins() {
      if (!adminFilterEl) return;
      try {
        const res = await fetch('../api/admin/get_admins.php');
        if (!res.ok) {
          return;
        }
        const data = await res.json();
        if (!data.ok) return;
        const currentValue = adminFilterEl ? adminFilterEl.value : '';

        if (adminFilterEl) {
          adminFilterEl.innerHTML = '<option value="">All Admins</option>';
        }
        if (dailyAdminFilterEl) {
          dailyAdminFilterEl.innerHTML = '<option value="">All Admins</option>';
        }

        data.data.forEach(a => {
          if (adminFilterEl) {
            const opt = document.createElement('option');
            opt.value = a.uid;
            opt.textContent = a.name;
            adminFilterEl.appendChild(opt);
          }
          if (dailyAdminFilterEl) {
            const optDaily = document.createElement('option');
            optDaily.value = a.uid;
            optDaily.textContent = a.name;
            dailyAdminFilterEl.appendChild(optDaily);
          }
        });

        if (currentValue) {
          syncAdminFilters(currentValue);
        }
        if (dailyAdminFilterEl && currentValue && !dailyAdminFilterEl.value) {
          dailyAdminFilterEl.value = currentValue;
        }
        refreshDailyPreview();
      } catch (err) {
        // ignore
      }
    }

    async function loadSuspicious() {
      try {
        const adminUid = getReportAdminFilterValue();
        const url = new URL('../api/scans/get_suspicious.php', window.location.href);
        url.searchParams.set('limit', '200');
        if (adminUid) url.searchParams.set('admin_uid', adminUid);
        const res = await fetch(url.toString());
        if (!res.ok) {
          return;
        }
        const data = await res.json();
        if (!data.ok) return;
        if (suspiciousCountEl) {
          suspiciousCountEl.textContent = data.data.length;
          if (suspiciousMetaEl) {
            suspiciousMetaEl.textContent = data.data.length > 0 ? 'Needs review' : 'All clear';
          }
        }
        const el = document.getElementById('suspiciousRows');
        if (!el) return;
        if (data.data.length === 0) {
          el.innerHTML = '<tr><td colspan="7">No suspicious activity</td></tr>';
          return;
        }
        let html = '';
        data.data.forEach(row => {
          const adminDisplay = row.admin_name ? row.admin_name : (row.admin_uid ? row.admin_uid : '');
          const name = row.name ? row.name : 'Unknown';
          const role = row.role ? row.role.charAt(0).toUpperCase() + row.role.slice(1) : '-';
          html += `<tr>
            <td>${row.id}</td>
            <td><b>${name}</b></td>
            <td><span class="role-badge role-${role.toLowerCase()}">${role}</span></td>
            <td>${row.direction}</td>
            <td><b>${adminDisplay}</b></td>
            <td>${row.prev_created_at}</td>
            <td>${row.created_at}</td>
          </tr>`;
        });
        el.innerHTML = html;
      } catch (err) {
        // ignore
      }
    }

    async function pollRegisterSignal() {
      try {
        const res = await fetch('../api/signals/get_register_signal.php?consume=1');
        const data = await res.json();
        if (!data.ok || !data.data || !data.data.uid) {
          return;
        }
        const signal = data.data;
        const signalMs = signal.ts ? signal.ts * 1000 : parseSignalTime(signal.created_at || '');
        if (Number.isFinite(signalMs) && signalMs < registerSignalStartMs - 5000) {
          return;
        }
        showUnregistered(signal.uid, signal.created_at || '');
      } catch (err) {
        // ignore
      }
    }

    async function pollAdminSignal() {
      try {
        const res = await fetch('../api/admin/get_admin_scan_signal.php?consume=1');
        const data = await res.json();
        if (!data.ok || !data.data || !data.data.uid) {
          return;
        }
        const signal = data.data;
        const signalMs = signal.ts ? signal.ts * 1000 : parseSignalTime(signal.created_at || '');
        if (Number.isFinite(signalMs) && signalMs < Date.now() - 10000) {
          return;
        }
        statusEl.textContent = `Admin scan detected: ${signal.name || signal.uid}`;
        setTimeout(() => { if (statusEl) statusEl.textContent = ''; }, 4000);
        loadScans();
      } catch (err) {
        // ignore
      }
    }

    const navControls = document.querySelectorAll('[data-tab], [data-action], [data-href]');
    navControls.forEach(btn => {
      btn.addEventListener('click', () => {
        if (btn.dataset.href) {
          window.location.href = btn.dataset.href;
          return;
        }
        if (btn.dataset.action === 'scanLog') {
          focusScanLogPanel();
          return;
        }
        if (btn.dataset.tab) {
          setActiveTab(btn.dataset.tab);
        }
      });
    });

    if (rowsEl) {
      rowsEl.addEventListener('click', (event) => {
        const profileButton = event.target.closest('[data-profile-uid]');
        if (profileButton) {
          openUserProfile(profileButton.dataset.profileUid);
          return;
        }
        const registerButton = event.target.closest('[data-register-uid]');
        if (registerButton) {
          openRegister(registerButton.dataset.registerUid, registerButton.dataset.registerTime);
        }
      });
    }

    if (closeProfileBtn) {
      closeProfileBtn.addEventListener('click', closeUserProfile);
    }
    document.querySelectorAll('[data-close-profile]').forEach(el => {
      el.addEventListener('click', closeUserProfile);
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeUserProfile();
      }
      if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[data-tab][role="button"]')) {
        event.preventDefault();
        setActiveTab(event.target.dataset.tab);
      }
    });

    if (personalUserSelectEl) {
      personalUserSelectEl.addEventListener('change', () => {
        loadUserLogs(personalUserSelectEl.value);
      });
    }

    if (refreshStorageBtn) {
      refreshStorageBtn.addEventListener('click', loadStorageStats);
    }
    if (historyModeEl) {
      historyModeEl.addEventListener('change', () => {
        loadCharts();
      });
    }

    registerFormEl.addEventListener('submit', async (event) => {
      event.preventDefault();
      registerStatusEl.textContent = 'Saving...';

      const formData = new FormData(registerFormEl);
      const photoFile = formData.get('photo');
      if (photoFile && photoFile.size > 0) {
        resizePhotoTo360(photoFile, function(blob) {
          const resizedFile = new File([blob], photoFile.name, { type: 'image/jpeg' });
          formData.set('photo', resizedFile);
          submitRegistration(formData);
        });
      } else {
        formData.delete('photo');
        submitRegistration(formData);
      }
    });

    function submitRegistration(formData) {
      fetch('../api/users/register_user.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.ok) {
          registerStatusEl.textContent = 'Saved';
          const savedUid = regUidEl.value;
          registerFormEl.reset();
          regPhotoPreviewEl.src = '/server/School_Entrance_Monitoring_System/image/nophoto_s.png';
          regUidEl.readOnly = false;
          loadUsers();
          loadScans();
          loadUserDetails(savedUid);
        } else {
          registerStatusEl.textContent = data.error || 'Save failed';
        }
      })
      .catch(err => {
        registerStatusEl.textContent = 'Network error';
      });
    }

    document.getElementById('regRole').addEventListener('change', (event) => {
      updateRoleFields(event.target.value);
    });

    if (regPhotoEl && regPhotoPreviewEl) {
      regPhotoEl.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
          resizePhotoTo360(file, function(blob) {
            const url = URL.createObjectURL(blob);
            regPhotoPreviewEl.src = url;
          });
        } else {
          regPhotoPreviewEl.src = '/server/School_Entrance_Monitoring_System/image/nophoto_s.png';
        }
      });
    }

    editRegisteredEl.addEventListener('click', () => {
      showEditFormFromUser(currentRegisteredUser);
    });

    async function loadPersonalAdmins() {
      if (!personalAdminFilterEl) {
        return;
      }
      try {
        const res = await fetch('../api/admin/get_admins.php');
        const data = await res.json();
        if (!data.ok) {
          return;
        }
        const currentValue = personalAdminFilterEl.value;
        personalAdminFilterEl.innerHTML = '<option value="">All Admins</option>';
        data.data.forEach(admin => {
          const option = document.createElement('option');
          option.value = admin.uid;
          option.textContent = admin.name;
          personalAdminFilterEl.appendChild(option);
        });
        if (currentValue) {
          personalAdminFilterEl.value = currentValue;
        }
      } catch (err) {
        // ignore
      }
    }

    async function loadReportAdmins() {
      if (!reportAdminFilterEl) {
        return;
      }
      try {
        const res = await fetch('../api/admin/get_admins.php');
        const data = await res.json();
        if (!data.ok) {
          return;
        }
        const currentValue = reportAdminFilterEl.value;
        reportAdminFilterEl.innerHTML = '<option value="">All Admins</option>';
        data.data.forEach(admin => {
          const option = document.createElement('option');
          option.value = admin.uid;
          option.textContent = admin.name;
          reportAdminFilterEl.appendChild(option);
        });
        if (currentValue) {
          reportAdminFilterEl.value = currentValue;
        }
      } catch (err) {
        // ignore
      }
    }

    async function loadAdminStats() {
      if (!adminStatsGridEl) {
        return;
      }
      try {
        const adminUid = getReportAdminFilterValue();
        const url = new URL('../api/admin/get_admin_stats.php', window.location.href);
        if (adminUid) {
          url.searchParams.set('admin_uid', adminUid);
        }
        const res = await fetch(url.toString());
        if (!res.ok) {
          adminStatsGridEl.innerHTML = '<div class="card"><h3>Stats Unavailable</h3></div>';
          return;
        }
        const data = await res.json();
        if (!data.ok) {
          adminStatsGridEl.innerHTML = '<div class="card"><h3>Stats Unavailable</h3></div>';
          return;
        }
        if (!data.data || data.data.length === 0) {
          adminStatsGridEl.innerHTML = '<div class="card"><h3>No Admin Activity</h3><p class="sub">No scans recorded yet.</p></div>';
          return;
        }
        let html = '';
        data.data.forEach(admin => {
          const total = Number(admin.total_scans || 0);
          const inCount = Number(admin.in_count || 0);
          const outCount = Number(admin.out_count || 0);
          const unique = Number(admin.unique_users || 0);
          const suspicious = Number(admin.suspicious_count || 0);
          html += `
            <div class="card">
              <h3>${admin.admin_name || 'Unknown'}</h3>
              <div class="value">${total}</div>
              <div class="meta">Total Scans</div>
              <div class="meta" style="margin-top:4px;">In: ${inCount} | Out: ${outCount}</div>
              <div class="meta">Unique Users: ${unique}</div>
              <div class="meta" style="color: var(--danger-color);">Suspicious: ${suspicious}</div>
            </div>`;
        });
        adminStatsGridEl.innerHTML = html;
      } catch (err) {
        adminStatsGridEl.innerHTML = '<div class="card"><h3>Stats Error</h3></div>';
      }
    }

     // Initial load
    if (personalUserSelectEl && personalUserLogsEl) {
      loadUsers();
    }
    loadPersonalAdmins();
    loadAdmins();
    loadReportAdmins();
    loadAdminStats();
    loadStorageStats();
    loadScans();
    loadSuspicious();
    showPrompt();
    pollRegisterSignal();
    if (dailyReportDateEl) {
      dailyReportDateEl.value = formatLocalDateInput(new Date());
      dailyReportDateEl.addEventListener('change', refreshDailyPreview);
    }
    if (dailyAdminFilterEl) {
      dailyAdminFilterEl.addEventListener('change', refreshDailyPreview);
    }
    if (dailySuspiciousOnlyEl) {
      dailySuspiciousOnlyEl.addEventListener('change', refreshDailyPreview);
    }
    if (adminFilterEl) {
      adminFilterEl.addEventListener('change', () => {
        syncAdminFilters(adminFilterEl.value);
        loadScans();
        loadSuspicious();
        loadCharts();
      });
    }
    if (personalAdminFilterEl) {
      personalAdminFilterEl.addEventListener('change', () => {
        loadUsers();
        loadUserLogs(personalUserSelectEl ? personalUserSelectEl.value : '');
      });
    }
    if (reportAdminFilterEl) {
      reportAdminFilterEl.addEventListener('change', () => {
        loadAdminStats();
        loadSuspicious();
      });
    }
    if (dailyPrintBtn) {
      dailyPrintBtn.addEventListener('click', () => openDailyReport('print'));
    }
    if (dailyCsvBtn) {
      dailyCsvBtn.addEventListener('click', () => openDailyReport('csv'));
    }
    if (dailyXlsBtn) {
      dailyXlsBtn.addEventListener('click', () => openDailyReport('xls'));
    }
    refreshDailyPreview();

    const dailyLogSearchInputEl = document.getElementById('dailyLogSearchInput');
    const dailyLogSearchRoleEl = document.getElementById('dailyLogSearchRole');
    const dailyLogSearchBtnEl = document.getElementById('dailyLogSearchBtn');
    const dailyLogSearchResultsEl = document.getElementById('dailyLogSearchResults');
    const personPreviewPanelEl = document.getElementById('personPreviewPanel');
    const previewCloseBtnEl = document.getElementById('previewCloseBtn');
    let searchDebounceTimer = null;

    function getInitials(name) {
      if (!name) return '?';
      const parts = name.trim().split(/\s+/);
      if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
      return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }

    function getRoleLabel(role) {
      const labels = { student: 'Student', faculty: 'Faculty', staff: 'Staff', visitor: 'Visitor', admin: 'Admin' };
      return labels[role] || role || '-';
    }

    function getRoleBadgeClass(role) {
      return `role-badge role-${role || 'unknown'}`;
    }

    function highlightMatch(text, query) {
      if (!query || !text) return escapeHtml(text);
      const escaped = escapeHtml(text);
      const qEscaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const regex = new RegExp(`(${qEscaped})`, 'gi');
      return escaped.replace(regex, '<span class="search-highlight">$1</span>');
    }

    async function performDailyLogSearch() {
      const query = dailyLogSearchInputEl ? dailyLogSearchInputEl.value.trim() : '';
      const role = dailyLogSearchRoleEl ? dailyLogSearchRoleEl.value : '';

      if (!query) {
        dailyLogSearchResultsEl.classList.add('hidden');
        dailyLogSearchResultsEl.innerHTML = '';
        return;
      }

      dailyLogSearchResultsEl.classList.remove('hidden');
      dailyLogSearchResultsEl.innerHTML = '<div class="search-loading">Searching...</div>';

      try {
        const url = new URL('../api/users/search_users.php', window.location.href);
        url.searchParams.set('q', query);
        if (role) url.searchParams.set('role', role);
        const res = await fetch(url.toString());
        if (!res.ok) {
          dailyLogSearchResultsEl.innerHTML = '<div class="search-no-results">Search failed</div>';
          return;
        }
        const data = await res.json();
        if (!data.ok || !Array.isArray(data.data)) {
          dailyLogSearchResultsEl.innerHTML = '<div class="search-no-results">No results</div>';
          return;
        }
        if (data.data.length === 0) {
          dailyLogSearchResultsEl.innerHTML = '<div class="search-no-results">No matching records found</div>';
          return;
        }

        let html = '';
        data.data.forEach(user => {
          let metaText = '';
          if (user.role === 'student') {
            if (user.identifier) metaText = `Student ID: ${user.identifier}`;
            if (user.course) metaText += (metaText ? ' | ' : '') + `Course: ${user.course}`;
          } else if (user.role === 'faculty') {
            if (user.identifier) metaText = `Faculty ID: ${user.identifier}`;
            if (user.department) metaText += (metaText ? ' | ' : '') + user.department;
          } else if (user.role === 'staff') {
            if (user.identifier) metaText = `Staff ID: ${user.identifier}`;
            if (user.department) metaText += (metaText ? ' | ' : '') + user.department;
          } else if (user.role === 'visitor') {
            if (user.purpose) metaText = `Purpose: ${user.purpose}`;
            if (user.email) metaText += (metaText ? ' | ' : '') + user.email;
          }
          if (!metaText && user.email) metaText = user.email;
          if (!metaText && user.phone) metaText = user.phone;

          const photoHtml = user.photo && /^[A-Za-z0-9_\-\.]+\.(jpg|jpeg|png|gif|webp)$/i.test(user.photo)
            ? `<img src="/server/School_Entrance_Monitoring_System/uploads/${encodeURIComponent(user.photo)}" alt="" onerror="this.parentElement.innerHTML='${getInitials(user.name)}'" />`
            : getInitials(user.name);

          html += `<div class="search-result-item" data-uid="${escapeHtml(user.uid)}" data-role="${escapeHtml(user.role || '')}">
            <div class="search-result-avatar">${photoHtml}</div>
            <div class="search-result-info">
              <div class="search-result-name">${highlightMatch(user.name, query)}</div>
              <div class="search-result-meta">${escapeHtml(metaText || '')}</div>
            </div>
            <span class="search-result-badge ${getRoleBadgeClass(user.role)}">${getRoleLabel(user.role)}</span>
          </div>`;
        });
        dailyLogSearchResultsEl.innerHTML = html;

        dailyLogSearchResultsEl.querySelectorAll('.search-result-item').forEach(item => {
          item.addEventListener('click', () => {
            openPersonPreview(item.dataset.uid);
            dailyLogSearchResultsEl.classList.add('hidden');
          });
        });
      } catch (err) {
        dailyLogSearchResultsEl.innerHTML = '<div class="search-no-results">Search error</div>';
      }
    }

    if (dailyLogSearchInputEl) {
      dailyLogSearchInputEl.addEventListener('input', () => {
        if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(performDailyLogSearch, 300);
      });
      dailyLogSearchInputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
          performDailyLogSearch();
        }
      });
    }

    if (dailyLogSearchBtnEl) {
      dailyLogSearchBtnEl.addEventListener('click', performDailyLogSearch);
    }

    if (dailyLogSearchRoleEl) {
      dailyLogSearchRoleEl.addEventListener('change', () => {
        const query = dailyLogSearchInputEl ? dailyLogSearchInputEl.value.trim() : '';
        if (query.length >= 2) performDailyLogSearch();
      });
    }

    async function openPersonPreview(uid) {
      if (!personPreviewPanelEl) return;
      personPreviewPanelEl.classList.remove('hidden');

      const previewPhotoEl = document.getElementById('previewPhoto');
      const previewNameEl = document.getElementById('previewName');
      const previewUidEl = document.getElementById('previewUid');
      const previewRoleEl = document.getElementById('previewRole');
      const previewIdDetailEl = document.getElementById('previewIdDetail');
      const previewExtraDetailsEl = document.getElementById('previewExtraDetails');
      const previewHistoryRowsEl = document.getElementById('previewHistoryRows');

      if (previewPhotoEl) previewPhotoEl.src = '/server/School_Entrance_Monitoring_System/image/nophoto_s.png';
      if (previewNameEl) previewNameEl.textContent = 'Loading...';
      if (previewUidEl) previewUidEl.textContent = uid;
      if (previewRoleEl) previewRoleEl.textContent = '';
      if (previewIdDetailEl) previewIdDetailEl.textContent = '';
      if (previewExtraDetailsEl) previewExtraDetailsEl.innerHTML = '';
      if (previewHistoryRowsEl) previewHistoryRowsEl.innerHTML = '<tr><td colspan="5">Loading...</td></tr>';

      try {
        const userRes = await fetch(`../api/users/get_user.php?uid=${encodeURIComponent(uid)}`);
        if (!userRes.ok) throw new Error('User fetch failed');
        const userData = await userRes.json();
        if (!userData.ok || !userData.data) throw new Error('User not found');

        const user = userData.data;
        const role = user.role || 'unknown';
        const roleLabel = getRoleLabel(role);

        if (previewPhotoEl) {
          previewPhotoEl.src = getProfilePhotoSrc(user.photo);
          previewPhotoEl.onerror = function() {
            this.onerror = null;
            this.src = '/server/School_Entrance_Monitoring_System/image/nophoto_s.png';
          };
        }
        if (previewNameEl) previewNameEl.textContent = user.name || 'Unknown';
        if (previewRoleEl) {
          previewRoleEl.className = getRoleBadgeClass(role);
          previewRoleEl.textContent = roleLabel;
        }
        if (previewUidEl) previewUidEl.textContent = user.uid || '-';

        let idLine = '';
        if (role === 'student' && user.student_id) idLine = `Student ID: ${user.student_id}`;
        else if (role === 'faculty' && user.faculty_id) idLine = `Faculty ID: ${user.faculty_id}`;
        else if (role === 'staff' && user.staff_id) idLine = `Staff ID: ${user.staff_id}`;
        if (idLine && previewIdDetailEl) previewIdDetailEl.innerHTML = `<span class="detail-item"><strong>ID:</strong> ${escapeHtml(idLine)}</span>`;

        let extraHtml = '';
        if (role === 'student') {
          if (user.course) extraHtml += `<span class="detail-item"><strong>Course:</strong> ${escapeHtml(user.course)}</span>`;
          if (user.school_year) extraHtml += `<span class="detail-item"><strong>SY:</strong> ${escapeHtml(user.school_year)}</span>`;
          if (user.section) extraHtml += `<span class="detail-item"><strong>Section:</strong> ${escapeHtml(user.section)}</span>`;
        } else if (role === 'faculty' || role === 'staff') {
          if (user.department) extraHtml += `<span class="detail-item"><strong>Dept:</strong> ${escapeHtml(user.department)}</span>`;
        } else if (role === 'visitor') {
          if (user.purpose) extraHtml += `<span class="detail-item"><strong>Purpose:</strong> ${escapeHtml(user.purpose)}</span>`;
          if (user.valid_until) extraHtml += `<span class="detail-item"><strong>Valid Until:</strong> ${escapeHtml(user.valid_until)}</span>`;
        }
        if (user.email) extraHtml += `<span class="detail-item"><strong>Email:</strong> ${escapeHtml(user.email)}</span>`;
        if (user.phone) extraHtml += `<span class="detail-item"><strong>Phone:</strong> ${escapeHtml(user.phone)}</span>`;
        if (previewExtraDetailsEl) previewExtraDetailsEl.innerHTML = extraHtml;

        try {
          const historyRes = await fetch(`../api/users/get_personal_activity.php?uid=${encodeURIComponent(uid)}&limit=50`);
          if (!historyRes.ok) throw new Error('History fetch failed');
          const historyData = await historyRes.json();
          if (!historyData.ok || !Array.isArray(historyData.data)) {
            if (previewHistoryRowsEl) previewHistoryRowsEl.innerHTML = '<tr><td colspan="5">Unable to load history</td></tr>';
          } else if (historyData.data.length === 0) {
            if (previewHistoryRowsEl) previewHistoryRowsEl.innerHTML = '<tr><td colspan="5">No scan history found</td></tr>';
          } else {
            let hHtml = '';
            historyData.data.forEach(row => {
              const direction = String(row.direction || '-').toUpperCase();
              const adminDisplay = row.admin_name || row.admin_uid || '-';
              const status = row.suspicious == 1 ? '<span class="badge danger">Suspicious</span>' : '<span class="badge info">Normal</span>';
              hHtml += `<tr>
                <td>${row.id || '-'}</td>
                <td style="font-weight:700; ${direction === 'IN' ? 'color:var(--accent)' : 'color:var(--danger)'}">${direction}</td>
                <td>${escapeHtml(adminDisplay)}</td>
                <td>${status}</td>
                <td>${row.created_at || '-'}</td>
              </tr>`;
            });
            if (previewHistoryRowsEl) previewHistoryRowsEl.innerHTML = hHtml;
          }
        } catch (err) {
          if (previewHistoryRowsEl) previewHistoryRowsEl.innerHTML = '<tr><td colspan="5">History unavailable</td></tr>';
        }
      } catch (err) {
        if (previewNameEl) previewNameEl.textContent = 'Unable to load user';
        if (previewHistoryRowsEl) previewHistoryRowsEl.innerHTML = '<tr><td colspan="5">User data unavailable</td></tr>';
      }
    }

    if (previewCloseBtnEl) {
      previewCloseBtnEl.addEventListener('click', () => {
        if (personPreviewPanelEl) personPreviewPanelEl.classList.add('hidden');
      });
    }

    let scanPollInterval = null;
    let suspiciousPollInterval = null;
    let registerPollInterval = null;
    let adminPollInterval = null;

    // Scan log panel references
    const scanLogPanel = document.getElementById('scanLogPanel');

    const sidebar = document.querySelector('.sidebar');
    if (sidebar && scanLogPanel) {
      const updatePanelPosition = () => {
        const isHovered = sidebar.matches(':hover') || document.activeElement && sidebar.contains(document.activeElement);
        if (isHovered) {
          document.body.classList.add('sidebar-hovered');
        } else {
          document.body.classList.remove('sidebar-hovered');
        }
      };
      sidebar.addEventListener('mouseenter', updatePanelPosition);
      sidebar.addEventListener('mouseleave', updatePanelPosition);
      sidebar.addEventListener('focusin', updatePanelPosition);
      sidebar.addEventListener('focusout', updatePanelPosition);
      updatePanelPosition();
    }

    // Logout handler
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', async () => {
        try {
          await fetch('../api/admin/admin_logout.php', { method: 'POST' });
        } catch (err) {
          // ignore
        }
        window.location.href = 'login.php';
      });
    }

    // Settings logic
    let settingsData = {
      theme: 'light',
      font_size: 'medium',
      refresh_today_scan: 5,
      refresh_inside_now: 5,
      refresh_suspicious_alerts: 10,
      daily_reset_hour: 0
    };

    function applySettings(settings) {
      if (!settings) return;
      settingsData = { ...settingsData, ...settings };

      if (settingThemeEl) settingThemeEl.checked = settingsData.theme === 'dark';
      if (settingFontSizeEl) settingFontSizeEl.value = settingsData.font_size || 'medium';
      if (settingRefreshTodayEl) settingRefreshTodayEl.value = settingsData.refresh_today_scan || 5;
      if (settingRefreshInsideEl) settingRefreshInsideEl.value = settingsData.refresh_inside_now || 5;
      if (settingRefreshSuspiciousEl) settingRefreshSuspiciousEl.value = settingsData.refresh_suspicious_alerts || 10;
      if (settingDailyResetHourEl) settingDailyResetHourEl.value = settingsData.daily_reset_hour ?? 0;

      document.body.classList.remove('dark-mode');
      document.body.classList.remove('font-size-small', 'font-size-medium', 'font-size-large');
      if (settingsData.theme === 'dark') {
        document.body.classList.add('dark-mode');
      }
      const fsClass = 'font-size-' + (settingsData.font_size || 'medium');
      document.body.classList.add(fsClass);
    }

    async function loadSettings() {
      try {
        const res = await fetch('../api/system/get_settings.php');
        if (!res.ok) return;
        const data = await res.json();
        if (data.ok && data.data) {
          applySettings(data.data);
        }
      } catch (e) {
        // ignore
      }
    }

    if (saveSettingsBtn) {
      saveSettingsBtn.addEventListener('click', async () => {
        if (settingsStatusEl) settingsStatusEl.textContent = 'Saving...';
        const payload = {
          theme: settingThemeEl && settingThemeEl.checked ? 'dark' : 'light',
          font_size: settingFontSizeEl ? settingFontSizeEl.value : 'medium',
          refresh_today_scan: settingRefreshTodayEl ? settingRefreshTodayEl.value : 5,
          refresh_inside_now: settingRefreshInsideEl ? settingRefreshInsideEl.value : 5,
          refresh_suspicious_alerts: settingRefreshSuspiciousEl ? settingRefreshSuspiciousEl.value : 10,
          daily_reset_hour: settingDailyResetHourEl ? settingDailyResetHourEl.value : 0
        };
        try {
          const res = await fetch('../api/system/save_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          const data = await res.json();
          if (data.ok) {
            if (settingsStatusEl) settingsStatusEl.textContent = 'Saved';
            applySettings(payload);
            restartPollingIntervals();
          } else {
            if (settingsStatusEl) settingsStatusEl.textContent = data.error || 'Save failed';
          }
        } catch (err) {
          if (settingsStatusEl) settingsStatusEl.textContent = 'Network error';
        }
      });
    }

    if (settingThemeEl) {
      settingThemeEl.addEventListener('change', () => {
        const theme = settingThemeEl.checked ? 'dark' : 'light';
        document.body.classList.toggle('dark-mode', theme === 'dark');
        localStorage.setItem('theme', theme);
      });
    }

    function clearPollingIntervals() {
      if (scanPollInterval) clearInterval(scanPollInterval);
      if (suspiciousPollInterval) clearInterval(suspiciousPollInterval);
      if (registerPollInterval) clearInterval(registerPollInterval);
      if (adminPollInterval) clearInterval(adminPollInterval);
      scanPollInterval = null;
      suspiciousPollInterval = null;
      registerPollInterval = null;
      adminPollInterval = null;
    }

    function restartPollingIntervals() {
      clearPollingIntervals();
      const scanMs = Math.max(1000, (parseInt(settingsData.refresh_today_scan || 5, 10) * 1000));
      const suspiciousMs = Math.max(1000, (parseInt(settingsData.refresh_suspicious_alerts || 10, 10) * 1000));
      scanPollInterval = setInterval(loadScans, scanMs);
      suspiciousPollInterval = setInterval(loadSuspicious, suspiciousMs);
      registerPollInterval = setInterval(pollRegisterSignal, 1500);
      adminPollInterval = setInterval(pollAdminSignal, 1500);
    }

    // Theme switcher logic
    if (themeSwitcher) {
      const currentTheme = localStorage.getItem('theme') || 'light';
      if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode');
      }
      themeSwitcher.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        let theme = 'light';
        if (document.body.classList.contains('dark-mode')) {
          theme = 'dark';
        }
        localStorage.setItem('theme', theme);
        if (settingThemeEl) settingThemeEl.checked = theme === 'dark';
      });
    }

    loadSettings();
    restartPollingIntervals();
  </script>
</body>
</html>
