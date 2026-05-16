#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Preferences.h>

// Single RC522 wiring (SPI)
#define SS_PIN 25
#define RST_PIN 27  // keep 27 to free up I2C for LCD

const char* WIFI_SSID = "DESKTOP-FTP1D16 9697";
const char* WIFI_PASS = "12345678";
const char* DEFAULT_SERVER_PATH = "/server/School_Entrance_Monitoring_System/api/admin/report_admin_scan.php";
const bool DEBUG_SERIAL = true;

// Master card UID (must match server settings)
const char* MASTER_UID = "97:2A:59:06";

Preferences prefs;
String serverTarget;

MFRC522 mfrc522(SS_PIN, RST_PIN);

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

String extractBaseTarget(const String& target) {
  String base = target;
  int queryIndex = base.indexOf('?');
  if (queryIndex >= 0) {
    base = base.substring(0, queryIndex);
  }
  int hashIndex = base.indexOf('#');
  if (hashIndex >= 0) {
    base = base.substring(0, hashIndex);
  }
  int lastSlash = base.lastIndexOf('/');
  if (lastSlash >= 0) {
    base = base.substring(0, lastSlash + 1);
  }
  if (base.length() == 0) {
    base = String(DEFAULT_SERVER_PATH);
    int fallbackSlash = base.lastIndexOf('/');
    base = base.substring(0, fallbackSlash + 1);
  }
  return base;
}

String buildEndpointUrl(const String& filename) {
  String base = extractBaseTarget(serverTarget);
  if (!base.endsWith("/")) {
    base += "/";
  }
  return buildServerUrl(base + filename);
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
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }
  HTTPClient http;
  String url = buildEndpointUrl("admin_login.php");
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
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }
  HTTPClient http;
  String url = buildEndpointUrl("get_user.php") + "?uid=" + uid;
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

  if (WiFi.status() != WL_CONNECTED) {
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
      showStatus("Admin Access", "Open dashboard");
    } else {
      bool registered = checkRegisteredUser(uid);
      if (registered) {
        showStatus("Registered Card", "Not admin");
      } else {
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
    Serial.print("Server target: ");
    Serial.println(serverTarget);
    Serial.println("Commands: GET_URL, GET_TARGET, SET_URL <url>, SET_PATH <path>, RESET_URL");
  }

  pinMode(SS_PIN, OUTPUT);
  digitalWrite(SS_PIN, HIGH);

  WiFi.begin(WIFI_SSID, WIFI_PASS);

  Serial.print("Connecting to WiFi");
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
  if (!handleScan(mfrc522)) {
    delay(50);
  }
}
