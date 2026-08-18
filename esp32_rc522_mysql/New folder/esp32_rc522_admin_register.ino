#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Preferences.h>

// Single RC522 wiring (SPI)
#define SS_PIN 25
#define RST_PIN 27  // keep 27 to free up I2C for LCD

// Button and LED pins for portable mode
#define BUTTON_PIN 4      // SX button input with internal pull-up
#define LED_IN_PIN 2      // LED for IN mode (Green)
#define LED_OUT_PIN 15    // LED for OUT mode (Red)
#define LED_ADMIN_PIN 13  // LED for ADMIN mode (Blue)

// Connect to the 2-scanner hotspot
const char* WIFI_SSID = "SEMS-ESP32";
const char* WIFI_PASS = "sems12345";
IPAddress LOCAL_IP(192, 168, 4, 100);
IPAddress GATEWAY(192, 168, 4, 1);
IPAddress SUBNET(255, 255, 255, 0);
const char* SERVER_IP = "192.168.4.2";

// This is the ROOT of the api/ folder only
const char* DEFAULT_SERVER_PATH = "/School_Entrance_Monitoring_Systems/api";
const bool DEBUG_SERIAL = true;

// Subfolders under api/
const char* SUBFOLDER_ADMIN = "admin";
const char* SUBFOLDER_SIGNALS = "signals";
const char* SUBFOLDER_USERS = "users";
const char* SUBFOLDER_SCANS = "scans";

// Master card UID (must match server settings)
const char* MASTER_UID = "97:2A:59:06";

// Scanner direction modes for portable scanner
enum DirectionMode {
  MODE_IN = 0,
  MODE_OUT = 1,
  MODE_ADMIN = 2
};

DirectionMode currentMode = MODE_IN;
String DIRECTION = "IN";  // Will be updated based on currentMode

Preferences prefs;
String serverTarget;

MFRC522 mfrc522(SS_PIN, RST_PIN);

// Button and LED state variables
bool lastToggleState = false;
const unsigned long BUTTON_DEBOUNCE_MS = 200;

// Scan cooldown to prevent duplicate scans and allow mode changes
bool scanCooldownActive = false;
unsigned long lastScanTime = 0;
const unsigned long SCAN_COOLDOWN_MS = 3000;

void handleButton() {
  bool currentButtonState = digitalRead(BUTTON_PIN) == LOW;  // Active LOW with pull-up
  static unsigned long lastChange = 0;
  unsigned long now = millis();
  
  if (currentButtonState != lastToggleState && (now - lastChange > BUTTON_DEBOUNCE_MS)) {
    lastChange = now;
    if (DEBUG_SERIAL) {
      Serial.print("[BTN] Toggle changed to: ");
      Serial.println(currentButtonState ? "CLOSED (ON)" : "OPEN (OFF)");
    }
    if (currentButtonState) {
      currentMode = (DirectionMode)((currentMode + 1) % 3);
      updateDirectionString();
      updateLEDs();
    }
    lastToggleState = currentButtonState;
  }
}

void updateLEDs() {
  digitalWrite(LED_IN_PIN, currentMode == MODE_IN ? HIGH : LOW);
  digitalWrite(LED_OUT_PIN, currentMode == MODE_OUT ? HIGH : LOW);
  digitalWrite(LED_ADMIN_PIN, currentMode == MODE_ADMIN ? HIGH : LOW);
}

void updateDirectionString() {
  switch (currentMode) {
    case MODE_IN: DIRECTION = "IN"; break;
    case MODE_OUT: DIRECTION = "OUT"; break;
    case MODE_ADMIN: DIRECTION = "ADMIN"; break;
  }
  if (DEBUG_SERIAL) {
    Serial.print("[MODE] "); Serial.print(currentMode);
    Serial.print(" ("); Serial.print(DIRECTION); Serial.println(")");
  }
}

// Blink admin LED when in admin mode waiting for scan
unsigned long lastAdminBlink = 0;
bool adminLedState = false;

void updateAdminBlink() {
  if (currentMode == MODE_ADMIN) {
    unsigned long now = millis();
    if (now - lastAdminBlink > 500) {
      lastAdminBlink = now;
      adminLedState = !adminLedState;
      digitalWrite(LED_ADMIN_PIN, adminLedState ? HIGH : LOW);
    }
  }
}

