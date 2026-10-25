#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Preferences.h>
#include "esp_wifi.h"
#include <HardwareSerial.h>

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
#define BUZZER_PIN   14   // Piezo buzzer for access granted
#define BUZZER_FREQUENCY 2000
// SIM900A UART2: module TX -> ESP32 RX, module RX -> ESP32 TX.
#define SIM900A_RX_PIN 34
#define SIM900A_TX_PIN 13
#define SIM900A_BAUD 9600
// =========================================================

const char* WIFI_SSID = "DESKTOP-FTP1D16 9697";
const char* WIFI_PASS = "12345678";

const char* DEFAULT_SERVER_PATH = "/server/School_Entrance_Monitoring_System/api";
const bool DEBUG_SERIAL = true;
const unsigned long SMS_POLL_INTERVAL_MS = 30000;

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

// Admin login persistence
bool adminLoggedIn = false;
const char* PREFS_ADMIN_KEY = "admin_logged_in";

// Buzzer feedback for access granted (non-blocking)
unsigned long buzzerEndTime = 0;
const unsigned long BUZZER_DURATION_MS = 1500;

MFRC522 mfrc522In(SS_PIN_IN, RST_PIN_IN);
MFRC522 mfrc522Out(SS_PIN_OUT, RST_PIN_OUT);
HardwareSerial sim900a(2);

bool readModemResponse(const char* command, String& response, unsigned long timeoutMs);

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

  if (line == "RUN_DIAGNOSTICS") {
    runDiagnostics();
    return;
  }

  if (line == "RUN_OUTPUT_TEST") {
    runOutputTest();
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

  Serial.println("Unknown command. Use RUN_DIAGNOSTICS, RUN_OUTPUT_TEST, GET_URL, GET_TARGET, SET_URL <url>, SET_PATH <path>, or RESET_URL");
}

void runOutputTest() {
  Serial.println("[DIAG] Output test starting. WARNING: relays will remain locked.");
  lockAll();
  Serial.println("[RELAY] M1=HIGH (locked), M2=HIGH (locked). No unlock pulse was sent.");
  tone(BUZZER_PIN, BUZZER_FREQUENCY, 250);
  delay(300);
  noTone(BUZZER_PIN);
  Serial.println("[BUZZER] Tone sent for 250 ms (check audibly).");
  Serial.println("[DIAG] Output test complete.");
}

