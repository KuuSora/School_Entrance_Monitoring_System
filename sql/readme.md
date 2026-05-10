# ESP32 RC522 MySQL Logger

Simple ESP32 RFID scanner using RC522 (SPI), I2C 16x2 LCD, and a buzzer. On each scan, it POSTs the card UID to a PHP endpoint and shows the result on the LCD.

## Hardware
- ESP32 DevKit (any ESP32 with SPI and I2C)
- RC522 RFID module (SPI)
- 16x2 I2C LCD (PCF8574 backpack, default address 0x27)
- Active buzzer

## Wiring (ESP32)
### RC522 (SPI)
- SDA/SS -> GPIO5
- RST -> GPIO27
- SCK -> GPIO18 (default VSPI)
- MISO -> GPIO19 (default VSPI)
- MOSI -> GPIO23 (default VSPI)
- 3.3V -> 3V3
- GND -> GND

### I2C LCD
- SDA -> GPIO21 (default I2C SDA)
- SCL -> GPIO22 (default I2C SCL)
- VCC -> 5V or 3.3V (match your LCD backpack)
- GND -> GND

### Buzzer
- + -> GPIO4
- - -> GND

## Software Setup
1) Install Arduino libraries:
   - WiFi
   - HTTPClient
   - SPI
   - MFRC522
   - Wire
   - LiquidCrystal_I2C
2) Open the sketch and set:
   - WIFI_SSID
   - WIFI_PASS
   - SERVER_URL
3) Upload to the ESP32.

## Server Endpoint
The sketch POSTs form data to the PHP endpoint:

- URL: SERVER_URL
- Method: POST
- Content-Type: application/x-www-form-urlencoded
- Body: uid=AA:BB:CC:DD&gpio=5

Direction mapping:
- GPIO5 = OUT
- GPIO25 = IN

You can also send an explicit direction:
- Body: uid=AA:BB:CC:DD&direction=IN

Expected responses (examples):
- Access Granted with name: {"name":"John"}
- New card: text containing "New Card"

## Registration Endpoint
Register or update a card with role-specific details:

- URL: /server/api/register_user.php
- Method: POST
- Content-Type: application/x-www-form-urlencoded
- Body (common fields):
   - uid (required)
   - name (required)
   - role (student | faculty | staff | visitor)
   - phone
   - notes

Role-specific fields:
- student: student_id (required), course, school_year, section
- faculty: faculty_id (required), department
- staff: staff_id (required), department
- visitor: purpose, valid_until (YYYY-MM-DD)

## Database Update
The scans table now stores direction (IN/OUT).

If you already created the table, run:
```sql
ALTER TABLE scans ADD COLUMN direction VARCHAR(8) NOT NULL DEFAULT 'IN';
```

The people records are now split into separate tables. If you already created
the old users table, drop it and create these tables:
```sql
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS students (
   uid VARCHAR(64) PRIMARY KEY,
   name VARCHAR(128) NOT NULL,
   student_id VARCHAR(64) NOT NULL,
   course VARCHAR(128) NULL,
   school_year VARCHAR(32) NULL,
   section VARCHAR(32) NULL,
   phone VARCHAR(32) NULL,
   notes VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS faculty (
   uid VARCHAR(64) PRIMARY KEY,
   name VARCHAR(128) NOT NULL,
   faculty_id VARCHAR(64) NOT NULL,
   department VARCHAR(128) NULL,
   phone VARCHAR(32) NULL,
   notes VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS staff (
   uid VARCHAR(64) PRIMARY KEY,
   name VARCHAR(128) NOT NULL,
   staff_id VARCHAR(64) NOT NULL,
   department VARCHAR(128) NULL,
   phone VARCHAR(32) NULL,
   notes VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS visitors (
   uid VARCHAR(64) PRIMARY KEY,
   name VARCHAR(128) NOT NULL,
   purpose VARCHAR(128) NULL,
   valid_until DATE NULL,
   phone VARCHAR(32) NULL,
   notes VARCHAR(255) NULL
);
```

## Notes
- RC522 uses 3.3V only. Do not power it with 5V.
- If your LCD address is not 0x27, change it in the sketch.

---

## Project Prompt (Professional)
You are a senior full-stack developer. Summarize and extend this RFID-based School Entrance Monitoring System. The system consists of an ESP32 + RC522 RFID reader that posts scans to a PHP API, a MySQL database that stores people and scan logs, and a web dashboard for admins.

Goals:
- Keep current URLs and file paths working (Apache/XAMPP setup).
- Maintain the scan flow: RFID card -> API -> database -> dashboard updates.
- Preserve role-based registration (student, faculty, staff, visitor) and admin login.
- Respect existing schema and migrations; add fields only when needed.

Architecture Overview:
- Device: ESP32 posts form data to log_scan.php with uid and gpio/direction.
- API (PHP): Handles auth, scan logging, user registration, reporting, and email alerts.
- DB (MySQL): Stores people in separate tables (students, faculty, staff, visitors), scans, admins, and admin_uid_rejections.
- Dashboard (PHP + JS + CSS): Admin UI for stats, scan log, registration, and suspicious activity.

Key Data Model:
- students(uid, name, student_id, course, school_year, section, email, phone, notes)
- faculty(uid, name, faculty_id, department, email, phone, notes)
- staff(uid, name, staff_id, department, email, phone, notes)
- visitors(uid, name, purpose, valid_until, email, phone, notes)
- admins(uid, name)
- scans(id, uid, direction, admin_uid, created_at)
- admin_uid_rejections(id, posted_admin_uid, scanned_uid, direction, reason, ip, user_agent, created_at)

Core API Endpoints:
- log_scan.php: validates uid + direction, logs scans, resolves user name, logs rejected admin_uid attempts, sends email notification if available.
- register_user.php: upserts role-specific user data.
- get_users.php / get_user.php: list users or fetch a single user profile.
- get_scans.php: returns scan history, stats, suspicious flags.
- get_suspicious.php: consecutive IN/IN or OUT/OUT detection.
- get_user_logs.php: per-user scan history.
- admin_login.php / admin_logout.php: admin session management.
- migrate_ensure_master_admin.php: inserts Master Card admin if missing.
- migrate_backfill_admin_uid.php: backfills scans admin_uid.
- migrate_create_admin_rejections.php: ensures admin_uid_rejections table.
- migrate_add_email_columns.php: adds email columns + admin_uid to scans if missing.

Scan Flow:
1) ESP32 reads card -> posts uid + gpio (5=OUT, 25=IN) to log_scan.php.
2) API normalizes uid, determines direction, resolves user role/name.
3) API logs scan to scans table (with admin_uid if available).
4) API returns JSON response; dashboard polls get_scans.php for updates.
5) If user email exists and SMTP is enabled, send email alert.

Admin Flow:
- Admin logs in by scanning an admin card (admin_login.php). Session stored in PHP.
- Admin dashboard shows stats, scan log, suspicious alerts, and registration form.

Email Alerts:
- Config in api/config.php; SMTP send via api/emailer.php.
- Triggered on each scan for users with valid email.

Constraints:
- Keep compatible with XAMPP/Apache and current URL paths.
- Use existing database schema and migrations; avoid breaking API responses.
- Maintain JSON response shapes for the dashboard frontend.

Deliverables:
- Clear, minimal changes with backward compatibility.
- Update schema via migrations when needed.
- Keep UI modern and readable.
