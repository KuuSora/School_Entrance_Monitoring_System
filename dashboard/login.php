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
  <title>RFID Admin Access</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap');

    :root {
      --ink: #0b1b2b;
      --ink-dark: #0b1b2b;
      --muted: #5d748a;
      --accent: #3bb4ff;
      --accent-deep: #1f78c1;
      --accent-2: #f7c948;
      --panel: rgba(255, 255, 255, 0.92);
      --panel-strong: #ffffff;
      --stroke: rgba(15, 47, 74, 0.15);
      --glow: rgba(59, 180, 255, 0.25);
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "IBM Plex Sans", "Trebuchet MS", sans-serif;
      color: var(--ink);
      background:
        radial-gradient(circle at 12% 10%, rgba(59, 180, 255, 0.18), transparent 40%),
        radial-gradient(circle at 90% 16%, rgba(247, 201, 72, 0.18), transparent 45%),
        linear-gradient(135deg, #f8fcff 0%, #eef6ff 55%, #fef9e9 100%);
      min-height: 100vh;
    }

    .page {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 36px 24px;
      position: relative;
      overflow: hidden;
    }

    .page::before {
      content: "";
      position: absolute;
      inset: -20% 0 0 0;
      background:
        repeating-linear-gradient(90deg, rgba(15, 47, 74, 0.06) 0 1px, transparent 1px 120px),
        repeating-linear-gradient(0deg, rgba(15, 47, 74, 0.05) 0 1px, transparent 1px 120px);
      opacity: 0.3;
      pointer-events: none;
      z-index: 1;
    }

    .page::after {
      content: "";
      position: absolute;
      inset: 0;
      background: url('../image/capsu_pilar_gate.jpg') center/cover no-repeat;
      opacity: 0.18;
      filter: saturate(1.05) contrast(1.05);
      pointer-events: none;
      z-index: 0;
    }

    .layout {
      width: min(1120px, 100%);
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.9fr);
      gap: 32px;
      position: relative;
      z-index: 2;
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
      font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
      font-weight: 600;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      font-size: 12px;
      color: #4b6b85;
    }

    .brand-mark {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: linear-gradient(150deg, var(--accent) 0%, var(--accent-2) 100%);
      display: grid;
      place-items: center;
      color: #0b1b2b;
      font-weight: 700;
      box-shadow: 0 12px 24px rgba(59, 180, 255, 0.28);
    }

    .hero-title {
      font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
      font-size: clamp(32px, 4vw, 52px);
      font-weight: 700;
      line-height: 1.05;
      margin: 0;
    }

    .hero-title span {
      color: var(--accent-deep);
    }

    .hero-sub {
      margin: 0;
      color: var(--muted);
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
      border: 1px solid rgba(59, 180, 255, 0.35);
      background: rgba(59, 180, 255, 0.12);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: #1f78c1;
    }

    .login-card {
      background: var(--panel);
      border: 1px solid var(--stroke);
      border-radius: 24px;
      box-shadow: 0 24px 50px rgba(15, 47, 74, 0.18);
      padding: 28px;
      display: grid;
      gap: 18px;
      align-content: center;
      backdrop-filter: blur(8px);
    }

    .card-title {
      font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
      font-size: 20px;
      margin: 0;
      color: var(--ink);
    }

    .card-sub {
      margin: 0;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.6;
    }

    .scan-panel {
      display: grid;
      gap: 14px;
      padding: 18px;
      border-radius: 18px;
      border: 1px dashed rgba(15, 47, 74, 0.2);
      background: #f4f9ff;
    }

    .scan-header {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .scan-icon {
      width: 56px;
      height: 56px;
      border-radius: 18px;
      background: rgba(59, 180, 255, 0.16);
      display: grid;
      place-items: center;
      font-weight: 700;
      color: var(--accent-deep);
      font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
      letter-spacing: 1px;
    }

    .status {
      font-size: 14px;
      color: var(--muted);
    }

    .status strong {
      color: var(--ink-dark);
    }

    .log {
      font-size: 12px;
      color: rgba(11, 27, 43, 0.55);
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(59, 180, 255, 0.18);
      color: #1f78c1;
      font-size: 12px;
      font-weight: 600;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid rgba(59, 180, 255, 0.4);
    }

    .pulse {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--accent);
      box-shadow: 0 0 12px var(--glow);
      animation: pulse 1.4s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(0.85); opacity: 0.6; }
      50% { transform: scale(1); opacity: 1; }
    }

    .fade-up {
      animation: fadeUp 0.8s ease both;
    }

    .fade-up.delay-1 { animation-delay: 0.1s; }
    .fade-up.delay-2 { animation-delay: 0.2s; }
    .fade-up.delay-3 { animation-delay: 0.3s; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 900px) {
      .layout {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="layout">
      <section class="hero">
        <div class="brand fade-up">
          <div class="brand-mark">SE</div>
          School Entrance Monitoring
        </div>
        <h1 class="hero-title fade-up delay-1">Secure access for <span>admin</span> operations.</h1>
        <p class="hero-sub fade-up delay-2">
          Present an admin RFID card or the Master Card to unlock the dashboard.
          This access point monitors scans in real time and verifies authorization instantly.
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
      </aside>
    </div>
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
          pillStateEl.textContent = 'Waiting for admin or Master Card';
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
                pillStateEl.textContent = 'Waiting for admin or Master Card';
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
            pillStateEl.textContent = 'Waiting for admin or Master Card';
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
