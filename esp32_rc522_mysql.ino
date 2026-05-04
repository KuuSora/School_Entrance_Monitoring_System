#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// RC522 wiring (SPI)
#define SS_PIN_1 5
#define SS_PIN_2 25
#define RST_PIN 27  // CHANGED FROM 22 TO 27 TO FREE UP I2C FOR LCD

const char* WIFI_SSID = "DESKTOP-FTP1D16 9697";
const char* WIFI_PASS = "12345678";
const char* SERVER_URL = "http://192.168.68.141/server/api/log_scan.php";
const bool DEBUG_SERIAL = true;
const int BUZZER_PIN = 4;
void debugPrintWifiInfo() {
  if (!DEBUG_SERIAL) {
    return;
  }
  Serial.print("WiFi status: ");
  Serial.println(WiFi.status());
  Serial.print("SSID: ");
  Serial.println(WiFi.SSID());
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  Serial.print("RSSI: ");
  Serial.println(WiFi.RSSI());
}

const char* httpCodeToText(int code) {
  switch (code) {
    case HTTP_CODE_OK: return "200 OK";
    case HTTP_CODE_BAD_REQUEST: return "400 Bad Request";
    case HTTP_CODE_UNAUTHORIZED: return "401 Unauthorized";
    case HTTP_CODE_FORBIDDEN: return "403 Forbidden";
    case HTTP_CODE_NOT_FOUND: return "404 Not Found";
    case HTTP_CODE_METHOD_NOT_ALLOWED: return "405 Method Not Allowed";
    case HTTP_CODE_INTERNAL_SERVER_ERROR: return "500 Server Error";
    default: return "(unknown)";
  }
}


MFRC522 mfrc522_1(SS_PIN_1, RST_PIN);
MFRC522 mfrc522_2(SS_PIN_2, RST_PIN);
LiquidCrystal_I2C lcd(0x27, 16, 2); // 0x27 is the default I2C address for most LCDs

String uidToString(MFRC522::Uid* uid) {
  String out = "";
  for (byte i = 0; i < uid->size; i++) {
    if (uid->uidByte[i] < 0x10) {
      out += "0";
    }
    out += String(uid->uidByte[i], HEX);
    if (i < uid->size - 1) {
      out += ":";
    }
  }
  out.toUpperCase();
  return out;
}


void beepOk() {
  digitalWrite(BUZZER_PIN, HIGH);
  delay(100);
  digitalWrite(BUZZER_PIN, LOW);
}

bool handleScan(MFRC522& reader, int gpioPin) {
  if (!reader.PICC_IsNewCardPresent()) {
    return false;
  }
  if (!reader.PICC_ReadCardSerial()) {
    if (DEBUG_SERIAL) {
      Serial.println("Card read failed");
    }
    return false;
  }

  String uid = uidToString(&reader.uid);
  Serial.print("UID: ");
  Serial.println(uid);
  beepOk();

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Scanning...");

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(SERVER_URL);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String body = "uid=" + uid + "&gpio=" + String(gpioPin);
    int code = http.POST(body);
    String resp = http.getString();
    if (DEBUG_SERIAL) {
      Serial.print("POST ");
      Serial.println(SERVER_URL);
      Serial.print("HTTP ");
      Serial.print(code);
      Serial.print(" ");
      Serial.println(httpCodeToText(code));
      Serial.print("Response: ");
      Serial.println(resp);
    }
    http.end();

    // Show result on LCD
    if (code == 200) {
      lcd.clear();
      if (resp.indexOf("Access Granted") != -1) {
        // Extract Name from JSON {"name":"John"}
        int nameStart = resp.indexOf("\"name\":\"") + 8;
        int nameEnd = resp.indexOf("\"", nameStart);
        String name = resp.substring(nameStart, nameEnd);

        lcd.setCursor(0, 0);
        lcd.print("Access Granted");
        lcd.setCursor(0, 1);
        lcd.print(name.substring(0, 16)); // Max 16 chars
      } else if (resp.indexOf("New Card") != -1) {
        lcd.setCursor(0, 0);
        lcd.print("New Card");
        lcd.setCursor(0, 1);
        lcd.print("Please Register");
      }
    } else {
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Network Error");
      lcd.setCursor(0, 1);
      lcd.print(code);
    }
  } else {
    Serial.println("WiFi disconnected");
    debugPrintWifiInfo();
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("WiFi Disconnect");
  }

  reader.PICC_HaltA();
  reader.PCD_StopCrypto1();

  delay(3000); // Leave the message on the screen for 3 seconds
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Ready to Scan");

  return true;
}

void setup() {
  Serial.begin(115200);
  delay(100);

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  pinMode(SS_PIN_1, OUTPUT);
  pinMode(SS_PIN_2, OUTPUT);
  digitalWrite(SS_PIN_1, HIGH);
  digitalWrite(SS_PIN_2, HIGH);

  WiFi.begin(WIFI_SSID, WIFI_PASS);

  // Initialize LCD
  lcd.init();
  lcd.backlight();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Connecting WiFi");

  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    lcd.print(".");
  }
  Serial.println(" connected");
  debugPrintWifiInfo();

  SPI.begin();
  mfrc522_1.PCD_Init();
  mfrc522_2.PCD_Init();
  Serial.println("RC522 readers ready");
  if (DEBUG_SERIAL) {
    byte v1 = mfrc522_1.PCD_ReadRegister(MFRC522::VersionReg);
    byte v2 = mfrc522_2.PCD_ReadRegister(MFRC522::VersionReg);
    Serial.print("RC522 #1 version: 0x");
    Serial.println(v1, HEX);
    Serial.print("RC522 #2 version: 0x");
    Serial.println(v2, HEX);
  }

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Ready to Scan");
}

void loop() {
  bool handled = handleScan(mfrc522_1, SS_PIN_1);
  if (!handled) {
    handled = handleScan(mfrc522_2, SS_PIN_2);
  }
  if (!handled) {
    delay(50);
  }
}