#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Preferences.h>
#include "esp_wifi.h"

// ===================== WIRING =====================
// SPI bus shared: SCK=18, MOSI=23, MISO=19 (hardware SPI2)
// Reader IN (Entry)
#define SS_PIN_IN     5
#define RST_PIN_IN   17
// Reader OUT (Exit)
#define SS_PIN_OUT   25
#define RST_PIN_OUT  27
// HW-316 Relay Module (active LOW)
#define RELAY_M1     21   // IN solenoid (left)
#define RELAY_M2     26   // OUT solenoid (right)
#define BUZZER_PIN   14   // Active HIGH buzzer for access granted
// =========================================================

const char* WIFI_SSID = "DESKTOP-FTP1D16 9697";
const char* WIFI_PASS = "12345678";

const char* DEFAULT_SERVER_PATH = "/server/School_Entrance_Monitoring_System/api";
const bool DEBUG_SERIAL = true;

const char* SUBFOLDER_ADMIN = "admin";
const char* SUBFOLDER_SIGNALS = "signals";
const char* SUBFOLDER_USERS = "users";
const char* SUBFOLDER_SCANS = "scans";

Preferences prefs;
String serverTarget;

// Door unlock state with timeout
bool inUnlocked = false;
bool outUnlocked = false;
unsigned long inUnlockTime = 0;
unsigned long outUnlockTime = 0;
const unsigned long UNLOCK_TIMEOUT = 3000;

// Scan cooldown per reader to prevent duplicate reads
unsigned long inLastScanTime = 0;
unsigned long outLastScanTime = 0;
const unsigned long SCAN_COOLDOWN_MS = 1500;

// Buzzer feedback for access granted (non-blocking)
unsigned long buzzerEndTime = 0;
const unsigned long BUZZER_DURATION_MS = 1000;

MFRC522 mfrc522In(SS_PIN_IN, RST_PIN_IN);
MFRC522 mfrc522Out(SS_PIN_OUT, RST_PIN_OUT);

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
    default: return "(unknown)";
  }
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
  IPAddress gw = WiFi.gatewayIP();
  return "http://" + gw.toString() + path;
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

  Serial.println("Unknown command. Use GET_URL, GET_TARGET, SET_URL <url>, SET_PATH <path>, or RESET_URL");
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

