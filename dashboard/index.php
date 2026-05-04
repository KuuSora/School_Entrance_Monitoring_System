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
</head>
<body>
  <header class="topbar">
    <div class="topbar-title">RFID Attendance Dashboard</div>
    <div class="topbar-actions">
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
      <button class="tab-btn active" data-tab="dashboardTab" title="Dashboard">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M4 13h6V4H4v9zm0 7h6v-5H4v5zm10 0h6v-9h-6v9zm0-16v5h6V4h-6z" />
          </svg>
        </span>
        <span class="tab-label">Dashboard</span>
      </button>
      <button class="tab-btn" data-tab="registerTab" title="Register">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M7 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-6-6H7zm6 1.5L18.5 9H13V3.5zM8 13h4a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2zm0 4h8a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2zm7-7h2a1 1 0 1 1 0 2h-2a1 1 0 1 1 0-2z" />
          </svg>
        </span>
        <span class="tab-label">Register</span>
      </button>
      <button class="tab-btn" data-tab="dashboardTab" title="Scan Log">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M4 5a2 2 0 0 1 2-2h8l6 6v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5zm10-1.5V9h5.5L14 3.5zM8 13h8a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2zM8 17h6a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2z" />
          </svg>
        </span>
        <span class="tab-label">Scan Log</span>
      </button>
      <button class="tab-btn" data-tab="reportsTab" title="Reports">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M4 19a1 1 0 0 1 1-1h14a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1zm2-3V9a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0zm5 0V6a1 1 0 1 1 2 0v10a1 1 0 1 1-2 0zm5 0V11a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0z" />
          </svg>
        </span>
        <span class="tab-label">Reports</span>
      </button>
    </nav>
    <main class="main">
      <div id="dashboardTab" class="tab-content active">
        <section class="section">
          <h1>Dashboard Overview</h1>
          <p class="sub">Quick stats for today, week, and month.</p>
          <div class="grid">
            <div class="card">
              <h3>Today Scans</h3>
              <div class="value" id="todayTotal">-</div>
              <div class="meta" id="todayMeta">In: - | Out: -</div>
            </div>
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
            <div class="card">
              <h3>Inside Now</h3>
              <div class="value" id="insideTotal">-</div>
              <div class="meta" id="insideMeta">Students: - | Faculty: -</div>
            </div>
            <div class="card alert-card">
              <h3>Suspicious Alerts</h3>
              <div class="value" id="suspiciousCount">-</div>
              <div class="meta" id="suspiciousMeta">Last 24 hours</div>
            </div>
          </div>
        </section>

        <section class="section">
          <h1>Person Activity</h1>
          <p class="sub">View scans per registered person.</p>
          <div class="split">
            <div class="card">
              <h3>Person Logs</h3>
              <div class="field">
                <label for="userSelect">Select person</label>
                <select id="userSelect"></select>
              </div>
              <div class="panel-table" style="margin-top: 12px;">
                <table class="table-compact">
                  <thead>
                        <tr>
                          <th>ID</th>
                          <th>Dir</th>
                          <th>Admin</th>
                          <th>Time</th>
                        </tr>
                  </thead>
                  <tbody id="userLogs"></tbody>
                </table>
              </div>
            </div>
            <div class="stack">
              <div class="card">
                <h3>Peak Times (Today)</h3>
                <ul class="list" id="peakTimes"></ul>
              </div>
              <div class="card">
                <h3>Alerts</h3>
                <ul class="list" id="alertList"></ul>
              </div>
            </div>
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

      <div id="reportsTab" class="tab-content">
        <section class="section">
          <h1>Reports</h1>
          <p class="sub">Suspicious activity (consecutive IN/IN or OUT/OUT)</p>
          <div class="panel-table" style="margin-top: 12px;">
            <table class="table-compact">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User</th>
                  <th>Dir</th>
                  <th>Prev ID</th>
                  <th>Prev Time</th>
                  <th>Time</th>
                  <th>Admin</th>
                </tr>
              </thead>
              <tbody id="suspiciousRows"></tbody>
            </table>
          </div>
        </section>
      </div>
    </main>

    <aside class="panel-fixed">
      <h2 class="panel-title">RFID Scan Log</h2>
      <p class="panel-note">Permanent view for in/out scans</p>
      <div style="display:flex; gap:8px; align-items:center; margin:6px 0 10px 0;">
        <label for="adminFilter" style="font-size:13px; color:var(--muted); margin-right:6px;">Admin:</label>
        <select id="adminFilter" style="padding:8px 10px; border-radius:8px; border:1px solid var(--stroke); background:#fff;">
          <option value="">All Admins</option>
        </select>
        <label style="font-size:13px; color:var(--muted); margin-left:8px; display:flex; align-items:center; gap:6px;">
          <input type="checkbox" id="suspiciousOnly" />
          <span>Only suspicious</span>
        </label>
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
  </div>

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
    const peakTimesEl = document.getElementById('peakTimes');
    const alertListEl = document.getElementById('alertList');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const userSelectEl = document.getElementById('userSelect');
    const userLogsEl = document.getElementById('userLogs');
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
    const viewRoleEl = document.getElementById('viewRole');
    const viewFacultyIdEl = document.getElementById('viewFacultyId');
    const viewStaffIdEl = document.getElementById('viewStaffId');
    const viewDepartmentEl = document.getElementById('viewDepartment');
    const viewPurposeEl = document.getElementById('viewPurpose');
    const viewValidUntilEl = document.getElementById('viewValidUntil');
    let lastScanId = 0;
    let currentRegisteredUser = null;

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

    function setStats(stats) {
      if (!stats) {
        return;
      }

      todayTotalEl.textContent = stats.today.total;
      todayMetaEl.textContent = `In: ${stats.today.in} | Out: ${stats.today.out}`;

      weekTotalEl.textContent = stats.week.total;
      weekMetaEl.textContent = `Avg/day: ${stats.week.avg_per_day}`;

      monthTotalEl.textContent = stats.month.total;
      monthMetaEl.textContent = `Best day: ${stats.month.best_day}`;

      activeStudentsEl.textContent = stats.active_students_7d;
      insideTotalEl.textContent = stats.inside.total;
      insideMetaEl.textContent = `Students: ${stats.inside.students} | Faculty: ${stats.inside.faculty}`;

      let peakHtml = '';
      if (stats.peak_hours_today.length === 0) {
        peakHtml = '<li><span>No scans today</span><span class="badge">0</span></li>';
      } else {
        stats.peak_hours_today.forEach(item => {
          peakHtml += `<li><span>${formatHourRange(item.hour)}</span><span class="badge">${item.count}</span></li>`;
        });
      }
      peakTimesEl.innerHTML = peakHtml;

      const alerts = stats.alerts || {
        unregistered_cards: 0,
        scans_last_10_min: 0,
        unique_today: 0,
        consecutive_in: 0,
        consecutive_out: 0
      };
      alertListEl.innerHTML = `
        <li><span>Unregistered cards</span><span class="badge">${alerts.unregistered_cards}</span></li>
        <li><span>Scans last 10 min</span><span class="badge">${alerts.scans_last_10_min}</span></li>
        <li><span>Unique today</span><span class="badge">${alerts.unique_today}</span></li>
        <li><span>Consecutive IN</span><span class="badge">${alerts.consecutive_in}</span></li>
        <li><span>Consecutive OUT</span><span class="badge">${alerts.consecutive_out}</span></li>
      `;
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
        const res = await fetch(`../api/get_user.php?uid=${encodeURIComponent(uid)}`);
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
      try {
        const res = await fetch('../api/get_users.php');
        const data = await res.json();
        if (!data.ok) {
          return;
        }
        userSelectEl.innerHTML = '';
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
          const label = idLabel ? `${user.name} (${idLabel})` : user.name;
          const option = document.createElement('option');
          option.value = user.uid;
          option.textContent = label;
          userSelectEl.appendChild(option);
        });
        if (data.data.length > 0) {
          loadUserLogs(userSelectEl.value);
        } else {
          userLogsEl.innerHTML = '<tr><td colspan="3">No registered people</td></tr>';
        }
      } catch (err) {
        userLogsEl.innerHTML = '<tr><td colspan="3">Unable to load users</td></tr>';
      }
    }

    async function loadUserLogs(uid) {
      if (!uid) {
        return;
      }
      try {
        const res = await fetch(`../api/get_user_logs.php?uid=${encodeURIComponent(uid)}&limit=200`);
        const data = await res.json();
        if (!data.ok) {
          userLogsEl.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
          return;
        }
        if (data.data.length === 0) {
          userLogsEl.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
          return;
        }
        let html = '';
        data.data.forEach(row => {
          const adminDisplay = row.admin_name ? row.admin_name : (row.admin_uid ? row.admin_uid : '');
          html += `<tr>
            <td>${row.id}</td>
            <td>${row.direction}</td>
            <td>${adminDisplay}</td>
            <td>${row.created_at}</td>
          </tr>`;
        });
        userLogsEl.innerHTML = html;
      } catch (err) {
        userLogsEl.innerHTML = '<tr><td colspan="4">Unable to load logs</td></tr>';
      }
    }

    async function loadScans() {
      const adminUid = adminFilterEl ? adminFilterEl.value : '';
      const suspiciousOnly = suspiciousOnlyEl && suspiciousOnlyEl.checked ? 1 : 0;
      try {
        const url = new URL('../api/get_scans.php', window.location.href);
        url.searchParams.set('limit', '200');
        if (adminUid) url.searchParams.set('admin_uid', adminUid);
        if (suspiciousOnly) url.searchParams.set('suspicious', '1');
        const res = await fetch(url.toString());
        const data = await res.json();
        if (!data.ok) {
          statusEl.textContent = 'Error loading data';
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

        // highlight the newest scan row with a glow animation
        if (newestScan) {
          // show unregistered modal if needed
          if (newestScan.name === 'New User' || !newestScan.name) {
            setTimeout(() => showUnregistered(newestScan.uid, newestScan.created_at), 100);
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
        const res = await fetch('../api/get_admins.php');
        const data = await res.json();
        if (!data.ok) return;
        adminFilterEl.innerHTML = '<option value="">All Admins</option>';
        data.data.forEach(a => {
          const opt = document.createElement('option');
          opt.value = a.uid;
          opt.textContent = a.name;
          adminFilterEl.appendChild(opt);
        });
      } catch (err) {
        // ignore
      }
    }

    async function loadSuspicious() {
      try {
        const res = await fetch('../api/get_suspicious.php?limit=200');
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
          html += `<tr>
            <td>${row.id}</td>
            <td><b>${name}</b></td>
            <td>${row.direction}</td>
            <td>${row.prev_id}</td>
            <td>${row.prev_created_at}</td>
            <td>${row.created_at}</td>
            <td>${adminDisplay}</td>
          </tr>`;
        });
        el.innerHTML = html;
      } catch (err) {
        // ignore
      }
    }

    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => setActiveTab(btn.dataset.tab));
    });

    userSelectEl.addEventListener('change', () => {
      loadUserLogs(userSelectEl.value);
    });

    registerFormEl.addEventListener('submit', async (event) => {
      event.preventDefault();
      registerStatusEl.textContent = 'Saving...';

      const formData = new FormData(registerFormEl);
      try {
        const res = await fetch('../api/register_user.php', {
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

    // Initial load
    loadUsers();
    loadAdmins();
    loadScans();
    loadSuspicious();
    showPrompt();
    if (adminFilterEl) adminFilterEl.addEventListener('change', loadScans);
    // Poll every 3 seconds
    setInterval(loadScans, 3000);
    // Refresh suspicious reports every 10s
    setInterval(loadSuspicious, 10000);

    // Logout handler
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', async () => {
        try {
          await fetch('../api/admin_logout.php', { method: 'POST' });
        } catch (err) {
          // ignore
        }
        window.location.href = 'login.php';
      });
    }
  </script>
</body>
</html>
