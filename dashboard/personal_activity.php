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
  <title>Personal Activity</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="topbar">
    <div class="topbar-title">Personal Activity</div>
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
      <button class="tab-btn" type="button" data-href="index.php" title="Dashboard">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M4 13h6V4H4v9zm0 7h6v-5H4v5zm10 0h6v-9h-6v9zm0-16v5h6V4h-6z" />
          </svg>
        </span>
        <span class="tab-label">Dashboard</span>
      </button>
      <button class="tab-btn" type="button" data-href="index.php" title="Register">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M7 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-6-6H7zm6 1.5L18.5 9H13V3.5zM8 13h4a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2zm0 4h8a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2zm7-7h2a1 1 0 1 1 0 2h-2a1 1 0 1 1 0-2z" />
          </svg>
        </span>
        <span class="tab-label">Register</span>
      </button>
      <button class="tab-btn active" type="button" data-href="personal_activity.php" title="Personal Activity">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M12 2a5 5 0 0 1 5 5v1h1.5A2.5 2.5 0 0 1 21 10.5v8A2.5 2.5 0 0 1 18.5 21h-13A2.5 2.5 0 0 1 3 18.5v-8A2.5 2.5 0 0 1 5.5 8H7V7a5 5 0 0 1 5-5zm3 6V7a3 3 0 0 0-6 0v1h6zm-3 5a2 2 0 0 0-1 3.732V17a1 1 0 0 0 2 0v-.268A2 2 0 0 0 12 13z" />
          </svg>
        </span>
        <span class="tab-label">Personal Activity</span>
      </button>
      <button class="tab-btn" type="button" data-href="index.php" title="Scan Log">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M4 5a2 2 0 0 1 2-2h8l6 6v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5zm10-1.5V9h5.5L14 3.5zM8 13h8a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2zM8 17h6a1 1 0 1 1 0 2H8a1 1 0 1 1 0-2z" />
          </svg>
        </span>
        <span class="tab-label">Scan Log</span>
      </button>
      <button class="tab-btn" type="button" data-href="index.php" title="Reports">
        <span class="tab-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M4 19a1 1 0 0 1 1-1h14a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1zm2-3V9a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0zm5 0V6a1 1 0 1 1 2 0v10a1 1 0 1 1-2 0zm5 0V11a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0z" />
          </svg>
        </span>
        <span class="tab-label">Reports</span>
      </button>
    </nav>

    <main class="main">
      <section class="section">
        <h1>Personal Activity</h1>
        <p class="sub">Browse scans by person, scoped by admin, on a dedicated page.</p>
        <div class="split">
          <div class="card">
            <h3>Person Logs</h3>
            <div class="field">
              <label for="adminFilter">Admin filter</label>
              <select id="adminFilter">
                <option value="">All Admins</option>
              </select>
            </div>
            <div class="field">
              <label for="userSelect">Select person</label>
              <select id="userSelect"></select>
            </div>
            <div class="tag-row" id="userAdminTags"></div>
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
              <h3>Quick Note</h3>
              <p class="sub" style="margin-bottom: 0;">
                This page isolates personal activity so the dashboard can stay focused on scan overview and reports.
              </p>
            </div>
            <div class="card">
              <h3>Back to Dashboard</h3>
              <p class="sub" style="margin-bottom: 12px;">Return to the main overview and live scan log.</p>
              <button class="btn" type="button" data-href="index.php">Open Dashboard</button>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    const adminFilterEl = document.getElementById('adminFilter');
    const userSelectEl = document.getElementById('userSelect');
    const userLogsEl = document.getElementById('userLogs');
    const userAdminTagsEl = document.getElementById('userAdminTags');
    const tabButtons = document.querySelectorAll('.tab-btn');
    let adminTagsByUid = {};

    function getAdminFilterValue() {
      return adminFilterEl ? adminFilterEl.value : '';
    }

    function renderUserAdminTags(tags) {
      if (!userAdminTagsEl) {
        return;
      }
      userAdminTagsEl.innerHTML = '';
      if (!tags || tags.length === 0) {
        const emptyTag = document.createElement('span');
        emptyTag.className = 'tag muted';
        emptyTag.textContent = 'No admin tags';
        userAdminTagsEl.appendChild(emptyTag);
        return;
      }
      tags.forEach(tag => {
        const span = document.createElement('span');
        span.className = 'tag';
        span.textContent = tag;
        userAdminTagsEl.appendChild(span);
      });
    }

    async function loadAdmins() {
      if (!adminFilterEl) {
        return;
      }
      try {
        const res = await fetch('../api/admin/get_admins.php');
        const data = await res.json();
        if (!data.ok) {
          return;
        }
        const currentValue = adminFilterEl.value;
        adminFilterEl.innerHTML = '<option value="">All Admins</option>';
        data.data.forEach(admin => {
          const option = document.createElement('option');
          option.value = admin.uid;
          option.textContent = admin.name;
          adminFilterEl.appendChild(option);
        });
        if (currentValue) {
          adminFilterEl.value = currentValue;
        }
      } catch (err) {
        // ignore
      }
    }

    async function loadUsers() {
      if (!userSelectEl || !userLogsEl) {
        return;
      }
      try {
        const adminUid = getAdminFilterValue();
        const usersUrl = new URL('../api/users/get_users.php', window.location.href);
        if (adminUid) usersUrl.searchParams.set('admin_uid', adminUid);

        const adminUsersUrl = new URL('../api/admin/get_admin_users.php', window.location.href);
        if (adminUid) adminUsersUrl.searchParams.set('admin_uid', adminUid);

        const [usersRes, adminUsersRes] = await Promise.all([
          fetch(usersUrl.toString()),
          fetch(adminUsersUrl.toString())
        ]);

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

        const previousValue = userSelectEl.value;
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
          userSelectEl.appendChild(option);
        });

        if (data.data.length > 0) {
          const nextValue = data.data.some(user => user.uid === previousValue) ? previousValue : userSelectEl.value;
          userSelectEl.value = nextValue;
          loadUserLogs(userSelectEl.value);
        } else {
          userLogsEl.innerHTML = '<tr><td colspan="4">No registered people</td></tr>';
          renderUserAdminTags([]);
        }
      } catch (err) {
        userLogsEl.innerHTML = '<tr><td colspan="4">Unable to load users</td></tr>';
        renderUserAdminTags([]);
      }
    }

    async function loadUserLogs(uid) {
      if (!userLogsEl) {
        return;
      }
      if (!uid) {
        userLogsEl.innerHTML = '<tr><td colspan="4">Select a person to view logs</td></tr>';
        renderUserAdminTags([]);
        return;
      }
      try {
        const adminUid = getAdminFilterValue();
        const url = new URL('../api/users/get_personal_activity.php', window.location.href);
        url.searchParams.set('uid', uid);
        url.searchParams.set('limit', '200');
        if (adminUid) {
          url.searchParams.set('admin_uid', adminUid);
        }
        const res = await fetch(url.toString());
        if (!res.ok) {
          userLogsEl.innerHTML = '<tr><td colspan="4">Server error</td></tr>';
          console.error('get_personal_activity.php returned HTTP', res.status, await res.text());
          renderUserAdminTags([]);
          return;
        }
        let data;
        try {
          data = await res.json();
        } catch (e) {
          userLogsEl.innerHTML = '<tr><td colspan="4">Invalid server response</td></tr>';
          console.error('Failed to parse JSON from get_personal_activity.php:', e, await res.text());
          renderUserAdminTags([]);
          return;
        }
        if (!data.ok) {
          userLogsEl.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
          renderUserAdminTags([]);
          return;
        }
        if (data.data.length === 0) {
          userLogsEl.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
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
        userLogsEl.innerHTML = html;
        renderUserAdminTags(Array.from(adminSet));
      } catch (err) {
        userLogsEl.innerHTML = '<tr><td colspan="4">Unable to load logs</td></tr>';
        renderUserAdminTags([]);
      }
    }

    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        if (btn.dataset.href) {
          window.location.href = btn.dataset.href;
        }
      });
    });

    if (adminFilterEl) {
      adminFilterEl.addEventListener('change', () => {
        loadUsers();
      });
    }

    if (userSelectEl) {
      userSelectEl.addEventListener('change', () => {
        loadUserLogs(userSelectEl.value);
      });
    }

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

    loadAdmins();
    loadUsers();
  </script>
</body>
</html>
