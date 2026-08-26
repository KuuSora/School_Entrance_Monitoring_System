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
  <link rel="stylesheet" href="style.css">
  <style>
    :root {
      --bg-color: #f4f7fb;
      --card-bg: #ffffff;
      --text-color: #102033;
      --muted-color: #5b6b7f;
      --border-color: #d8e1ea;
      --accent-color: #0f766e;
      --accent-color-light: #e6fffb;
      --shadow-color: rgba(0, 0, 0, 0.05);
      --danger-color: #dc2626;
      --danger-bg: #fef2f2;
      --sidebar-bg: #ffffff;
      --sidebar-accent: #0f766e;
      --sidebar-ink: #102033;
      --sidebar-hover: rgba(15, 118, 110, 0.08);
      --sidebar-active-bg: #e6fffb;
      --sidebar-icon-bg: #edf7f6;
    }

    .dark-mode {
      --bg-color: #242547;
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
      background-color: var(--bg-color);
      color: var(--text-color);
      transition: background-color 0.3s, color 0.3s;
    }

    .topbar {
      background-color: var(--card-bg);
      border-bottom: 1px solid var(--border-color);
      box-shadow: 0 2px 4px var(--shadow-color);
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
      border: 1px solid var(--border-color);
    }

    .dark-mode .sidebar .nav-brand {
      background: #2c2d57;
      border-color: #3b3d77;
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
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
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
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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
      font-size: 20px;
      font-weight: 700;
      color: #333;
      margin-bottom: 4px;
    }
    .id-card-name.yellogreen { color: #9ACD32; }
    .id-card-info { font-size: 14px; color: #555; margin-bottom: 2px; }
  </style>
  <style>
    /* Dashboard Preview Layout */
    .preview-layout {
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: var(--space-lg);
      align-items: flex-start;
    }
    .preview-layout > .stats-column {
      min-width: 0;
    }
    .preview-layout > .id-card-container {
      min-width: 0;
    }
    .id-card-container {
      position: sticky;
      top: 20px;
    }
    .id-card-container .id-card-display {
      position: static;
      transform: none;
      opacity: 1;
      transition: none;
    }
    .id-card-container .id-card {
      width: 100%;
      transition: box-shadow 0.3s ease;
    }
    .id-card-container .id-card.highlight {
      box-shadow: 0 0 20px 5px var(--accent-color-light);
    }
    .id-card-placeholder {
      display: grid;
      place-items: center;
      height: 320px; /* Match card height */
      border: 2px dashed var(--border-color);
      border-radius: 16px;
      color: var(--muted-color);
      text-align: center;
      font-size: 14px;
    }
    .id-card-placeholder.hidden { display: none; }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-title">RFID Attendance Dashboard</div>
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
        <span class="brand-dot">RF</span>
        <span class="brand-text">RFID Console</span>
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
            <div class="stats-column">
              <h1>Live Dashboard</h1>
              <p class="sub">Real-time campus entrance statistics.</p>
              <div class="stats-list">
                <div class="stat-item">
                  <span class="stat-label">Today Scans</span>
                  <span class="stat-value" id="todayTotal">-</span>
                  <span class="stat-meta" id="todayMeta">In: - | Out: -</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Inside Now</span>
                  <span class="stat-value" id="insideTotal">-</span>
                  <span class="stat-meta" id="insideMeta">Students: - | Faculty: -</span>
                </div>
                <div class="stat-item stat-item-alert">
                  <span class="stat-label">Suspicious Alerts</span>
                  <span class="stat-value" id="suspiciousCount">-</span>
                  <span class="stat-meta" id="suspiciousMeta">Last 24 hours</span>
                </div>
              </div>
            </div>
            <div class="id-card-container">
              <div id="idCardDisplay" class="id-card-display hidden">
                <div class="id-card">
                  <div class="id-card-base"></div>
                  <div class="id-card-content">
                    <div class="id-card-layout">
                      <div class="id-card-photo-area">
                        <img id="idCardPhoto" src="" alt="User Photo">
                      </div>
                      <div class="id-card-info-area">
                        <div id="idCardName" class="id-card-name"></div>
                        <div id="idCardRole" class="id-card-role"></div>
                        <div id="idCardId" class="id-card-id"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div id="idCardPlaceholder" class="id-card-placeholder">Waiting for next scan...</div>
            </div>
          </div>
        </section>
        <section class="section">
          <h1>Overview</h1>
          <p class="sub">Quick stats for the week and month.</p>
          <div class="grid">
            <div class="card">
              <h3>Week Scans</h3>
              <div class="value" id="weekTotal">-</div>
              <div class="meta" id="weekMeta">Avg/day: -</div>
            </div>
            <div class="card">
              <h3>Month Scans</h3>
              <div class="value" id="monthTotal">-</div>
              <div class="meta" id="monthMeta">Best day: -</div>
            </div>
            <div class="card">
              <h3>Active Students</h3>
              <div class="value" id="activeStudents">-</div>
              <div class="meta">Last 7 days</div>
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
            <form id="registerForm">
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
          <div style="display:flex; gap:8px; align-items:center; margin:6px 0 10px 0; flex-wrap:wrap;">
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
          <div class="daily-report-actions" style="margin-bottom:10px;">
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

    <aside class="panel-fixed" id="scanLogPanel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">RFID Scan Log</h2>
          <p class="panel-note">Permanent view for in/out scans</p>
        </div>
        <div class="panel-header-actions">
          <label for="adminFilter" class="filter-label">Admin:</label>
          <select id="adminFilter" class="filter-select">
            <option value="">All Admins</option>
          </select>
          <label class="filter-label">
            <input type="checkbox" id="suspiciousOnly" />
            <span>Only suspicious</span>
          </label>
          <button class="panel-toggle-btn" id="panelToggleBtn" title="Minimize panel">− Minimize</button>
        </div>
      </div>
      <div class="status" id="status">Loading...</div>
      <div class="panel-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Dir</th>
              <th>UID</th>
              <th>Admin</th>
              <th>Time</th>
            </tr>
          </thead>
          <tbody id="rows"></tbody>
        </table>
      </div>
    </aside>
  <button class="floating-log-btn" id="floatingLogBtn" title="Open scan log">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="badge" id="scanBadge" style="display:none">0</span>
  </button>
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
    const idCardDisplayEl = document.getElementById('idCardDisplay');
    const idCardPhotoEl = document.getElementById('idCardPhoto');
    const idCardNameEl = document.getElementById('idCardName');
    const idCardRoleEl = document.getElementById('idCardRole');
    const idCardIdEl = document.getElementById('idCardId');
    const idCardPlaceholderEl = document.getElementById('idCardPlaceholder');
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
    let chartHistoryData = [];
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
      if (panel.classList.contains('panel-minimized')) {
        setPanelMinimized(false);
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
      if (tabId === 'reportsTab') {
        loadCharts();
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
              label += ` — ${tags.join(' · ')}`;
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
          
          if (user.photo) {
            idCardPhotoEl.src = '/server/School_Entrance_Monitoring_System/image/' + user.photo;
          } else {
            idCardPhotoEl.src = '/server/School_Entrance_Monitoring_System/image/idcapsu.png';
          }
          
          idCardNameEl.textContent = user.name || 'Unknown';
          idCardNameEl.classList.add('yellogreen'); // Add the requested color class

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

          // Clear any existing timeout and set a new one to hide the card
          if (idCardTimeout) clearTimeout(idCardTimeout);
          // Hide after 12 seconds, remove highlight sooner
          setTimeout(() => cardEl.classList.remove('highlight'), 4000);
          idCardTimeout = setTimeout(hideIdCard, 12000);
        } else {
          hideIdCard();
        }
      } catch (err) {
        console.error("Failed to fetch user for ID card", err);
        hideIdCard();
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
          let userDisplay = name === 'New User'
            ? `<button onclick="openRegister('${row.uid}', '${row.created_at}')" style="cursor:pointer; padding:4px 8px; border:1px solid #ccc; background:#fff; border-radius:4px;">Register</button>`
            : `<b>${name}</b>`;

          const adminDisplay = row.admin_name ? row.admin_name : (row.admin_uid ? row.admin_uid : '');
          const suspiciousBadge = row.suspicious == 1 ? '<span class="suspicious-badge">⚠</span>' : '';
          const trClass = row.suspicious == 1 ? 'class="suspicious-row"' : '';

          // add data attributes so we can target the newest scan row for animation
          newHtml += `<tr ${trClass} data-scan-id="${row.id}" data-direction="${dir}">
            <td>${row.id}</td>
            <td>${userDisplay}${suspiciousBadge}</td>
            <td>${dir}</td>
            <td>${row.uid}</td>
            <td>${adminDisplay}</td>
            <td>${row.created_at}</td>
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
        if (scanBadge) {
          scanBadge.textContent = data.data.length || '';
          scanBadge.style.display = data.data.length ? 'grid' : 'none';
        }

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

    if (personalUserSelectEl) {
      personalUserSelectEl.addEventListener('change', () => {
        loadUserLogs(personalUserSelectEl.value);
      });
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
      try {
        const res = await fetch('../api/users/register_user.php', {
          method: 'POST',
          body: new URLSearchParams(formData)
        });
        const data = await res.json();
        if (data.ok) {
          registerStatusEl.textContent = 'Saved';
          // clear the form inputs so previous values don't remain
          const savedUid = regUidEl.value;
          registerFormEl.reset();
          regUidEl.readOnly = false;
          loadUsers();
          loadScans();
          loadUserDetails(savedUid);
        } else {
          registerStatusEl.textContent = data.error || 'Save failed';
        }
      } catch (err) {
        registerStatusEl.textContent = 'Network error';
      }
    });

    document.getElementById('regRole').addEventListener('change', (event) => {
      updateRoleFields(event.target.value);
    });

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
    let scanPollInterval = null;
    let suspiciousPollInterval = null;
    let registerPollInterval = null;
    let adminPollInterval = null;

    // Panel minimize toggle
    const panelToggleBtn = document.getElementById('panelToggleBtn');
    const scanLogPanel = document.getElementById('scanLogPanel');
    const floatingLogBtn = document.getElementById('floatingLogBtn');
    const scanBadge = document.getElementById('scanBadge');

    function setPanelMinimized(minimized) {
      if (!scanLogPanel) return;
      if (minimized) {
        scanLogPanel.classList.add('panel-minimized');
        document.body.classList.add('panel-minimized');
        if (panelToggleBtn) panelToggleBtn.textContent = '+ Maximize';
      } else {
        scanLogPanel.classList.remove('panel-minimized');
        document.body.classList.remove('panel-minimized');
        if (panelToggleBtn) panelToggleBtn.textContent = '\u2212 Minimize';
      }
    }

    function togglePanel() {
      const isMinimized = scanLogPanel.classList.contains('panel-minimized');
      setPanelMinimized(!isMinimized);
    }

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

    if (panelToggleBtn && scanLogPanel) {
      panelToggleBtn.addEventListener('click', togglePanel);
    }
    if (floatingLogBtn && scanLogPanel) {
      floatingLogBtn.addEventListener('click', () => setPanelMinimized(false));
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
