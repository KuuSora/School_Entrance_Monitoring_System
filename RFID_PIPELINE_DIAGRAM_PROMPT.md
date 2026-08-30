# RFID Access Control Pipeline — Image Prompt

## Single Prompt (Copy & Paste)

```
Create a clean technical infographic (16:9, 1920x1080) showing an RFID School Entrance Monitoring System pipeline flowing left-to-right in 5 connected stages on a white background with blueprint aesthetic.

STAGE 1 — CARD SCAN: Hand holding RFID card near RC522 reader module (green PCB with coil). Dashed RF waves between them. Labels: "RFID Card (UID)", "RC522 Reader", "13.56MHz RF Field". Abbreviations: RFID, UID, PICC.

STAGE 2 — PROCESS: ESP32 DevKit V1 (blue PCB, WiFi antenna) with 3 LEDs (green=IN, red=OUT, blue=ADMIN) and push button. Labels: "ESP32 + FreeRTOS", "GPIO", "SPI". Pin callouts: GPIO 25/18/23/19/27 to RC522, GPIO 2/15/13 to LEDs, GPIO 4 to button.

STAGE 3 — TRANSMIT: WiFi waves from ESP32 to router to cloud. JSON packet floating mid-air. Labels: "WiFi 802.11", "HTTP POST/GET", "JSON {uid, direction}". API endpoints: log_scan.php, admin_login.php, get_user.php.

STAGE 4 — VERIFY: Server icon + MySQL cylinder + 5 role table cards (students, faculty, staff, visitors, admins). Decision diamond: "REGISTERED?" → Yes/No. Labels: "Apache PHP", "MySQL", "scans table". Abbreviations: PHP, SQL, XAMPP.

STAGE 5 — OUTPUT: Laptop showing dashboard (stat cards, chart, scan log table) + smartphone showing SMS notification. Green checkmark (granted) / red X (denied). Labels: "Web Dashboard", "Chart.js", "SMS (TextBee)", "SMTP Email".

BOTTOM LEGEND: Color-coded categories (Hardware=green, Network=blue, Server=purple, Database=orange, Dashboard=teal). Abbreviation table: RFID, UID, ESP32, GPIO, SPI, FreeRTOS, WiFi, HTTP, JSON, API, PHP, MySQL, SMS, SMTP.

TOP TITLE: "RFID School Entrance Monitoring System — Access Control Pipeline".

STYLE: Isometric 3D components, flat design, subtle shadows, sans-serif labels on colored pills, dark gray directional arrows between stages.
```

---

## Component Reference (Quick)

| Component | Label | Abbreviation |
|---|---|---|
| RFID card + reader | RC522 Module | RFID, UID, PICC |
| Microcontroller | ESP32 DevKit V1 | GPIO, SPI, FreeRTOS |
| Wireless | WiFi 802.11 b/g/n | HTTP, JSON, API |
| Backend | Apache + PHP | PHP, XAMPP |
| Database | MySQL | SQL, RDBMS |
| Frontend | Web Dashboard | HTML, CSS, JS |
| Notifications | SMS/Email | SMS, SMTP |
