#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <Preferences.h>

// RC522 wiring (SPI)
#define SS_PIN_1 5
#define SS_PIN_2 25
#define RST_PIN 27  // CHANGED FROM 22 TO 27 TO FREE UP I2C FOR LCD

const char* WIFI_SSID = "DESKTOP-FTP1D16 9697";
const char* WIFI_PASS = "12345678";
const char* DEFAULT_SERVER_PATH = "/server/School_Entrance_Monitoring_System/api/log_scan.php";
const bool DEBUG_SERIAL = true;
const int BUZZER_PIN = 4;

Preferences prefs;
String serverTarget;
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

String loadServerTarget() {
  prefs.begin("config", true);
  String target = prefs.getString("server_url", "");
  prefs.end();

  if (target.length() == 0) {
    return String(DEFAULT_SERVER_PATH);
  }
  return target;
}

bool saveServerTarget(const String& target) {
  prefs.begin("config", false);
  size_t written = prefs.putString("server_url", target);
  prefs.end();
  return written > 0;
}

bool clearServerUrl() {
  prefs.begin("config", false);
  bool removed = prefs.remove("server_url");
  prefs.end();
  return removed;
}

String normalizePath(String path) {
  path.trim();
  if (path.length() == 0) {
    return String(DEFAULT_SERVER_PATH);
  }
  if (!path.startsWith("/")) {
    path = "/" + path;
  }
  return path;
}

String buildServerUrl(const String& target) {
  if (target.startsWith("http://") || target.startsWith("https://")) {
    return target;
  }

  String path = normalizePath(target);
  IPAddress gw = WiFi.gatewayIP();
  return "http://" + gw.toString() + path;
}

void handleSerialCommands() {
  if (!Serial.available()) {
    return;
  }

  String line = Serial.readStringUntil('\n');
  line.trim();
  if (line.length() == 0) {
    return;
  }

  if (line.startsWith("SET_URL ")) {
    String newTarget = line.substring(8);
    newTarget.trim();
    if (newTarget.length() == 0) {
      Serial.println("SET_URL missing value");
      return;
    }
    if (saveServerTarget(newTarget)) {
      serverTarget = newTarget;
      Serial.print("Server target updated: ");
      Serial.println(serverTarget);
      if (WiFi.status() == WL_CONNECTED) {
        Serial.print("Resolved URL: ");
        Serial.println(buildServerUrl(serverTarget));
      } else {
        Serial.println("WiFi not connected; URL will resolve after connect");
      }
    } else {
      Serial.println("Failed to save Server target");
    }
    return;
  }

  if (line.startsWith("SET_PATH ")) {
    String newPath = line.substring(9);
    newPath.trim();
    if (newPath.length() == 0) {
      Serial.println("SET_PATH missing value");
      return;
    }
    newPath = normalizePath(newPath);
    if (saveServerTarget(newPath)) {
      serverTarget = newPath;
      Serial.print("Server path updated: ");
      Serial.println(serverTarget);
      if (WiFi.status() == WL_CONNECTED) {
        Serial.print("Resolved URL: ");
        Serial.println(buildServerUrl(serverTarget));
      } else {
        Serial.println("WiFi not connected; URL will resolve after connect");
      }
    } else {
      Serial.println("Failed to save Server path");
    }
    return;
  }

  if (line == "GET_URL") {
    Serial.print("Server URL: ");
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println(buildServerUrl(serverTarget));
    } else {
      Serial.println("(WiFi not connected)");
    }
    return;
  }

  if (line == "GET_TARGET") {
    Serial.print("Server target: ");
    Serial.println(serverTarget);
    return;
  }

  if (line == "RESET_URL") {
    if (clearServerUrl()) {
      serverTarget = String(DEFAULT_SERVER_PATH);
      Serial.print("Server target reset: ");
      Serial.println(serverTarget);
      if (WiFi.status() == WL_CONNECTED) {
        Serial.print("Resolved URL: ");
        Serial.println(buildServerUrl(serverTarget));
      }
    } else {
      Serial.println("No stored Server URL to clear");
    }
    return;
  }

  Serial.println("Unknown command. Use GET_URL, GET_TARGET, SET_URL <url>, SET_PATH <path>, or RESET_URL");
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
    String url = buildServerUrl(serverTarget);
    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String body = "uid=" + uid + "&gpio=" + String(gpioPin);
    int code = http.POST(body);
    String resp = http.getString();
    if (DEBUG_SERIAL) {
      Serial.print("POST ");
      Serial.println(url);
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

  serverTarget = loadServerTarget();
  if (DEBUG_SERIAL) {
    Serial.print("Server target: ");
    Serial.println(serverTarget);
    Serial.println("Commands: GET_URL, GET_TARGET, SET_URL <url>, SET_PATH <path>, RESET_URL");
  }

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
  if (DEBUG_SERIAL) {
    Serial.print("Resolved URL: ");
    Serial.println(buildServerUrl(serverTarget));
  }

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
  handleSerialCommands();
  bool handled = handleScan(mfrc522_1, SS_PIN_1);
  if (!handled) {
    handled = handleScan(mfrc522_2, SS_PIN_2);
  }
  if (!handled) {
    delay(50);
  }
}