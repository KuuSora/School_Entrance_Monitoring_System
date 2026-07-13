#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Preferences.h>
#include "esp_wifi.h"

// ===================== MODE SWITCH =====================
// Leave this defined: ESP32 becomes the hotspot, laptop connects to it.
// Comment it out to go back to the old behavior (ESP32 joins your
// laptop's hotspot as a client).
#define AP_MODE
// =========================================================

// Single RC522 wiring (SPI)
#define SS_PIN 25
#define RST_PIN 27  // keep 27 to free up I2C for LCD

#ifdef AP_MODE
  // ESP32 becomes the WiFi hotspot. Your laptop connects to THIS network.
  const char* AP_SSID = "SEMS-ESP32";
  const char* AP_PASS = "sems12345";   // 8+ chars required, or "" for open network
  IPAddress AP_LOCAL_IP(192, 168, 4, 1);
  IPAddress AP_GATEWAY(192, 168, 4, 1);
  IPAddress AP_SUBNET(255, 255, 255, 0);

  // Your laptop MUST be given a STATIC IP on this subnet (instructions
  // below). This is the address your XAMPP server will be reached at.
  const char* SERVER_IP = "192.168.4.2";
#else
  const char* WIFI_SSID = "DESKTOP-FTP1D16 9697";
  const char* WIFI_PASS = "12345678";
#endif

// This is the ROOT of the api/ folder only - NOT a specific .php file.
// Each endpoint below appends its own subfolder (admin/signals/users) on top
// of this, since your project splits endpoints across multiple folders.
const char* DEFAULT_SERVER_PATH = "/School_Entrance_Monitoring_Systems/api";
const bool DEBUG_SERIAL = true;

// Subfolders under api/, matching your actual htdocs layout.
const char* SUBFOLDER_ADMIN = "admin";
const char* SUBFOLDER_SIGNALS = "signals";
const char* SUBFOLDER_USERS = "users";

// Master card UID (must match server settings)
const char* MASTER_UID = "97:2A:59:06";

Preferences prefs;
String serverTarget;

MFRC522 mfrc522(SS_PIN, RST_PIN);

// True when the ESP32's network side is usable enough to attempt an HTTP call.
bool networkReady() {
#ifdef AP_MODE
  // "Ready" once at least one device (your laptop) has joined the hotspot.
  // Note: this doesn't guarantee it's specifically the laptop — if you ever
  // let other devices join this network, this check just becomes advisory.
  return WiFi.softAPgetStationNum() > 0;
#else
  return WiFi.status() == WL_CONNECTED;
#endif
}

void debugPrintWifiInfo() {
  if (!DEBUG_SERIAL) {
    return;
  }
#ifdef AP_MODE
  Serial.print("AP SSID: ");
  Serial.println(AP_SSID);
  Serial.print("AP IP: ");
  Serial.println(WiFi.softAPIP());
  Serial.print("Connected stations: ");
  Serial.println(WiFi.softAPgetStationNum());
#else
  Serial.print("WiFi status: ");
  Serial.println(WiFi.status());
  Serial.print("SSID: ");
  Serial.println(WiFi.SSID());
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  Serial.print("RSSI: ");
  Serial.println(WiFi.RSSI());
#endif
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
#ifdef AP_MODE
  // In AP mode there's no upstream gateway to borrow an IP from - the
  // laptop's address has to be known ahead of time (static IP).
  return "http://" + String(SERVER_IP) + path;
#else
  IPAddress gw = WiFi.gatewayIP();
  return "http://" + gw.toString() + path;
#endif
}

// Joins "<api root>/<subfolder>/<filename>", handling slashes cleanly.
String joinApiPath(String root, const String& subfolder, const String& filename) {
  root.trim();
  if (root.length() == 0) {
    root = String(DEFAULT_SERVER_PATH);
  }
  if (!root.endsWith("/")) {
    root += "/";
  }
  return root + subfolder + "/" + filename;
}