const char* httpCodeToTextLocal(int code) {
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

bool httpPost(const String& url, const String& body, int& outCode) {
  if (!networkReady()) return false;
  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.setTimeout(3000);
  outCode = http.POST(body);
  String resp = http.getString();
  if (DEBUG_SERIAL) {
    Serial.print("POST "); Serial.println(url);
    Serial.print("HTTP "); Serial.print(outCode); Serial.print(" "); Serial.println(httpCodeToTextLocal(outCode));
    Serial.print("Response: "); Serial.println(resp);
  }
  http.end();
  return outCode == HTTP_CODE_OK;
}

bool httpGet(const String& url, int& outCode) {
  if (!networkReady()) return false;
  HTTPClient http;
  http.begin(url);
  http.setTimeout(3000);
  outCode = http.GET();
  String resp = http.getString();
  if (DEBUG_SERIAL) {
    Serial.print("GET "); Serial.println(url);
    Serial.print("HTTP "); Serial.print(outCode); Serial.print(" "); Serial.println(httpCodeToTextLocal(outCode));
    Serial.print("Response: "); Serial.println(resp);
  }
  http.end();
  return outCode == HTTP_CODE_OK;
}

bool checkAdminByApi(const String& uid) {
  int code = -1;
  String url = buildEndpointUrl(SUBFOLDER_ADMIN, "admin_login.php");
  String body = "uid=" + uid;
  return httpPost(url, body, code) && code == HTTP_CODE_OK;
}

bool checkRegisteredUser(const String& uid) {
  int code = -1;
  String url = buildEndpointUrl(SUBFOLDER_USERS, "get_user.php") + "?uid=" + uid;
  return httpGet(url, code) && code == HTTP_CODE_OK;
}

bool postSignal(const String& url, const String& uid) {
  int code = -1;
  String body = "uid=" + uid;
  return httpPost(url, body, code);
}

bool logScan(const String& uid, const String& direction, const String& adminUid) {
  int code = -1;
  String url = buildEndpointUrl(SUBFOLDER_SCANS, "log_scan.php");
  String body = "uid=" + uid + "&direction=" + direction;
  if (adminUid.length() > 0) {
    body += "&admin_uid=" + adminUid;
  }
  return httpPost(url, body, code);
}

void triggerBuzzer() {
  digitalWrite(BUZZER_PIN, HIGH);
  buzzerEndTime = millis() + BUZZER_DURATION_MS;
}

void lockAll() {
  digitalWrite(RELAY_M1, HIGH);
  digitalWrite(RELAY_M2, HIGH);
  inUnlocked = false;
  outUnlocked = false;
  inUnlockTime = 0;
  outUnlockTime = 0;
  showStatus("LOCKED", "All solenoids off");
}

void lockIn() {
  digitalWrite(RELAY_M1, HIGH);
  inUnlocked = false;
  inUnlockTime = 0;
  showStatus("LOCKED IN", "Timeout");
}

void lockOut() {
  digitalWrite(RELAY_M2, HIGH);
  outUnlocked = false;
  outUnlockTime = 0;
  showStatus("LOCKED OUT", "Timeout");
}

void unlockOut() {
  digitalWrite(RELAY_M2, LOW);
  outUnlocked = true;
  outUnlockTime = millis();
  showStatus("UNLOCK OUT", "Relay M2 active");
  triggerBuzzer();
}

void unlockIn() {
  digitalWrite(RELAY_M1, LOW);
  inUnlocked = true;
  inUnlockTime = millis();
  showStatus("UNLOCK IN", "Relay M1 active");
  triggerBuzzer();
}

bool handleScan(MFRC522& reader, const String& direction, unsigned long& lastScanTimeRef) {
  unsigned long now = millis();
  if (now - lastScanTimeRef < SCAN_COOLDOWN_MS) {
    return false;
  }

  if (!reader.PICC_IsNewCardPresent()) return false;
  if (!reader.PICC_ReadCardSerial()) {
    if (DEBUG_SERIAL) Serial.println("Card read failed");
    return false;
  }

  String uid = uidToString(&reader.uid);
  Serial.print("UID ["); Serial.print(direction); Serial.print("]: "); Serial.println(uid);
  showStatus("Scanning...", "Please wait");

  lastScanTimeRef = now;

  String adminUid = "";
  if (!networkReady()) {
    Serial.println("Network not ready (ESP32 not connected to desktop WiFi?)");
    debugPrintWifiInfo();
    showStatus("No Connection", "Check network");
  } else {
    bool isAdmin = checkAdminByApi(uid);

     if (isAdmin) {
       adminUid = uid;
       postSignal(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"), uid);
       showStatus("Admin Access", "Open dashboard");
       triggerBuzzer();
     } else {
       bool registered = checkRegisteredUser(uid);
       if (registered) {
         showStatus("Registered Card", "Access logged");
         triggerBuzzer();
       } else {
         postSignal(buildEndpointUrl(SUBFOLDER_SIGNALS, "report_register_scan.php"), uid);
         showStatus("New Card", "Go Register");
       }
     }
  }

  logScan(uid, direction, adminUid);

  reader.PICC_HaltA();
  reader.PCD_StopCrypto1();

  if (direction == "OUT") {
    unlockOut();
    outUnlocked = true;
  } else if (direction == "IN") {
    unlockIn();
    inUnlocked = true;
  }
  showStatus("Ready to Scan", "");
  return true;
}

void setup() {
  Serial.begin(115200);
  delay(100);

  serverTarget = loadServerTarget();
  if (DEBUG_SERIAL) {
    Serial.print("API root: "); Serial.println(serverTarget);
    Serial.println("Commands: GET_URL, GET_TARGET, SET_URL <url>, SET_PATH <path>, RESET_URL");
  }

  pinMode(SS_PIN_IN, OUTPUT);
  digitalWrite(SS_PIN_IN, HIGH);
  pinMode(SS_PIN_OUT, OUTPUT);
  digitalWrite(SS_PIN_OUT, HIGH);

  pinMode(RELAY_M1, OUTPUT);
  pinMode(RELAY_M2, OUTPUT);
  digitalWrite(RELAY_M1, HIGH);
  digitalWrite(RELAY_M2, HIGH);

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println(" connected");

  debugPrintWifiInfo();
  if (DEBUG_SERIAL) {
    Serial.print("Example resolved endpoint: ");
    Serial.println(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"));
  }

  SPI.begin();
  mfrc522In.PCD_Init();
  mfrc522Out.PCD_Init();
  Serial.println("RC522 IN reader ready");
  Serial.println("RC522 OUT reader ready");
  if (DEBUG_SERIAL) {
    byte v1 = mfrc522In.PCD_ReadRegister(MFRC522::VersionReg);
    byte v2 = mfrc522Out.PCD_ReadRegister(MFRC522::VersionReg);
    Serial.print("RC522 IN version: 0x"); Serial.println(v1, HEX);
    Serial.print("RC522 OUT version: 0x"); Serial.println(v2, HEX);
  }

  lockAll();
  showStatus("Ready to Scan", "");
}

void loop() {
  handleSerialCommands();

  if (inUnlocked && (millis() - inUnlockTime >= UNLOCK_TIMEOUT)) {
    lockIn();
  }
  if (outUnlocked && (millis() - outUnlockTime >= UNLOCK_TIMEOUT)) {
    lockOut();
  }
  if (buzzerEndTime > 0 && millis() >= buzzerEndTime) {
    digitalWrite(BUZZER_PIN, LOW);
    buzzerEndTime = 0;
  }

  handleScan(mfrc522In, "IN", inLastScanTime);
  handleScan(mfrc522Out, "OUT", outLastScanTime);

  delay(50);
}
