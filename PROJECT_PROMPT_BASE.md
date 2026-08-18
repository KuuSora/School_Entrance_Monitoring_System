# School Entrance Monitoring System Prompt Base

Use this as the master prompt when recreating or extending this project.

## Prompt

You are building a school entrance monitoring system with PHP, MySQL, HTML, CSS, JavaScript, and an ESP32 RC522 RFID scanner.

Create a complete, organized, working application for tracking school entry and exit using RFID cards. The system must support admin login, dashboard analytics, person registration, personal activity logs, scan history, suspicious scan detection, and device-to-server communication with the ESP32.

## Project Goal

Build a web-based RFID monitoring system for a school entrance that:
- logs IN and OUT scans from RFID cards
- manages students, faculty, staff, visitors, and admins
- shows dashboard statistics and charts
- supports card registration and re-registration
- provides personal activity logs per person
- sends SMS or notification alerts when needed
- supports ESP32 hardware scanning through HTTP API endpoints

## Tech Stack

- Backend: PHP
- Database: MySQL
- Frontend: HTML, CSS, JavaScript
- Charts: Chart.js
- Hardware: ESP32 + MFRC522 RFID reader + LCD + buzzer
- Optional notifications: TextBee SMS gateway and email support

## Main Pages

### 1. Login Page

Create a clean admin login screen for RFID access.
- If the admin is already logged in, redirect to the dashboard.
- Support RFID-based admin login.
- Show a modern, polished UI with a branded hero section and login card.
- Include scan status and feedback for the RFID admin card.

### 2. Dashboard Page

Create the main admin dashboard with authentication protection.
- Redirect unauthenticated users to login.
- Show the logged-in admin name.
- Include logout button.
- Use a sidebar navigation with these areas:
  - Dashboard
  - Register
  - Personal Activity
  - Scan Log
  - Reports

Dashboard sections:
- Today scans with IN/OUT breakdown
- Week scans with average per day
- Month scans with best day
- Active students in the last 7 days
- Inside now totals with role breakdown
- Suspicious alerts in the last 24 hours
- Charts for scan history, role share, and overall IN vs OUT totals
- A register section that prompts scanning a card before registration or editing

### 3. Personal Activity Page

Create a dedicated page for browsing one person’s scan activity.
- Require admin session login.
- Show admin filter dropdown.
- Show person selector dropdown.
- Show admin tags associated with the selected person.
- Display a compact table of scan logs for the selected UID.
- Include a shortcut back to the dashboard.

## Core Features

### Registration System

Support registration for these roles:
- student
- faculty
- staff
- visitor

Each role should store the proper identity fields:
- student: uid, name, student_id, course, school_year, section, optional email, phone, notes
- faculty: uid, name, faculty_id, department, optional email, phone, notes
- staff: uid, name, staff_id, department, optional email, phone, notes
- visitor: uid, name, purpose, valid_until, optional email, phone, notes

Registration rules:
- UID is required.
- Name is required.
- Role-specific ID fields are required for student, faculty, and staff.
- Visitor uses purpose and valid_until.
- Existing records should be upserted rather than duplicated.
- The UI should support viewing an existing registered person and editing their details.

### Scan Logging

Log every RFID scan to the database.
- Detect direction as IN or OUT.
- Accept direction from the device or derive it from GPIO mapping.
- Save the admin UID when the scan is made under an admin session or validated admin context.
- Mark scans as suspicious when direction patterns look inconsistent.
- Identify unknown cards as unregistered.
- Return scan results in JSON.
- If a person has a phone number and the scan direction is IN, send an SMS notification.

### Admin Login and Admin Session

Support RFID-based admin login.
- A special master RFID card should log in as Master Card.
- Only authorized admin UIDs may log in.
- Store admin session data after login.
- Enforce one active admin at a time if that rule is part of the flow.
- Logout should clear the session and active admin state.

### Signal Files for Hardware Sync

