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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Entrance Display</title>
  <style>
    :root {
      color-scheme: dark;
      --background: #081a33;
      --surface: #102b4b;
      --surface-light: #173f70;
      --text: #edf7ff;
      --muted: #a9c5df;
      --accent: #5aa9e6;
      --success: #72c6e8;
      --danger: #ef7890;
    }

    * { box-sizing: border-box; }

    body {
      min-height: 100vh;
      margin: 0;
      display: grid;
      place-items: center;
      overflow: hidden;
      background:
        radial-gradient(circle at 50% 20%, rgba(90, 169, 230, .3), transparent 42%),
        var(--background);
      color: var(--text);
      font-family: "Segoe UI", Arial, sans-serif;
    }

    .display-shell {
      width: min(94vw, 1320px);
      display: grid;
      justify-items: center;
      gap: clamp(14px, 2.5vh, 28px);
      text-align: center;
    }

    .display-header {
      display: flex;
      width: 100%;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }

    .display-heading {
      margin: 0;
      font-size: clamp(20px, 2.5vw, 34px);
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .display-subtitle {
      margin: 6px 0 0;
      color: var(--muted);
      font-size: clamp(13px, 1.4vw, 18px);
    }

    .display-actions {
      display: flex;
      gap: 10px;
    }

    button {
      border: 1px solid rgba(148, 163, 184, .45);
      border-radius: 9px;
      padding: 10px 14px;
      color: var(--text);
      background: rgba(15, 23, 42, .65);
      cursor: pointer;
      font: inherit;
    }

    button:hover { background: var(--surface-light); }

    .status {
      min-height: 24px;
      color: var(--muted);
      font-size: clamp(13px, 1.4vw, 18px);
    }

    .id-card {
      width: min(88vw, 980px);
      aspect-ratio: 2100 / 1236;
      display: block;
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(114, 198, 232, .55);
      border-radius: 20px;
      background: #ffffff url('../image/idcapsu.png') center / 100% 100% no-repeat;
      box-shadow: 0 30px 80px rgba(0, 0, 0, .4), 0 0 50px rgba(90, 169, 230, .25);
      opacity: 0;
      transform: translateY(12px) scale(.98);
      transition: opacity .25s ease, transform .25s ease;
    }

    .id-card.visible {
      opacity: 1;
      transform: translateY(0) scale(1);
    }

    .id-card-layout {
      width: 100%;
      height: 100%;
      position: relative;
    }

    .photo-wrap {
      position: absolute;
      top: 18.6%;
      left: 67.5%;
      width: 29.8%;
      height: 65.8%;
      overflow: hidden;
      border: 0;
      border-radius: 0;
      background: transparent;
      box-shadow: none;
      z-index: 2;
    }

    .photo-wrap img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: cover;
    }

    .card-info {
      position: absolute;
      top: 38%;
      left: 0;
      width: 67%;
      height: 100%;
      z-index: 2;
      text-align: center;
    }

    .card-name {
      margin: 0;
      color: #000000;
      font-size: clamp(14px, 2.45vw, 36px);
      line-height: 1.05;
      overflow-wrap: anywhere;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      text-transform: uppercase;
      text-shadow: 0 0 4px rgba(255,255,255,0.9);
    }

    .card-department {
      position: absolute;
      top: 13%;
      left: 0;
      width: 100%;
      color: #000000;
      font-size: clamp(10px, 1.45vw, 19px);
      font-weight: 600;
      text-shadow: 0 0 3px rgba(255,255,255,.9);
    }

    .card-role {
      position: absolute;
      top: 35%;
      left: 10%;
      color: #ffffff;
      font-size: clamp(10px, 1.4vw, 18px);
      font-weight: 600;
      text-transform: capitalize;
      text-shadow: 0 1px 3px rgba(0,0,0,.7);
    }

    .card-id {
      position: absolute;
      top: 80.5%;
      left: 67%;
      right: 2%;
      z-index: 2;
      color: #ffffff;
      font-size: clamp(9px, 1.35vw, 17px);
      font-weight: 700;
      text-align: center;
      text-shadow: 0 1px 3px rgba(0,0,0,.7);
    }

    .placeholder {
      width: min(88vw, 980px);
      min-height: min(58vh, 570px);
      display: grid;
      place-items: center;
      border: 2px dashed rgba(148, 163, 184, .45);
      border-radius: 28px;
      color: var(--muted);
      font-size: clamp(22px, 3vw, 42px);
    }

    .hidden { display: none; }

    @media (max-width: 700px) {
      body { overflow: auto; padding: 20px 0; }
      .display-header { align-items: flex-start; flex-direction: column; }
    }
  </style>
