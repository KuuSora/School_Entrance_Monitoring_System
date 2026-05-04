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