Support lightweight JSON-based signal files for ESP32/device polling.
- Register scan signal for preparing registration from the scanner.
- Admin scan signal for admin login verification.
- Device or browser polls these endpoints and can consume the signal once read.

### Notifications

Support outbound notifications for scan events.
- SMS sending should be integrated through TextBee.
- Email support should be available through a configured mailer.
- Store notification records with status, provider, timestamps, and error fields if needed.

## API Endpoints

### Admin APIs

- `api/admin/admin_login.php` - authenticate an admin RFID card
- `api/admin/admin_logout.php` - clear the current admin session and active state
- `api/admin/get_admins.php` - list admins
- `api/admin/get_admin_users.php` - list people associated with an admin filter
- `api/admin/get_admin_scan_signal.php` - read admin scan signal
- `api/admin/report_admin_scan.php` - write admin scan signal

### User APIs

- `api/users/get_users.php` - list all registered people across roles
- `api/users/get_user.php` - fetch one user by UID
- `api/users/get_user_logs.php` - fetch logs for one UID
- `api/users/get_personal_activity.php` - fetch grouped personal activity data
- `api/users/register_user.php` - create or update a user record by role

### Scan APIs

- `api/scans/log_scan.php` - log a scan event and return the result
- `api/scans/get_scans.php` - list scan history with role, admin, and suspicious flags
- `api/scans/get_scan_history.php` - return scan history for charts and analytics
- `api/scans/get_suspicious.php` - return suspicious scan activity

### Signal APIs

- `api/signals/get_register_signal.php` - read register scan signal
- `api/signals/report_register_scan.php` - write register scan signal

### System APIs

- `api/system/db.php` - database connection helper
- `api/system/config.php` - SMTP and TextBee configuration
- `api/system/emailer.php` - email support helper

## Database Tables

Design the database around these tables:

- `students` - student registry data
- `faculty` - faculty registry data
- `staff` - staff registry data
- `visitors` - visitor registry data
- `admins` - admin accounts
- `scans` - all RFID scan events
- `admin_uid_rejections` - rejected admin UID attempts
- `notifications` - SMS or email notification history

Important fields and relationships:
- Each person table uses `uid` as the primary key.
- `scans` stores `uid`, `direction`, `admin_uid`, and `created_at`.
- `notifications` stores `uid`, `phone`, `message`, provider info, status, and timestamps.
- Support views or summary queries for sent notifications and daily metrics.

## ESP32 / RFID Device Behavior

Build the ESP32 sketch so it can:
- connect to Wi-Fi
- read two RC522 readers
- determine scan direction using GPIO inputs
- POST UID and direction to the PHP server
- show scan feedback on the LCD
- beep the buzzer on success
- allow changing the server URL or path through serial commands
- support scanning a UID to register or verify a person

The device should call the server API with form-encoded data and use the server response to show access granted, new user, or error feedback.

## UI and UX Rules

- Make the interface clean, modern, and school/security themed.
- Use a strong dashboard layout with a sidebar and top bar.
- Keep admin actions obvious and fast.
- Use readable cards, tables, badges, and charts.
- Show clear empty states and loading states.
- Keep the register workflow smooth after scanning a card.
- Keep personal activity separate from the dashboard overview.

## Business Rules

- Only authenticated admins can access dashboard and personal activity pages.
- RFID UID format should be validated.
- Existing persons should be updated, not duplicated.
- Unknown cards should be treated as unregistered.
- Admin cards must not be accepted as normal register cards.
- Scan history should preserve direction and admin attribution.
- Suspicious scan detection should be based on scan sequence logic.

## Expected Output When Recreating the Project

When using this prompt to build the project again, produce:
- the PHP pages
- the API endpoints
- the MySQL schema
- the ESP32 sketch
- the CSS and JavaScript needed for the dashboard
- the notification support code
- a working file structure that matches the app flow

## Reuse Note

If you rebuild this project later, paste this prompt first and then customize only the parts you want to change, such as branding, database names, or notification providers.