</head>
<body>
  <main class="display-shell">
    <header class="display-header">
      <div>
        <h1 class="display-heading">School Entrance Monitoring</h1>
        <p class="display-subtitle">Student entrance display</p>
      </div>
      <div class="display-actions">
        <button id="fullscreenButton" type="button">Full screen</button>
        <button id="closeButton" type="button">Close</button>
      </div>
    </header>

    <div id="status" class="status" aria-live="polite">Waiting for the next scan...</div>

    <section id="placeholder" class="placeholder">
      Waiting for a student card scan...
    </section>

    <section id="idCard" class="id-card hidden" aria-live="polite">
      <div class="id-card-layout">
        <div class="photo-wrap">
          <img id="photo" src="../image/nophoto_s.png" alt="Student photo">
        </div>
        <div class="card-info">
          <h2 id="name" class="card-name">Student</h2>
          <div id="department" class="card-department"></div>
          <div id="role" class="card-role">Student</div>
        </div>
        <div id="identifier" class="card-identifier card-id"></div>
      </div>
    </section>
  </main>

  <script>
    const placeholderEl = document.getElementById('placeholder');
    const idCardEl = document.getElementById('idCard');
    const statusEl = document.getElementById('status');
    const photoEl = document.getElementById('photo');
    const nameEl = document.getElementById('name');
    const departmentEl = document.getElementById('department');
    const roleEl = document.getElementById('role');
    const identifierEl = document.getElementById('identifier');
    let latestScanId = 0;
    let requestToken = 0;

    function normalizeUid(uid) {
      return String(uid || '').replace(/[^0-9a-f]/gi, '').toUpperCase();
    }

    function photoSrc(photo) {
      if (!photo || !/^[A-Za-z0-9_.-]+\.(jpg|jpeg|png|gif|webp)$/i.test(photo)) {
        return '../image/nophoto_s.png';
      }
      return '../uploads/' + encodeURIComponent(photo);
    }

    function showPlaceholder(message) {
      placeholderEl.textContent = message;
      placeholderEl.classList.remove('hidden');
      idCardEl.classList.add('hidden');
      idCardEl.classList.remove('visible');
    }

    async function showLatestUser(scan) {
      const token = ++requestToken;
      const response = await fetch('../api/users/get_user.php?uid=' + encodeURIComponent(scan.uid));
      const result = await response.json();
      if (token !== requestToken || !result.ok || !result.data) return;

      const user = result.data;
      if (normalizeUid(user.uid) !== normalizeUid(scan.uid)) return;
      const role = String(user.role || 'student');
      const identifier = role === 'student' && user.student_id
        ? 'Student ID: ' + user.student_id
        : role === 'faculty' && user.faculty_id
          ? 'Faculty ID: ' + user.faculty_id
          : role === 'staff' && user.staff_id
            ? 'Staff ID: ' + user.staff_id
            : 'UID: ' + (user.uid || '-');

      photoEl.src = photoSrc(user.photo);
      photoEl.onerror = () => { photoEl.onerror = null; photoEl.src = '../image/nophoto_s.png'; };
      nameEl.textContent = String(user.name || 'Unknown').toUpperCase();
      departmentEl.textContent = user.department || user.course || '';
      roleEl.textContent = role;
      identifierEl.textContent = identifier;
      placeholderEl.classList.add('hidden');
      idCardEl.classList.remove('hidden');
      requestAnimationFrame(() => idCardEl.classList.add('visible'));
      statusEl.textContent = 'Latest scan: ' + (scan.created_at || '');
    }

    async function pollLatestScan() {
      try {
        const response = await fetch('../api/scans/get_scans.php?limit=1', { cache: 'no-store' });
        if (!response.ok) throw new Error('Scan request failed');
        const result = await response.json();
        const scan = Array.isArray(result.data) ? result.data[0] : null;
        if (!result.ok || !scan) {
          showPlaceholder('Waiting for a student card scan...');
          return;
        }
        const scanId = Number(scan.id);
        if (scanId !== latestScanId) {
          latestScanId = scanId;
          if (scan.name === 'New User' || !scan.name) {
            showPlaceholder('Unregistered card detected');
          } else {
            await showLatestUser(scan);
          }
        }
      } catch (error) {
        statusEl.textContent = 'Display temporarily disconnected. Retrying...';
        console.error(error);
      }
    }

    document.getElementById('fullscreenButton').addEventListener('click', () => {
      if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();
    });
    document.getElementById('closeButton').addEventListener('click', () => window.close());
    pollLatestScan();
    setInterval(pollLatestScan, 1500);
  </script>
</body>
</html>