// Builds the full URL for a specific endpoint, given which subfolder it
// lives in (admin / signals / users) and its filename. This replaces the
// old assumption that every endpoint shared one folder.
String buildEndpointUrl(const String& subfolder, const String& filename) {
  if (serverTarget.startsWith("http://") || serverTarget.startsWith("https://")) {
    // Explicit full URL override (e.g. via SET_URL) - append subfolder/filename directly.
    String base = serverTarget;
    if (!base.endsWith("/")) {
      base += "/";
    }
    return base + subfolder + "/" + filename;
  }
  String path = joinApiPath(serverTarget, subfolder, filename);
  return buildServerUrl(path);
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

  // NOTE: SET_URL / SET_PATH now set the API ROOT (the folder that
  // contains admin/, signals/, users/) - not a single endpoint file.
  // Example: SET_PATH /School_Entrance_Monitoring_Systems/api

  if (line.startsWith("SET_URL ")) {
    String newTarget = line.substring(8);
    newTarget.trim();
    if (newTarget.length() == 0) {
      Serial.println("SET_URL missing value");
      return;
    }
    if (saveServerTarget(newTarget)) {
      serverTarget = newTarget;
      Serial.print("API root updated: ");
      Serial.println(serverTarget);
      if (networkReady()) {
        Serial.print("Example resolved endpoint: ");
        Serial.println(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"));
      } else {
        Serial.println("Network not ready; URL will resolve once connected");
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
      Serial.print("API root updated: ");
      Serial.println(serverTarget);
      if (networkReady()) {
        Serial.print("Example resolved endpoint: ");
        Serial.println(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"));
      } else {
        Serial.println("Network not ready; URL will resolve once connected");
      }
    } else {
      Serial.println("Failed to save Server path");
    }
    return;
  }

  if (line == "GET_URL") {
    Serial.print("Example resolved endpoint: ");
    if (networkReady()) {
      Serial.println(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"));
    } else {
      Serial.println("(network not ready)");
    }
    return;
  }

  if (line == "GET_TARGET") {
    Serial.print("API root: ");
    Serial.println(serverTarget);
    return;
  }

  if (line == "RESET_URL") {
    if (clearServerUrl()) {
      serverTarget = String(DEFAULT_SERVER_PATH);
      Serial.print("API root reset: ");
      Serial.println(serverTarget);
      if (networkReady()) {
        Serial.print("Example resolved endpoint: ");
        Serial.println(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"));
      }
    } else {
      Serial.println("No stored Server URL to clear");
    }
    return;
  }

  Serial.println("Unknown command. Use GET_URL, GET_TARGET, SET_URL <api root>, SET_PATH <api root>, or RESET_URL");
}

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

String normalizeUid(const String& uid) {
  String out = "";
  for (size_t i = 0; i < uid.length(); i++) {
    char c = uid.charAt(i);
    if (isxdigit(c)) {
      out += (char)toupper(c);
    }
  }
  return out;
}

void showStatus(const String& line1, const String& line2) {
  if (!DEBUG_SERIAL) {
    return;
  }
  Serial.print("STATUS: ");
  Serial.print(line1);
  if (line2.length() > 0) {
    Serial.print(" | ");
    Serial.print(line2);
  }
  Serial.println();
}

bool checkAdminByApi(const String& uid) {
  if (!networkReady()) {
    return false;
  }
  HTTPClient http;
  String url = buildEndpointUrl(SUBFOLDER_ADMIN, "admin_login.php");
  http.begin(url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  String body = "uid=" + uid;
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
  return code == HTTP_CODE_OK && resp.indexOf("\"ok\":true") >= 0;
}

bool checkRegisteredUser(const String& uid) {
  if (!networkReady()) {
    return false;
  }
  HTTPClient http;
  String url = buildEndpointUrl(SUBFOLDER_USERS, "get_user.php") + "?uid=" + uid;
  http.begin(url);
  int code = http.GET();
  String resp = http.getString();
  if (DEBUG_SERIAL) {
    Serial.print("GET ");
    Serial.println(url);
    Serial.print("HTTP ");
    Serial.print(code);
    Serial.print(" ");
    Serial.println(httpCodeToText(code));
    Serial.print("Response: ");
    Serial.println(resp);
  }
  http.end();
  return code == HTTP_CODE_OK && resp.indexOf("\"ok\":true") >= 0;
}

bool postSignal(const String& url, const String& uid) {
  if (!networkReady()) {
    return false;
  }
  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  String body = "uid=" + uid;
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
  return code == HTTP_CODE_OK;
}

bool handleScan(MFRC522& reader) {
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
  String uidNorm = normalizeUid(uid);
  String masterNorm = normalizeUid(String(MASTER_UID));

  Serial.print("UID: ");
  Serial.println(uid);
  showStatus("Scanning...", "Please wait");

  if (!networkReady()) {
    Serial.println("Network not ready (laptop not connected to hotspot?)");
    debugPrintWifiInfo();
    showStatus("No Connection", "Check network");
  } else {
    bool isAdmin = false;
    if (uidNorm == masterNorm) {
      isAdmin = true;
    } else {
      isAdmin = checkAdminByApi(uid);
    }

    if (isAdmin) {
      postSignal(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"), uid);
      showStatus("Admin Access", "Open dashboard");
    } else {
      bool registered = checkRegisteredUser(uid);
      if (registered) {
        showStatus("Registered Card", "Not admin");
      } else {
        postSignal(buildEndpointUrl(SUBFOLDER_SIGNALS, "report_register_scan.php"), uid);
        showStatus("New Card", "Go Register");
      }
    }
  }

  reader.PICC_HaltA();
  reader.PCD_StopCrypto1();

  delay(3000);
  showStatus("Ready to Scan", "");
  return true;
}

void setup() {
  Serial.begin(115200);
  delay(100);

  serverTarget = loadServerTarget();
  if (DEBUG_SERIAL) {
    Serial.print("API root: ");
    Serial.println(serverTarget);
    Serial.println("Commands: GET_URL, GET_TARGET, SET_URL <api root>, SET_PATH <api root>, RESET_URL");
  }

  pinMode(SS_PIN, OUTPUT);
  digitalWrite(SS_PIN, HIGH);

#ifdef AP_MODE
  WiFi.mode(WIFI_AP);
  WiFi.softAPConfig(AP_LOCAL_IP, AP_GATEWAY, AP_SUBNET);
  WiFi.softAP(AP_SSID, AP_PASS);
  esp_wifi_set_ps(WIFI_PS_NONE);  // stops power-save related reconnects/CCMP replay errors
  Serial.print("Hotspot started: ");
  Serial.println(AP_SSID);
  Serial.print("Connect your laptop to it, then give it the static IP: ");
  Serial.println(SERVER_IP);
#else
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println(" connected");
#endif

  debugPrintWifiInfo();
  if (DEBUG_SERIAL) {
    Serial.print("Example resolved endpoint: ");
    Serial.println(buildEndpointUrl(SUBFOLDER_ADMIN, "report_admin_scan.php"));
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
  if (!handleScan(mfrc522)) {
    delay(50);
  }
}