bool networkReady() {
  return WiFi.status() == WL_CONNECTED;
}

void debugPrintWifiInfo() {
  if (!DEBUG_SERIAL) return;
  Serial.print("WiFi status: "); Serial.println(WiFi.status());
  Serial.print("SSID: "); Serial.println(WiFi.SSID());
  Serial.print("IP: "); Serial.println(WiFi.localIP());
  Serial.print("RSSI: "); Serial.println(WiFi.RSSI());
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
    case 409: return "409 Admin Active";
    case HTTPC_ERROR_CONNECTION_REFUSED: return "-1 Connection Refused";
    case HTTPC_ERROR_SEND_HEADER_FAILED: return "-2 Send Header Failed";
    case HTTPC_ERROR_SEND_PAYLOAD_FAILED: return "-3 Send Payload Failed";
    case HTTPC_ERROR_NOT_CONNECTED: return "-4 Not Connected";
    case HTTPC_ERROR_CONNECTION_LOST: return "-5 Connection Lost";
    case HTTPC_ERROR_NO_STREAM: return "-6 No Stream";
    case HTTPC_ERROR_READ_TIMEOUT: return "-7 Read Timeout";
    default: return "(unknown)";
  }
}

bool httpPostWithRetry(const String& url, const String& body, int& outCode, String& outResp, int maxRetries = 3) {
  outCode = -1;
  outResp = "";
  for (int attempt = 1; attempt <= maxRetries; attempt++) {
    if (!networkReady()) {
      Serial.println("WiFi not ready, waiting...");
      delay(1000);
      continue;
    }
    HTTPClient http;
    if (DEBUG_SERIAL) {
      Serial.print("POST attempt "); Serial.print(attempt);
      Serial.print(" -> "); Serial.println(url);
    }
    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    http.setTimeout(5000);
    int code = http.POST(body);
    outResp = http.getString();
    outCode = code;
    http.end();
    if (DEBUG_SERIAL) {
      Serial.print("HTTP "); Serial.print(code); Serial.print(" "); Serial.println(httpCodeToText(code));
      Serial.print("Response: "); Serial.println(outResp);
    }
    if (code == HTTP_CODE_OK) return true;
    if (code == HTTP_CODE_NOT_FOUND || code == HTTP_CODE_FORBIDDEN || code == HTTP_CODE_BAD_REQUEST) break;
    delay(500);
  }
  return false;
}

bool httpGetWithRetry(const String& url, int& outCode, String& outResp, int maxRetries = 3) {
  outCode = -1;
  outResp = "";
  for (int attempt = 1; attempt <= maxRetries; attempt++) {
    if (!networkReady()) {
      Serial.println("WiFi not ready, waiting...");
      delay(1000);
      continue;
    }
    HTTPClient http;
    if (DEBUG_SERIAL) {
      Serial.print("GET attempt "); Serial.print(attempt);
      Serial.print(" -> "); Serial.println(url);
    }
    http.begin(url);
    http.setTimeout(5000);
    int code = http.GET();
    outResp = http.getString();
    outCode = code;
    http.end();
    if (DEBUG_SERIAL) {
      Serial.print("HTTP "); Serial.print(code); Serial.print(" "); Serial.println(httpCodeToText(code));
      Serial.print("Response: "); Serial.println(outResp);
    }
    if (code == HTTP_CODE_OK) return true;
    if (code == HTTP_CODE_NOT_FOUND || code == HTTP_CODE_FORBIDDEN || code == HTTP_CODE_BAD_REQUEST) break;
    delay(500);
  }
  return false;
}

String loadServerTarget() {
  prefs.begin("config", true);
  String target = prefs.getString("server_url", "");
  prefs.end();
  if (target.length() == 0) return String(DEFAULT_SERVER_PATH);
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
  if (path.length() == 0) return String(DEFAULT_SERVER_PATH);
  if (!path.startsWith("/")) path = "/" + path;
  return path;
}

String buildServerUrl(const String& target) {
  if (target.startsWith("http://") || target.startsWith("https://")) return target;
  String path = normalizePath(target);
  return "http://" + String(SERVER_IP) + path;
}

String joinApiPath(String root, const String& subfolder, const String& filename) {
  root.trim();
  if (root.length() == 0) root = String(DEFAULT_SERVER_PATH);
  if (!root.endsWith("/")) root += "/";
  return root + subfolder + "/" + filename;
}

