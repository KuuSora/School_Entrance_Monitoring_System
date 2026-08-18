# Portable Scanner Wiring Guide

## Hardware Connections

### ESP32 Pinout for Portable Scanner

| Component | ESP32 Pin | Notes |
|-----------|-----------|-------|
| **RC522 RFID** | | |
| SDA/SS | GPIO 25 | SPI Chip Select |
| SCK | GPIO 18 | Hardware SPI SCK |
| MOSI | GPIO 23 | Hardware SPI MOSI |
| MISO | GPIO 19 | Hardware SPI MISO |
| RST | GPIO 27 | Reset |
| 3.3V | 3.3V | Power |
| GND | GND | Ground |
| **Button** | | |
| One leg | GPIO 4 | Input with internal pull-up |
| Other leg | GND | Ground (active LOW) |
| **LED IN (Green)** | | |
| Anode (+) | GPIO 2 | Through 220Ω resistor |
| Cathode (-) | GND | |
| **LED OUT (Red)** | | |
| Anode (+) | GPIO 15 | Through 220Ω resistor |
| Cathode (-) | GND | |
| **LED ADMIN (Blue)** | | |
| Anode (+) | GPIO 13 | Through 220Ω resistor |
| Cathode (-) | GND | |

---

## Wiring Diagram

```
                    ESP32 DevKit V1
                   ┌─────────────────┐
                   │                 │
            3.3V ──┤              ┌──┤ GPIO 25 ──► RC522 SDA
                   │              │  │
            GND ───┤              │  │
                   │              │  │
            GPIO 18 ┼──────────────┼──► RC522 SCK
                   │              │  │
            GPIO 23 ┼──────────────┼──► RC522 MOSI
                   │              │  │
            GPIO 19 ┼──────────────┼──► RC522 MISO
                   │              │  │
            GPIO 27 ┼──────────────┼──► RC522 RST
                   │              │  │
            GPIO 4  ┼──────────────┼──► Button ─── GND
                   │              │  │
            GPIO 2  ┼────[220Ω]───┼──► LED Green (IN) ─── GND
                   │              │  │
            GPIO 15 ┼────[220Ω]───┼──► LED Red (OUT) ─── GND
                   │              │  │
            GPIO 13 ┼────[220Ω]───┼──► LED Blue (ADMIN) ─ GND
                   │              │  │
                   └─────────────────┘
```

---

## Button Operation

| Press Type | Action | LED Indication |
|------------|--------|----------------|
| **Single press** | Cycle mode: IN → OUT → ADMIN → IN | Corresponding LED lights |
| **Double press** | Skip one mode (IN → ADMIN, OUT → IN, ADMIN → OUT) | Corresponding LED lights |
| **Triple press** | Jump directly to ADMIN mode | Blue LED lights |
| **Long press (2+ sec)** | Reset to IN mode | Green LED lights |

---

## LED Indicators

| Mode | LED State | Description |
|------|-----------|-------------|
| **IN** | Green **ON** | Entry scanning mode |
| **OUT** | Red **ON** | Exit scanning mode |
| **ADMIN** | Blue **BLINKING** (500ms) | Admin login mode - waiting for admin card |

---

## Power Requirements

- **ESP32**: 5V via USB or 3.3V regulated
- **RC522**: 3.3V only (max 3.6V) - **DO NOT connect to 5V**
- **LEDs**: 220Ω current limiting resistors required
- **Button**: Uses internal pull-up (no external resistor needed)

---

## Software Files Updated

1. `esp32_rc522_mysql/esp32_rc522_mysql.ino` - Main portable scanner
2. `esp32_rc522_mysql/New folder/esp32_rc522_admin_register.ino` - Admin register portable

Both files now include:
- Button debouncing (50ms)
- Multi-press detection (1 second window)
- Long press detection (2 seconds)
- LED state management
- Admin mode LED blinking

---

## Testing

1. Upload code to ESP32
2. Open Serial Monitor (115200 baud)
3. Press button once - Green LED should light, serial shows "Mode changed to: IN"
4. Press again - Red LED lights, "Mode changed to: OUT"
5. Press again - Blue LED blinks, "Mode changed to: ADMIN"
6. Press again - Back to Green (IN)
7. Hold button 2+ seconds - Resets to IN (Green)
8. Triple press quickly - Jumps to ADMIN (Blue blinking)