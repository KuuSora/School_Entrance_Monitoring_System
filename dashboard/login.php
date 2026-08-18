<?php
session_start();

function is_local_dev_request(): bool {
  $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
  $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
  return in_array($remote_addr, ['127.0.0.1', '::1'], true)
    || $host === 'localhost'
    || $host === '127.0.0.1'
    || $host === '[::1]';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dev_login'])) {
  if (!is_local_dev_request()) {
    http_response_code(403);
    echo 'Local dev login is only available on localhost.';
    exit;
  }

  $_SESSION['admin_uid'] = 'LOCAL-DEV';
  $_SESSION['admin_name'] = 'Local Dev';
  header('Location: index.php');
  exit;
}

if (isset($_SESSION['admin_uid'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RFID Admin Access</title>
  <style>
/* Theme: professional (v2) */
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap');

:root {
  --bg: #f8fafc;
  --ink: #0f172a;
  --ink-muted: #64748b;
  --accent: #2563eb;
  --accent-deep: #1d4ed8;
  --card: #ffffff;
  --stroke: #e2e8f0;
  --shadow-sm: rgba(0, 0, 0, 0.04);
  --shadow-md: rgba(0, 0, 0, 0.06);
}

* { box-sizing: border-box; }

body {
  margin: 0;
  font-family: "IBM Plex Sans", system-ui, sans-serif;
  color: var(--ink);
  background: url('../image/capsu_pilar_gate.jpg') center/cover no-repeat fixed;
  min-height: 100vh;
  position: relative;
}

body::before {
  content: '';
  position: fixed;
  inset: 0;
  background: rgba(255, 255, 255, 0.20);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  z-index: 0;
}

.page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 36px 24px;
  position: relative;
  z-index: 1;
}

.layout {
  width: min(1120px, 100%);
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.9fr);
  gap: 32px;
}

.hero {
  display: grid;
  gap: 20px;
  align-content: center;
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  font-family: "Space Grotesk", system-ui, sans-serif;
  font-weight: 600;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  font-size: 18px;
  color: var(--ink-muted);
}

.brand .brand-mark {
  height: 80px;
  width: auto;
  margin-right: 10px;
  vertical-align: middle;
}

.hero-title {
  font-family: "Space Grotesk", system-ui, sans-serif;
  font-size: clamp(32px, 4vw, 52px);
  font-weight: 700;
  line-height: 1.05;
  margin: 0;
  color: var(--ink);
}

.hero-title span { color: var(--accent); }

.hero-sub {
  margin: 0;
  color: var(--ink-muted);
  font-size: 16px;
  line-height: 1.7;
  max-width: 520px;
}

.hero-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.badge {
  padding: 10px 14px;
  border-radius: 999px;
  border: 1px solid var(--accent);
  background: rgba(37, 99, 235, 0.08);
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--accent-deep);
}

.login-card {
  background: var(--card);
  border: 1px solid var(--stroke);
  border-radius: 18px;
  box-shadow: 0 4px 12px var(--shadow-sm);
  padding: 28px;
  display: grid;
  gap: 18px;
  align-content: center;
}

.card-title {
  font-family: "Space Grotesk", system-ui, sans-serif;
  font-size: 20px;
  margin: 0;
  color: var(--ink);
}

.card-sub {
  margin: 0;
  color: var(--ink-muted);
  font-size: 14px;
  line-height: 1.6;
}

.scan-panel {
  display: grid;
  gap: 14px;
  padding: 18px;
  border-radius: 14px;
  border: 1px dashed var(--stroke);
  background: #f8fafc;
}

.dev-login {
  display: grid;
  gap: 10px;
  margin-top: 4px;
}

.dev-login form { margin: 0; }

.dev-login-btn {
  appearance: none;
  border: 1px solid var(--accent);
  background: var(--accent);
  color: #fff;
  width: 100%;
  border-radius: 12px;
  padding: 12px 16px;
  font: inherit;
  font-weight: 600;
  letter-spacing: 0.2px;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
  transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
}

.dev-login-btn:hover {
  background: var(--accent-deep);
  box-shadow: 0 6px 14px rgba(37, 99, 235, 0.3);
  transform: translateY(-1px);
}

.dev-login-note {
  margin: 0;
  font-size: 12px;
  line-height: 1.5;
  color: var(--ink-muted);
}

.scan-header {
  display: flex;
  align-items: center;
  gap: 14px;
}

.scan-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: rgba(37, 99, 235, 0.1);
  display: grid;
  place-items: center;
  font-weight: 700;
  color: var(--accent);
  font-family: "Space Grotesk", system-ui, sans-serif;
  letter-spacing: 1px;
}

.status {
  font-size: 14px;
  color: var(--ink-muted);
}

.status strong { color: var(--ink); }

.log {
  font-size: 12px;
  color: var(--ink-muted);
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(37, 99, 235, 0.08);
  color: var(--accent-deep);
  font-size: 12px;
  font-weight: 600;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid rgba(37, 99, 235, 0.3);
}

.pulse {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent);
  box-shadow: 0 0 12px var(--accent);
  animation: pulse 1.4s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(0.85); opacity: 0.6; }
  50% { transform: scale(1); opacity: 1; }
}