String buildEndpointUrl(const String& subfolder, const String& filename) {
  if (serverTarget.startsWith("http://") || serverTarget.startsWith("https://")) {
    String base = serverTarget;
    if (!base.endsWith("/")) base += "/";
    return base + subfolder + "/" + filename;
  }
  String path = joinApiPath(serverTarget, subfolder, filename);
  return buildServerUrl(path);
}

void handleSerialCommands() {
  if (!Serial.available()) return;
  String line = Serial.readStringUntil('\n');
  line.trim();
  if (line.length() == 0) return;

  if (line.startsWith("SET_URL ")) {
    String newTarget = line.substring(8);
    newTarget.trim();
    if (newTarget.length() == 0) { Serial.println("SET_URL missing value"); return; }
    if (saveServerTarget(newTarget)) {
      serverTarget = newTarget;
      Serial.print("API root updated: "); Serial.println(serverTarget);
      if (networkReady()) Serial.println(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"));
      else Serial.println("Network not ready");
    } else Serial.println("Failed to save Server target");
    return;
  }

  if (line.startsWith("SET_PATH ")) {
    String newPath = line.substring(9);
    newPath.trim();
    if (newPath.length() == 0) { Serial.println("SET_PATH missing value"); return; }
    newPath = normalizePath(newPath);
    if (saveServerTarget(newPath)) {
      serverTarget = newPath;
      Serial.print("API root updated: "); Serial.println(serverTarget);
      if (networkReady()) Serial.println(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"));
      else Serial.println("Network not ready");
    } else Serial.println("Failed to save Server path");
    return;
  }

  if (line == "GET_URL") {
    Serial.print("Example resolved endpoint: ");
    if (networkReady()) Serial.println(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"));
    else Serial.println("(network not ready)");
    return;
  }

  if (line == "GET_TARGET") {
    Serial.print("API root: "); Serial.println(serverTarget);
    return;
  }

  if (line == "RESET_URL") {
    if (clearServerUrl()) {
      serverTarget = String(DEFAULT_SERVER_PATH);
      Serial.print("API root reset: "); Serial.println(serverTarget);
    } else Serial.println("No stored Server URL to clear");
    return;
  }

  Serial.println("Unknown command. Use GET_URL, GET_TARGET, SET_URL <api root>, SET_PATH <api root>, or RESET_URL");
}

String uidToString(MFRC522::Uid* uid) {
  String out = "";
  for (byte i = 0; i < uid->size; i++) {
    if (uid->uidByte[i] < 0x10) out += "0";
    out += String(uid->uidByte[i], HEX);
    if (i < uid->size - 1) out += ":";
  }
  out.toUpperCase();
  return out;
}

String normalizeUid(const String& uid) {
  String out = "";
  for (size_t i = 0; i < uid.length(); i++) {
    char c = uid.charAt(i);
    if (isxdigit(c)) out += (char)toupper(c);
  }
  return out;
}

void showStatus(const String& line1, const String& line2) {
  if (!DEBUG_SERIAL) return;
  Serial.print("STATUS: "); Serial.print(line1);
  if (line2.length() > 0) { Serial.print(" | "); Serial.print(line2); }
  Serial.println();
}

bool checkAdminByApi(const String& uid) {
  if (!networkReady()) return false;
  String url = buildEndpointUrl(SUBFOLDER_ADMIN, "admin_login.php");
  String body = "uid=" + uid;
  int code = -1;
  String resp = "";
  bool ok = httpPostWithRetry(url, body, code, resp);
  return ok;
}

bool checkRegisteredUser(const String& uid) {
  if (!networkReady()) return false;
  String url = buildEndpointUrl(SUBFOLDER_USERS, "get_user.php") + "?uid=" + uid;
  int code = -1;
  String resp = "";
  bool ok = httpGetWithRetry(url, code, resp);
  return ok && resp.indexOf("\"ok\":true") >= 0;
}

bool postSignal(const String& url, const String& uid) {
  if (!networkReady()) return false;
  String body = "uid=" + uid;
  int code = -1;
  String resp = "";
  return httpPostWithRetry(url, body, code, resp);
}

bool logScan(const String& uid, const String& direction, const String& adminUid) {
  if (!networkReady()) return false;
  String url = buildEndpointUrl(SUBFOLDER_SCANS, "log_scan.php");
  String body = "uid=" + uid + "&direction=" + direction + "&admin_uid=" + adminUid;
  int code = -1;
  String resp = "";
  return httpPostWithRetry(url, body, code, resp);
}

bool handleScan(MFRC522& reader) {
  if (!reader.PICC_IsNewCardPresent()) return false;
   if (!reader.PICC_ReadCardSerial()) {
     if (DEBUG_SERIAL) Serial.println("Card read failed");
     return false;
   }

   if (DEBUG_SERIAL) {
     Serial.print("RFID Serial raw bytes: ");
     for (byte i = 0; i < reader.uid.size; i++) {
       Serial.print(reader.uid.uidByte[i], HEX);
       if (i < reader.uid.size - 1) Serial.print(" ");
     }
     Serial.println();
   }

   String uid = uidToString(&reader.uid);
  String uidNorm = normalizeUid(uid);
  String masterNorm = normalizeUid(String(MASTER_UID));

  Serial.print("UID ["); Serial.print(DIRECTION); Serial.print("]: "); Serial.println(uid);
  showStatus("Scanning...", "Please wait");

  if (!networkReady()) {
    Serial.println("WiFi disconnected");
    debugPrintWifiInfo();
    showStatus("WiFi Disconnect", "Check network");
  } else {
    bool isAdmin = false;
    if (uidNorm == masterNorm) {
      isAdmin = true;
    } else {
      isAdmin = checkAdminByApi(uid);
    }

    if (isAdmin) {
      postSignal(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"), uid);
      logScan(uid, DIRECTION, uid);
      showStatus("Admin Access", "Open dashboard");
    } else {
      bool registered = checkRegisteredUser(uid);
      if (registered) {
        logScan(uid, DIRECTION, "");
        showStatus("Registered Card", "Not admin");
      } else {
        postSignal(buildEndpointUrl(SUBFOLDER_SIGNALS, "report_register_scan.php"), uid);
        showStatus("New Card", "Go Register");
      }
    }
  }

  reader.PICC_HaltA();
  reader.PCD_StopCrypto1();

  scanCooldownActive = true;
  lastScanTime = millis();
  return true;
}

void setup() {
  Serial.begin(115200);
  delay(100);

  serverTarget = loadServerTarget();
  if (DEBUG_SERIAL) {
    Serial.print("Server target: "); Serial.println(serverTarget);
    Serial.println("Commands: GET_URL, GET_TARGET, SET_URL <url>, SET_PATH <path>, RESET_URL");
  }

  pinMode(SS_PIN, OUTPUT);
  digitalWrite(SS_PIN, HIGH);

  // Initialize button and LEDs
  pinMode(BUTTON_PIN, INPUT_PULLUP);
  pinMode(LED_IN_PIN, OUTPUT);
  pinMode(LED_OUT_PIN, OUTPUT);
  pinMode(LED_ADMIN_PIN, OUTPUT);
  if (DEBUG_SERIAL) {
    Serial.print("[BTN] Initial state: "); Serial.println(digitalRead(BUTTON_PIN) == LOW ? "PRESSED" : "RELEASED");
  }
  lastToggleState = digitalRead(BUTTON_PIN) == LOW;
  
  // Initialize LEDs to show current mode
  updateLEDs();
  updateDirectionString();

  WiFi.config(LOCAL_IP, GATEWAY, SUBNET);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  Serial.print("Connecting to hotspot");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println(" connected");
  debugPrintWifiInfo();
  if (DEBUG_SERIAL) {
    Serial.print("Resolved URL: ");
    Serial.println(buildServerUrl(serverTarget));
  }

  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("RC522 reader ready");
  if (DEBUG_SERIAL) {
    byte v = mfrc522.PCD_ReadRegister(MFRC522::VersionReg);
    Serial.print("RC522 version: 0x");
    Serial.println(v, HEX);
  }

  showStatus("Ready to Scan", "");
}

void loop() {
  handleSerialCommands();
  handleButton();
  updateAdminBlink();
  
  if (scanCooldownActive) {
    if (millis() - lastScanTime > SCAN_COOLDOWN_MS) {
      scanCooldownActive = false;
      updateLEDs();
      showStatus("Ready to Scan", "");
    }
    delay(10);
  } else {
    if (!handleScan(mfrc522)) {
      delay(50);
    }
  }
}