void runDiagnostics() {
  Serial.println();
  Serial.println("=== HARDWARE DIAGNOSTICS (DUAL SCANNER) ===");
  Serial.println("[INFO] A GPIO output cannot detect a disconnected wire without loopback.");
  Serial.println("[PIN] SPI SCK=18, MOSI=23, MISO=19");
  Serial.println("[PIN] IN SS=5, RST=17; OUT SS=25, RST=27");
  Serial.println("[PIN] RELAY_M1=21, RELAY_M2=26, BUZZER=14, SIM900A_RX=34, SIM900A_TX=13");

  pinMode(SS_PIN_IN, OUTPUT);
  pinMode(SS_PIN_OUT, OUTPUT);
  digitalWrite(SS_PIN_IN, HIGH);
  digitalWrite(SS_PIN_OUT, HIGH);
  pinMode(RST_PIN_IN, OUTPUT);
  pinMode(RST_PIN_OUT, OUTPUT);
  digitalWrite(RST_PIN_IN, LOW);
  digitalWrite(RST_PIN_OUT, LOW);
  delay(5);
  digitalWrite(RST_PIN_IN, HIGH);
  digitalWrite(RST_PIN_OUT, HIGH);
  delay(50);
  SPI.begin();
  mfrc522In.PCD_Init();
  mfrc522Out.PCD_Init();
  byte inVersion = mfrc522In.PCD_ReadRegister(MFRC522::VersionReg);
  byte outVersion = mfrc522Out.PCD_ReadRegister(MFRC522::VersionReg);
  Serial.print("[RC522 IN] Version register: 0x");
  Serial.println(inVersion, HEX);
  Serial.println((inVersion == 0x00 || inVersion == 0xFF)
    ? "[RC522 IN] FAIL - check power, GND, SPI, SS=5, RST=17"
    : "[RC522 IN] PASS - SPI communication detected");
  Serial.print("[RC522 OUT] Version register: 0x");
  Serial.println(outVersion, HEX);
  Serial.println((outVersion == 0x00 || outVersion == 0xFF)
    ? "[RC522 OUT] FAIL - check power, GND, SPI, SS=25, RST=27"
    : "[RC522 OUT] PASS - SPI communication detected");

  Serial.print("[WIFI] ");
  Serial.println(networkReady() ? "PASS - connected" : "FAIL - not connected");
  if (networkReady()) {
    debugPrintWifiInfo();
    Serial.print("[API] Endpoint: ");
    Serial.println(buildEndpointUrl(SUBFOLDER_SCANS, "log_scan.php"));
  }

  String modemResponse;
  bool modemOk = readModemResponse("AT", modemResponse, 1500);
  Serial.print("[SIM900A] ");
  Serial.println(modemOk
    ? "PASS - modem replied OK"
    : "FAIL - no OK response; check power, GND, RX/TX crossing");
  Serial.println("[RELAY] PASS (software lock state only). Use RUN_OUTPUT_TEST and verify both relay modules stay locked.");
  Serial.println("=== DIAGNOSTICS COMPLETE ===");
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

bool httpPost(const String& url, const String& body, int& outCode, String* responseOut = nullptr) {
  if (!networkReady()) return false;
  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.setTimeout(1500);
  outCode = http.POST(body);
  String resp = http.getString();
  if (responseOut != nullptr) {
    *responseOut = resp;
  }
  if (DEBUG_SERIAL) {
    Serial.print("POST "); Serial.println(url);
    Serial.print("HTTP "); Serial.print(outCode); Serial.print(" "); Serial.println(httpCodeToTextLocal(outCode));
    Serial.print("Response: "); Serial.println(resp);
  }
  http.end();
  return outCode == HTTP_CODE_OK;
}

bool httpGet(const String& url, int& outCode, String* responseOut = nullptr, uint16_t timeoutMs = 1500) {
  if (!networkReady()) return false;
  HTTPClient http;
  http.begin(url);
  http.setTimeout(timeoutMs);
  outCode = http.GET();
  String resp = http.getString();
  if (responseOut != nullptr) {
    *responseOut = resp;
  }
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

String jsonStringValue(const String& json, const String& key) {
  String marker = "\"" + key + "\":\"";
  int start = json.indexOf(marker);
  if (start < 0) return "";
  start += marker.length();
  int end = json.indexOf('"', start);
  if (end < 0) return "";
  String value = json.substring(start, end);
  value.replace("\\\"", "\"");
  value.replace("\\\\", "\\");
  return value;
}

bool waitForModem(const char* expected, unsigned long timeoutMs) {
  unsigned long started = millis();
  String buffer;
  while (millis() - started < timeoutMs) {
    while (sim900a.available()) {
      buffer += (char)sim900a.read();
      if (buffer.indexOf(expected) >= 0) return true;
    }
    delay(2);
  }
  return false;
}

bool readModemResponse(const char* command, String& response, unsigned long timeoutMs = 1200) {
  while (sim900a.available()) sim900a.read();
  sim900a.println(command);
  response = "";
  unsigned long started = millis();
  while (millis() - started < timeoutMs) {
    while (sim900a.available()) {
      response += (char)sim900a.read();
    }
    if (response.indexOf("OK") >= 0 || response.indexOf("ERROR") >= 0) {
      return response.indexOf("OK") >= 0;
    }
    delay(2);
  }
  return false;
}

void printSim900aStatus() {
  String response;
  if (!readModemResponse("AT", response)) {
    Serial.println("[SIM900A] STATUS: NOT CONNECTED / NO MODEM RESPONSE");
    Serial.println("[SIM900A] Check separate power, shared GND, crossed TX/RX, and 3VT/3VR wiring");
    return;
  }

  Serial.println("[SIM900A] STATUS: MODEM CONNECTED");

  String registration;
  bool registrationCommandOk = readModemResponse("AT+CREG?", registration);
  Serial.print("[SIM900A] CREG RAW: ");
  Serial.println(registration);
  int registrationMarker = registration.indexOf("+CREG:");
  int comma = registrationMarker >= 0 ? registration.indexOf(',', registrationMarker) : -1;
  if (registrationCommandOk && registrationMarker >= 0 && comma >= 0) {
    int status = registration.substring(comma + 1).toInt();
    if (status == 1) {
      Serial.println("[SIM900A] NETWORK: REGISTERED (HOME)");
    } else if (status == 5) {
      Serial.println("[SIM900A] NETWORK: REGISTERED (ROAMING)");
    } else if (status == 2) {
      Serial.println("[SIM900A] NETWORK: SEARCHING");
    } else if (status == 3) {
      Serial.println("[SIM900A] NETWORK: REGISTRATION DENIED");
    } else {
      Serial.println("[SIM900A] NETWORK: NOT REGISTERED");
    }
  } else {
    Serial.println("[SIM900A] NETWORK: REGISTRATION CHECK FAILED");
  }

  String signal;
  if (readModemResponse("AT+CSQ", signal)) {
    int marker = signal.indexOf("+CSQ:");
    int comma = marker >= 0 ? signal.indexOf(',', marker) : -1;
    int rssi = marker >= 0 ? signal.substring(marker + 5, comma).toInt() : 99;
    if (rssi == 99) {
      Serial.println("[SIM900A] SIGNAL: UNKNOWN");
    } else {
      int percent = constrain((rssi * 100) / 31, 0, 100);
      Serial.print("[SIM900A] SIGNAL: RSSI ");
      Serial.print(rssi);
      Serial.print("/31 (");
      Serial.print(percent);
      Serial.println("%)");
    }
  } else {
    Serial.println("[SIM900A] SIGNAL: UNAVAILABLE");
  }
}

bool sendSmsViaSim900a(const String& phone, const String& message) {
  if (phone.length() == 0 || message.length() == 0) return false;

  while (sim900a.available()) sim900a.read();
  sim900a.println("AT");
  if (!waitForModem("OK", 700)) {
    Serial.println("[SIM900A] No modem response; check power, shared GND, crossed TX/RX, and 3VT/3VR wiring");
    return false;
  }

  sim900a.println("AT+CMGF=1");
  if (!waitForModem("OK", 700)) return false;

  sim900a.print("AT+CMGS=\"");
  sim900a.print(phone);
  sim900a.println("\"");
  if (!waitForModem(">", 2500)) {
    Serial.println("[SIM900A] No SMS prompt");
    return false;
  }

  sim900a.print(message);
  sim900a.write(26);
  bool sent = waitForModem("OK", 8000);
  Serial.println(sent ? "[SIM900A] SMS sent" : "[SIM900A] SMS failed");
  return sent;
}

void reportSmsResult(int id, bool sent) {
  if (!networkReady() || id < 1) return;
  int code = -1;
  String body = "id=" + String(id) + "&ok=" + (sent ? "1" : "0");
  if (!sent) body += "&error=SIM900A+send+failed";
  httpPost(buildEndpointUrl(SUBFOLDER_ADMIN, "report_sms_result.php"), body, code);
}

void reportDeviceHeartbeat(const String& device) {
  if (!networkReady()) return;
  int code = -1;
  httpPost(buildEndpointUrl(SUBFOLDER_ADMIN, "device_heartbeat.php"),
           "device=" + device, code);
}

void heartbeatServiceTask(void* parameter) {
  (void)parameter;
  vTaskDelay(pdMS_TO_TICKS(5000));
  for (;;) {
    reportDeviceHeartbeat("main_system");
    reportDeviceHeartbeat("sms_module");
    vTaskDelay(pdMS_TO_TICKS(30000));
  }
}

void pollPendingSms() {
  static unsigned long lastPoll = 0;
  if (millis() - lastPoll < SMS_POLL_INTERVAL_MS || !networkReady()) return;
  lastPoll = millis();

  int code = -1;
  String response;
  String url = buildEndpointUrl(SUBFOLDER_ADMIN, "get_pending_sms.php");
  // TextBee fallback runs inside this endpoint and can take longer than RFID
  // requests. Keep this timeout local to the low-priority SMS task.
  if (!httpGet(url, code, &response, 6000)) {
    if (DEBUG_SERIAL) {
      Serial.print("[SMS] Queue poll failed: HTTP ");
      Serial.println(code);
    }
    return;
  }

  int idStart = response.indexOf("\"id\":");
  int phoneStart = response.indexOf("\"phone\":\"");
  int messageStart = response.indexOf("\"message\":\"");
  if (idStart < 0 || phoneStart < 0 || messageStart < 0) return;
  int id = response.substring(idStart + 5).toInt();
  String phone = jsonStringValue(response, "phone");
  String message = jsonStringValue(response, "message");
  if (phone.length() > 0 && message.length() > 0) {
    reportSmsResult(id, sendSmsViaSim900a(phone, message));
  }
}

void smsServiceTask(void* parameter) {
  (void)parameter;
  vTaskDelay(pdMS_TO_TICKS(5000));
  unsigned long lastModemStatus = 0;
  for (;;) {
    if (millis() - lastModemStatus >= 30000) {
      printSim900aStatus();
      lastModemStatus = millis();
    }
    pollPendingSms();
    vTaskDelay(pdMS_TO_TICKS(1000));
  }
}

bool logScan(const String& uid, const String& direction, const String& adminUid) {
  int code = -1;
  String response;
  String url = buildEndpointUrl(SUBFOLDER_SCANS, "log_scan.php");
  String body = "uid=" + uid + "&direction=" + direction;
  if (adminUid.length() > 0) {
    body += "&admin_uid=" + adminUid;
  }
  bool posted = httpPost(url, body, code, &response);
  return posted;
}

void triggerBuzzer() {
  tone(BUZZER_PIN, BUZZER_FREQUENCY);
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
  bool cardRead = false;
  for (uint8_t attempt = 0; attempt < 2; attempt++) {
    if (reader.PICC_ReadCardSerial()) {
      cardRead = true;
      break;
    }
    delay(8);
  }
  if (!cardRead) {
    if (DEBUG_SERIAL) Serial.println("Card read failed");
    reader.PCD_StopCrypto1();
    return false;
  }

  String uid = uidToString(&reader.uid);
  Serial.print("UID ["); Serial.print(direction); Serial.print("]: "); Serial.println(uid);
  showStatus("Scanning...", "Please wait");

  lastScanTimeRef = now;

  String adminUid = "";
  bool cardIsRegistered = false;
  if (!networkReady()) {
    Serial.println("Network not ready (ESP32 not connected to desktop WiFi?)");
    debugPrintWifiInfo();
    showStatus("No Connection", "Check network");
  } else {
    bool isAdmin = checkAdminByApi(uid);

    if (isAdmin) {
      adminUid = uid;
      adminLoggedIn = true;
      prefs.begin("config", false);
      prefs.putBool(PREFS_ADMIN_KEY, true);
      prefs.end();
      postSignal(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"), uid);
      showStatus("Admin Access", "Open dashboard");
      triggerBuzzer();
      cardIsRegistered = true;
    } else {
      bool registered = checkRegisteredUser(uid);
      if (registered) {
        showStatus("Registered Card", "Access logged");
        triggerBuzzer();
        cardIsRegistered = true;
      } else {
        postSignal(buildEndpointUrl(SUBFOLDER_SIGNALS, "report_register_scan.php"), uid);
        showStatus("New Card", "Go Register");
      }
    }
  }

  logScan(uid, direction, adminUid);

  reader.PICC_HaltA();
  reader.PCD_StopCrypto1();

  if (cardIsRegistered) {
    if (direction == "OUT") {
      unlockOut();
      outUnlocked = true;
    } else if (direction == "IN") {
      unlockIn();
      inUnlocked = true;
    }
  } else {
    showStatus("No Unlock", "Card not registered");
  }
  showStatus("Ready to Scan", "");
  return true;
}

void setup() {
  Serial.begin(115200);
  delay(100);
  sim900a.begin(SIM900A_BAUD, SERIAL_8N1, SIM900A_RX_PIN, SIM900A_TX_PIN);
  Serial.println("[SIM900A] UART2 initialized");
  xTaskCreatePinnedToCore(
    smsServiceTask,
    "smsService",
    8192,
    nullptr,
    1,
    nullptr,
    0
  );
  xTaskCreatePinnedToCore(
    heartbeatServiceTask,
    "heartbeatService",
    4096,
    nullptr,
    1,
    nullptr,
    0
  );

  serverTarget = loadServerTarget();
  if (DEBUG_SERIAL) {
    Serial.print("API root: "); Serial.println(serverTarget);
    Serial.println("Commands: RUN_DIAGNOSTICS, RUN_OUTPUT_TEST, GET_URL, GET_TARGET, SET_URL <url>, SET_PATH <path>, RESET_URL");
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
  noTone(BUZZER_PIN);

  prefs.begin("config", false);
  adminLoggedIn = prefs.getBool(PREFS_ADMIN_KEY, false);
  prefs.end();
  if (DEBUG_SERIAL) {
    Serial.print("[ADMIN] Logged in: "); Serial.println(adminLoggedIn ? "yes" : "no");
  }

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
    noTone(BUZZER_PIN);
    buzzerEndTime = 0;
  }

  handleScan(mfrc522In, "IN", inLastScanTime);
  handleScan(mfrc522Out, "OUT", outLastScanTime);

  delay(50);
}