.fade-up { animation: fadeUp 0.8s ease both; }
.fade-up.delay-1 { animation-delay: 0.1s; }
.fade-up.delay-2 { animation-delay: 0.2s; }
.fade-up.delay-3 { animation-delay: 0.3s; }

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 900px) {
  .layout { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
  <div class="page">
    <div class="layout">
      <section class="hero">
        <div class="brand fade-up">
          <img src="\School_Entrance_Monitoring_Systems\image\Capiz_State_University.png" alt="CAPSU Logo" class="brand-mark">
          School Entrance Gate Monitoring System
        </div>
        <h1 class="hero-title fade-up delay-1">Secured RFID Gate access for <span>Capsunians</span></h1>
        <p class="hero-sub fade-up delay-2">
          Ensuring a safe, smart, and efficient campus entry for students, faculty, and staff.
        </p>
        <div class="hero-badges fade-up delay-3">
          <div class="badge">Admin Card Verified</div>
          <div class="badge">Master Card Accepted</div>
          <div class="badge">Live Scan Status</div>
        </div>
      </section>

      <aside class="login-card fade-up delay-2">
        <div>
          <h2 class="card-title">Access Terminal</h2>
          <p class="card-sub">Scan at the reader. Authorized cards are granted immediate entry.</p>
        </div>
        <div class="scan-panel">
          <div class="scan-header">
            <div class="scan-icon">RFID</div>
            <div>
              <div class="status" id="statusText">Waiting for scan...</div>
              <div class="log" id="scanLog">No scans yet.</div>
            </div>
          </div>
          <div class="pill" id="pillState"><span class="pulse"></span>Listening for RFID scans</div>
        </div>
        <p class="card-sub">Tip: keep the card steady on the reader for 1-2 seconds.</p>
        <?php if (is_local_dev_request()): ?>
          <div class="dev-login">
            <form method="post">
              <button type="submit" name="dev_login" value="1" class="dev-login-btn">Local Dev Login</button>
            </form>
            <p class="dev-login-note">Localhost only. Use this to open the dashboard while developing on this machine.</p>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>

  <script>
    const ui = {
      status: document.getElementById('statusText'),
      log: document.getElementById('scanLog'),
      pill: document.getElementById('pillState')
    };
    const endpoints = {
      adminSignal: '../api/admin/get_admin_scan_signal.php?consume=1',
      adminLogin: '../api/admin/admin_login.php',
      dashboard: 'index.php'
    };
    const copy = {
      notAuthorized: 'Scan detected but not authorized.',
      waiting: 'Waiting for admin or Master Card',
      waitingNew: 'Waiting for a new scan...',
      verifying: 'Admin card detected. Verifying...',
      verifyingPill: 'Verifying admin card',
      networkError: 'Network error. Retrying...',
      scannerError: 'Unable to read scanner. Retrying...'
    };

    const sessionStartMs = Date.now();
    const staleGraceMs = 5000;
    const pollIntervalMs = 1000;

    function setStatus({ status, statusHtml, pill, log }) {
      if (statusHtml !== undefined) {
        ui.status.innerHTML = statusHtml;
      } else if (status !== undefined) {
        ui.status.textContent = status;
      }
      if (pill !== undefined) {
        ui.pill.textContent = pill;
      }
      if (log !== undefined) {
        ui.log.textContent = log;
      }
    }

    function parseSignalTime(value) {
      if (!value) {
        return NaN;
      }
      const normalized = value.includes('T') ? value : value.replace(' ', 'T');
      const parsed = Date.parse(normalized);
      return Number.isNaN(parsed) ? NaN : parsed;
    }

    async function fetchJson(url, options) {
      const res = await fetch(url, options);
      return res.json();
    }

    async function attemptLogin(uid) {
      try {
        const data = await fetchJson(endpoints.adminLogin, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ uid })
        });
        if (data.ok) {
          setStatus({
            statusHtml: `Access granted. Welcome, <strong>${data.name}</strong>.`,
            pill: 'Redirecting...'
          });
          setTimeout(() => {
            window.location.href = endpoints.dashboard;
          }, 600);
        } else {
          const errorMessage = data && data.error ? data.error : copy.notAuthorized;
          setStatus({ status: errorMessage, pill: copy.waiting });
        }
      } catch (err) {
        setStatus({ status: copy.networkError });
      }
    }

    async function pollAdminSignal() {
      try {
        const data = await fetchJson(endpoints.adminSignal);
        if (!data.ok || !data.data) {
          return;
        }
        const signal = data.data;
        const signalMs = signal.ts ? signal.ts * 1000 : parseSignalTime(signal.created_at || '');
        if (Number.isFinite(signalMs) && signalMs < sessionStartMs - staleGraceMs) {
          setStatus({
            log: `Last admin UID: ${signal.uid} (${signal.created_at || 'unknown time'})`,
            status: copy.waitingNew,
            pill: copy.waiting
          });
          return;
        }
        setStatus({
          log: `Last admin UID: ${signal.uid} (${signal.created_at || 'just now'})`,
          status: copy.verifying,
          pill: copy.verifyingPill
        });
        await attemptLogin(signal.uid);
      } catch (err) {
        setStatus({ status: copy.scannerError });
      }
    }

    pollAdminSignal();
    setInterval(pollAdminSignal, pollIntervalMs);
  </script>
</body>
</html>
