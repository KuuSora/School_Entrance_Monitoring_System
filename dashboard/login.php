<?php
session_start();
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
  <title>RFID Admin Login</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&display=swap');

    :root {
      --bg: #f7f3ec;
      --ink: #1d1916;
      --accent: #0d5c4b;
      --accent-ink: #0b3e32;
      --card: #ffffff;
      --stroke: #d9d0c3;
      --muted: #5c4f45;
    }
    body {
      margin: 0;
      font-family: "Manrope", "Trebuchet MS", sans-serif;
      background: radial-gradient(circle at 12% 12%, rgba(13, 92, 75, 0.14), transparent 50%),
        linear-gradient(135deg, #f7f3ec 0%, #e3efe9 100%);
      color: var(--ink);
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px;
    }
    .card {
      width: min(560px, 100%);
      background: var(--card);
      border: 1px solid var(--stroke);
      border-radius: 20px;
      box-shadow: 0 18px 40px rgba(31, 26, 22, 0.16);
      padding: 28px;
      display: grid;
      gap: 18px;
    }
    .title {
      font-size: 22px;
      font-weight: 700;
      color: var(--accent-ink);
      letter-spacing: 0.3px;
    }
    .sub {
      margin: 0;
      color: var(--muted);
      font-size: 14px;
    }
    .scan-box {
      border: 1px dashed #ccbfb1;
      border-radius: 16px;
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      background: #fbf7f0;
    }
    .scan-icon {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      background: #e3f1ec;
      display: grid;
      place-items: center;
      color: var(--accent);
      font-weight: 700;
    }
    .status {
      font-size: 14px;
      color: var(--muted);
    }
    .status strong {
      color: var(--accent-ink);
    }
    .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #e3f1ec;
      color: var(--accent-ink);
      font-size: 12px;
      font-weight: 600;
      padding: 6px 10px;
      border-radius: 999px;
    }
    .log {
      font-size: 12px;
      color: var(--muted);
    }
  </style>
</head>
<body>
  <div class="card">
    <div>
      <div class="title">Security Access Required</div>
      <p class="sub">Scan an admin RFID card to open the dashboard.</p>
    </div>
    <div class="scan-box">
      <div class="scan-icon">RF</div>
      <div>
        <div class="status" id="statusText">Waiting for scan...</div>
        <div class="log" id="scanLog">No scans yet.</div>
      </div>
    </div>
    <div class="pill" id="pillState">Listening for RFID scans</div>
  </div>

  <script>
    const statusTextEl = document.getElementById('statusText');
    const scanLogEl = document.getElementById('scanLog');
    const pillStateEl = document.getElementById('pillState');
    let lastScanId = 0;

    async function attemptLogin(uid) {
      try {
        const res = await fetch('../api/admin_login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ uid })
        });
        const data = await res.json();
        if (data.ok) {
          statusTextEl.innerHTML = `Access granted. Welcome, <strong>${data.name}</strong>.`;
          pillStateEl.textContent = 'Redirecting...';
          setTimeout(() => {
            window.location.href = 'index.php';
          }, 600);
        } else {
          statusTextEl.textContent = 'Scan detected but not authorized.';
          pillStateEl.textContent = 'Waiting for admin card';
        }
      } catch (err) {
        statusTextEl.textContent = 'Network error. Retrying...';
      }
    }

    async function pollLatestScan() {
      try {
        const res = await fetch('../api/get_last_scan.php');
        const data = await res.json();
        if (!data.ok || !data.data) {
          return;
        }
        const scan = data.data;
        if (scan.id && scan.id > lastScanId) {
          lastScanId = scan.id;
          scanLogEl.textContent = `Last scan UID: ${scan.uid} (${scan.created_at})`;
          if (scan.is_admin && scan.uid) {
            // check server-side block to avoid immediate re-login after logout
            try {
              const blkRes = await fetch('../api/get_auto_login_block.php');
              const blk = await blkRes.json();
              const blockedUntil = blk && blk.blocked_until ? blk.blocked_until : 0;
              const nowSec = Math.floor(Date.now() / 1000);
              if (blockedUntil && blockedUntil > nowSec) {
                statusTextEl.textContent = 'Auto-login temporarily blocked';
                pillStateEl.textContent = 'Waiting for admin card';
                return;
              }
            } catch (e) {
              // ignore block check errors and proceed
            }
            statusTextEl.textContent = 'Admin card detected. Verifying...';
            pillStateEl.textContent = 'Verifying admin card';
            await attemptLogin(scan.uid);
          } else {
            statusTextEl.textContent = 'Scan detected but not authorized.';
            pillStateEl.textContent = 'Waiting for admin card';
          }
        }
      } catch (err) {
        statusTextEl.textContent = 'Unable to read scanner. Retrying...';
      }
    }

    pollLatestScan();
    setInterval(pollLatestScan, 1000);
  </script>
</body>
</html>
