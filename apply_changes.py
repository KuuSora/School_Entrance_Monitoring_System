import os

INDEX_PHP_PATH = os.path.join(os.path.dirname(__file__), 'dashboard', 'index.php')

def read_file(path):
    with open(path, 'r', encoding='utf-8') as f:
        return f.read()

def write_file(path, content):
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)

def main():
    content = read_file(INDEX_PHP_PATH)

    # Step 3: Add personalActivityTab section after registerTab closing div and before </main>
    register_tab_close = '      </div>\n    </main>'
    personal_activity_tab = '''      </div>
      <div id="personalActivityTab" class="tab-content">
        <section class="section">
          <h1>Personal Activity</h1>
          <p class="sub">View scan history for a specific person.</p>
          <div class="split">
            <div class="card">
              <h3>Filter by Admin</h3>
              <label for="personalAdminFilter" style="font-size:13px; color:var(--muted); margin-right:6px;">Admin:</label>
              <select id="personalAdminFilter" style="padding:8px 10px; border-radius:8px; border:1px solid var(--stroke); background:#fff;">
                <option value="">All Admins</option>
              </select>
              <label for="personalUserSelect" style="font-size:13px; color:var(--muted); margin-left:8px; display:flex; align-items:center; gap:6px;">Person:</label>
              <select id="personalUserSelect" style="padding:8px 10px; border-radius:8px; border:1px solid var(--stroke); background:#fff;">
                <option value="">Select a person</option>
              </select>
              <div id="personalUserAdminTags" class="tags" style="margin-top:8px;"></div>
            </div>
            <div class="card">
              <h3>Scan History</h3>
              <div class="panel-table">
                <table>
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Direction</th>
                      <th>Admin</th>
                      <th>Time</th>
                    </tr>
                  </thead>
                  <tbody id="personalUserLogs">
                    <tr><td colspan="4">Select a person to view logs</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>'''
    content = content.replace(register_tab_close, personal_activity_tab)

    # Step 4: Add variable declarations after idCardPlaceholderEl
    id_card_placeholder_line = '    const idCardPlaceholderEl = document.getElementById(\'idCardPlaceholder\');'
    personal_vars = '''    const idCardPlaceholderEl = document.getElementById('idCardPlaceholder');
    const personalAdminFilterEl = document.getElementById("personalAdminFilter");
    const personalUserSelectEl = document.getElementById("personalUserSelect");
    const personalUserLogsEl = document.getElementById("personalUserLogs");
    const personalUserAdminTagsEl = document.getElementById("personalUserAdminTags");
    let personalAdminTagsByUid = {};'''
    content = content.replace(id_card_placeholder_line, personal_vars)

    # Step 5: Add new JavaScript functions before loadUserLogs
    load_user_logs_func = '    async function loadUserLogs(uid) {'
    new_functions = '''    function getPersonalAdminFilterValue() {
      return personalAdminFilterEl ? personalAdminFilterEl.value : '';
    }

    function renderPersonalUserAdminTags(tags) {
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

    async function loadPersonalAdmins() {
      if (!personalAdminFilterEl) return;
      try {
        const res = await fetch('../api/admin/get_admins.php');
        if (!res.ok) {
          return;
        }
        const data = await res.json();
        if (!data.ok) return;
        const currentValue = personalAdminFilterEl ? personalAdminFilterEl.value : '';

        if (personalAdminFilterEl) {
          personalAdminFilterEl.innerHTML = '<option value="">All Admins</option>';
        }

        data.data.forEach(a => {
          if (personalAdminFilterEl) {
            const opt = document.createElement('option');
            opt.value = a.uid;
            opt.textContent = a.name;
            personalAdminFilterEl.appendChild(opt);
          }
        });

        if (currentValue) {
          personalAdminFilterEl.value = currentValue;
        }
      } catch (err) {
        // ignore
      }
    }

    async function loadPersonalUsers() {
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

        personalAdminTagsByUid = {};
        if (adminUsersData.ok && Array.isArray(adminUsersData.data)) {
          adminUsersData.data.forEach(row => {
            if (!row.uid) {
              return;
            }
            if (!personalAdminTagsByUid[row.uid]) {
              personalAdminTagsByUid[row.uid] = new Set();
            }
            const adminLabel = row.admin_name ? row.admin_name : (row.admin_uid ? row.admin_uid : '');
            if (adminLabel) {
              personalAdminTagsByUid[row.uid].add(adminLabel);
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
          if (personalAdminTagsByUid[user.uid]) {
            const tags = Array.from(personalAdminTagsByUid[user.uid]);
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
          loadPersonalUserLogs(personalUserSelectEl.value);
        } else {
          personalUserLogsEl.innerHTML = '<tr><td colspan="3">No registered people</td></tr>';
          renderPersonalUserAdminTags([]);
        }
      } catch (err) {
        personalUserLogsEl.innerHTML = '<tr><td colspan="3">Unable to load users</td></tr>';
        renderPersonalUserAdminTags([]);
      }
    }

    async function loadPersonalUserLogs(uid) {
      if (!personalUserLogsEl) {
        return;
      }
      if (!uid) {
        return;
      }
      try {
        const res = await fetch(`../api/users/get_user_logs.php?uid=${encodeURIComponent(uid)}&limit=200`);
        const data = await res.json();
        if (!data.ok) {
          personalUserLogsEl.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
          renderPersonalUserAdminTags([]);
          return;
        }
        if (data.data.length === 0) {
          personalUserLogsEl.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
          renderPersonalUserAdminTags([]);
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
        renderPersonalUserAdminTags(Array.from(adminSet));
      } catch (err) {
        personalUserLogsEl.innerHTML = '<tr><td colspan="4">Unable to load logs</td></tr>';
        renderPersonalUserAdminTags([]);
      }
    }

    async function loadUserLogs(uid) {'''
    content = content.replace(load_user_logs_func, new_functions)

    # Step 6: Add event listeners for personalAdminFilterEl and personalUserSelectEl after userSelect listener
    user_select_listener = '''    if (userSelectEl) {
      userSelectEl.addEventListener('change', () => {
        loadUserLogs(userSelectEl.value);
      });
    }'''
    extended_listeners = '''    if (userSelectEl) {
      userSelectEl.addEventListener('change', () => {
        loadUserLogs(userSelectEl.value);
      });
    }

    if (personalAdminFilterEl) {
      personalAdminFilterEl.addEventListener('change', () => {
        loadPersonalUsers();
        loadPersonalAdmins();
      });
    }

    if (personalUserSelectEl) {
      personalUserSelectEl.addEventListener('change', () => {
        loadPersonalUserLogs(personalUserSelectEl.value);
      });
    }'''
    content = content.replace(user_select_listener, extended_listeners)

    # Step 7: Add loadPersonalAdmins() call after loadAdmins() in initial load section
    initial_load_admins = '    loadAdmins();'
    extended_initial_load = '    loadAdmins();\n    loadPersonalAdmins();'
    content = content.replace(initial_load_admins, extended_initial_load)

    # Step 8: Write the modified content back to index.php
    write_file(INDEX_PHP_PATH, content)

if __name__ == '__main__':
    main()
