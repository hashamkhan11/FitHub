#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <ArduinoJson.h>
#include <Adafruit_Fingerprint.h>

// --- Relay wiring: DO NOT flip this. VCC->3V3, IN->D2. Confirmed working. ---
// LOW = unlock, HIGH = locked (this is also the boot-default state).
#define RELAY_PIN 2
#define BOOT_BUTTON_PIN 0   // hold this at boot for 3s to wipe saved config
#define AP_SSID "FitHub-Lock-Setup"

// --- Fingerprint sensor wiring: VIN->5V/3V3, GND->GND, data on 16 and 17. ---
#define FINGERPRINT_RX_PIN 16   // ESP32 RX <- sensor TX
#define FINGERPRINT_TX_PIN 17   // ESP32 TX -> sensor RX
#define FINGERPRINT_BAUD 57600
const unsigned long UNLOCK_PULSE_MS = 4000; // how long a fingerprint-granted entry stays unlocked

const unsigned long POLL_INTERVAL_MS = 1000;   // how often we ask the server for commands
const unsigned long WIFI_CONNECT_TIMEOUT_MS = 20000;

Preferences prefs;
WebServer setupServer(80);

HardwareSerial fingerSerial(2);
Adafruit_Fingerprint finger(&fingerSerial);
bool fingerprintReady = false;
bool waitingForFingerRemoval = false;

// ---------- dashboard-driven enrollment state machine ----------
// Enrollment needs two separate finger placements, but loop() must keep
// polling/acking other commands meanwhile - so this advances one step per
// loop() iteration instead of blocking, unlike the old Serial enrollFingerprint().
enum EnrollState { ENROLL_NONE, ENROLL_WAIT_FIRST, ENROLL_WAIT_REMOVE, ENROLL_WAIT_SECOND };
EnrollState enrollState = ENROLL_NONE;
uint8_t enrollId = 0;
int enrollCommandId = 0;
unsigned long enrollStepStartMs = 0;
const unsigned long ENROLL_STEP_TIMEOUT_MS = 20000;

String savedSsid, savedPass, savedServerUrl, savedToken;
bool configured = false;
unsigned long lastPollMs = 0;
unsigned long unlockUntilMs = 0; // 0 = no fingerprint-triggered unlock pending

// ---------- config storage (ESP32 flash, survives reboot) ----------

void loadConfig() {
    prefs.begin("lock", true);
    configured = prefs.getBool("configured", false);
    savedSsid = prefs.getString("ssid", "");
    savedPass = prefs.getString("pass", "");
    savedServerUrl = prefs.getString("server", "");
    savedToken = prefs.getString("token", "");
    prefs.end();
}

void saveConfig(const String &ssid, const String &pass, const String &server, const String &token) {
    prefs.begin("lock", false);
    prefs.putBool("configured", true);
    prefs.putString("ssid", ssid);
    prefs.putString("pass", pass);
    prefs.putString("server", server);
    prefs.putString("token", token);
    prefs.end();
}

void clearConfig() {
    prefs.begin("lock", false);
    prefs.clear();
    prefs.end();
}

// Updates just the server URL / token (e.g. after moving networks) without
// going through the full captive-portal setup flow - handy for debugging
// over Serial when the device already has working WiFi credentials.
void updateServerConfig(const String &server, const String &token) {
    prefs.begin("lock", false);
    prefs.putBool("configured", true);
    prefs.putString("ssid", savedSsid);
    prefs.putString("pass", savedPass);
    prefs.putString("server", server);
    prefs.putString("token", token);
    prefs.end();
    savedServerUrl = server;
    savedToken = token;
}

// ---------- setup mode: device becomes its own WiFi hotspot with a form ----------

String setupPage() {
    String html;
    html += "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1'>";
    html += "<title>FitHub Lock Setup</title>";
    html += "<style>body{font-family:Arial,sans-serif;background:#111;color:#eee;padding:24px;max-width:420px;margin:0 auto}";
    html += "h2{color:#d4af37}label{display:block;margin-top:14px;font-size:14px;color:#ccc}";
    html += "input{width:100%;padding:10px;margin-top:6px;border-radius:6px;border:1px solid #444;background:#1c1c1c;color:#eee;box-sizing:border-box}";
    html += "button{margin-top:22px;width:100%;padding:12px;border:none;border-radius:6px;background:#d4af37;color:#111;font-weight:bold;font-size:15px}</style>";
    html += "</head><body>";
    html += "<h2>FitHub Lock Setup</h2>";
    html += "<p>Enter your gym's WiFi and the device token from the FitHub dashboard (Lock page).</p>";
    html += "<form action='/save' method='POST'>";
    html += "<label>WiFi Name</label><input name='ssid' required>";
    html += "<label>WiFi Password</label><input name='pass' type='password'>";
    html += "<label>Server URL (e.g. https://yourgym.fithub.app)</label><input name='server' required>";
    html += "<label>Device Token</label><input name='token' required>";
    html += "<button type='submit'>Save & Restart</button>";
    html += "</form></body></html>";
    return html;
}

void handleSetupRoot() {
    setupServer.send(200, "text/html", setupPage());
}

void handleSetupSave() {
    String ssid = setupServer.arg("ssid");
    String pass = setupServer.arg("pass");
    String server = setupServer.arg("server");
    String token = setupServer.arg("token");

    if (ssid.isEmpty() || server.isEmpty() || token.isEmpty()) {
        setupServer.send(400, "text/plain", "WiFi name, server URL, and device token are required.");
        return;
    }

    // trim any trailing slash so we can safely append paths later
    while (server.endsWith("/")) {
        server.remove(server.length() - 1);
    }

    saveConfig(ssid, pass, server, token);
    setupServer.send(200, "text/html", "<html><body style='font-family:Arial;text-align:center;padding-top:60px;background:#111;color:#eee'><h2>Saved. Restarting...</h2></body></html>");
    delay(1500);
    ESP.restart();
}

void startSetupMode() {
    Serial.println("Entering setup mode...");
    WiFi.mode(WIFI_AP);
    WiFi.softAP(AP_SSID);
    Serial.print("Connect your phone to WiFi '");
    Serial.print(AP_SSID);
    Serial.print("' then visit http://");
    Serial.println(WiFi.softAPIP());

    setupServer.on("/", handleSetupRoot);
    setupServer.on("/save", HTTP_POST, handleSetupSave);
    setupServer.begin();

    while (true) {
        setupServer.handleClient();
    }
}

// ---------- normal mode: WiFi + polling ----------

bool connectWifi() {
    WiFi.mode(WIFI_STA);
    WiFi.begin(savedSsid.c_str(), savedPass.c_str());
    Serial.print("Connecting to WiFi");
    unsigned long start = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - start < WIFI_CONNECT_TIMEOUT_MS) {
        delay(500);
        Serial.print(".");
    }
    Serial.println();
    return WiFi.status() == WL_CONNECTED;
}

void openLock() {
    digitalWrite(RELAY_PIN, LOW);
    Serial.println("Lock OPEN");
}

void closeLock() {
    digitalWrite(RELAY_PIN, HIGH);
    Serial.println("Lock CLOSED");
}

// Opens the lock and schedules an automatic close after UNLOCK_PULSE_MS.
// Unlike the mobile-app-triggered open/close (which holds until an explicit
// close command), a fingerprint entry point must not stay unlocked forever.
void pulseUnlock() {
    openLock();
    unlockUntilMs = millis() + UNLOCK_PULSE_MS;
}

bool beginHttp(HTTPClient &http, WiFiClientSecure &secureClient, WiFiClient &plainClient, const String &url) {
    if (savedServerUrl.startsWith("https://")) {
        secureClient.setInsecure(); // no cert pinning yet; fine for now, revisit once the production domain is fixed
        return http.begin(secureClient, url);
    }
    return http.begin(plainClient, url);
}

void ackCommand(int commandId, const String &status) {
    HTTPClient http;
    WiFiClientSecure secureClient;
    WiFiClient plainClient;
    String url = savedServerUrl + "/api/lock/commands/" + String(commandId) + "/ack";

    if (!beginHttp(http, secureClient, plainClient, url)) {
        Serial.println("ack: failed to open connection");
        return;
    }

    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Device-Token", savedToken);
    http.addHeader("Accept", "application/json");

    JsonDocument doc;
    doc["status"] = status;
    String body;
    serializeJson(doc, body);

    int code = http.POST(body);
    Serial.printf("ack #%d (%s) -> HTTP %d\n", commandId, status.c_str(), code);
    http.end();
}

// Lets the dashboard show live status ("Place finger again", "Remove finger")
// while a multi-step command like enrollment is still running.
void reportProgress(int commandId, const String &message) {
    HTTPClient http;
    WiFiClientSecure secureClient;
    WiFiClient plainClient;
    String url = savedServerUrl + "/api/lock/commands/" + String(commandId) + "/progress";

    if (!beginHttp(http, secureClient, plainClient, url)) {
        Serial.println("progress: failed to open connection");
        return;
    }

    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Device-Token", savedToken);
    http.addHeader("Accept", "application/json");

    JsonDocument doc;
    doc["message"] = message;
    String body;
    serializeJson(doc, body);

    int code = http.POST(body);
    Serial.printf("progress #%d -> HTTP %d: %s\n", commandId, code, message.c_str());
    http.end();
}

void pollServer() {
    HTTPClient http;
    WiFiClientSecure secureClient;
    WiFiClient plainClient;
    String url = savedServerUrl + "/api/lock/poll";

    if (!beginHttp(http, secureClient, plainClient, url)) {
        Serial.println("poll: failed to open connection");
        return;
    }

    http.addHeader("X-Device-Token", savedToken);
    http.addHeader("Accept", "application/json");

    int code = http.GET();
    if (code == 200) {
        String payload = http.getString();
        JsonDocument doc;
        DeserializationError err = deserializeJson(doc, payload);
        if (!err) {
            for (JsonObject cmd : doc["commands"].as<JsonArray>()) {
                int id = cmd["id"];
                String action = cmd["action"].as<String>();
                Serial.printf("command #%d: %s\n", id, action.c_str());
                if (action == "open") {
                    openLock();
                    ackCommand(id, "completed");
                } else if (action == "close") {
                    closeLock();
                    ackCommand(id, "completed");
                } else if (action == "delete_fingerprint") {
                    int fid = cmd["payload"]["fingerprint_id"] | 0;
                    if (fid > 0 && fingerprintReady && finger.deleteModel((uint8_t)fid) == FINGERPRINT_OK) {
                        ackCommand(id, "completed");
                    } else {
                        ackCommand(id, "failed");
                    }
                } else if (action == "enroll") {
                    int fid = cmd["payload"]["fingerprint_id"] | 0;
                    if (enrollState != ENROLL_NONE || fid <= 0 || fid > 200 || !fingerprintReady) {
                        ackCommand(id, "failed");
                    } else {
                        enrollId = (uint8_t)fid;
                        enrollCommandId = id;
                        enrollState = ENROLL_WAIT_FIRST;
                        enrollStepStartMs = millis();
                        ackCommand(id, "in_progress");
                        reportProgress(id, "Place finger on the scanner...");
                    }
                } else {
                    ackCommand(id, "failed");
                }
            }
        } else {
            Serial.println("poll: could not parse response JSON");
        }
    } else if (code == 401) {
        Serial.println("poll: device token rejected (401) - check token via setup mode");
    } else if (code > 0) {
        Serial.printf("poll: HTTP %d\n", code);
    } else {
        Serial.printf("poll: connection error %d\n", code);
    }
    http.end();
}

// ---------- fingerprint sensor: entry via matched finger ----------

void setupFingerprint() {
    fingerSerial.begin(FINGERPRINT_BAUD, SERIAL_8N1, FINGERPRINT_RX_PIN, FINGERPRINT_TX_PIN);
    finger.begin(FINGERPRINT_BAUD);

    if (finger.verifyPassword()) {
        fingerprintReady = true;
        Serial.println("Fingerprint sensor found.");
    } else {
        Serial.println("Fingerprint sensor not found - check wiring (VIN/GND/16/12).");
    }
}

// Reports a matched fingerprint ID to the server. Matching itself already
// happened on-sensor, so only this small integer ever leaves the device.
void reportFingerprintScan(int id) {
    HTTPClient http;
    WiFiClientSecure secureClient;
    WiFiClient plainClient;
    String url = savedServerUrl + "/api/fingerprint/scan";

    if (!beginHttp(http, secureClient, plainClient, url)) {
        Serial.println("fingerprint scan: failed to open connection");
        return;
    }

    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Device-Token", savedToken);
    http.addHeader("Accept", "application/json");

    JsonDocument doc;
    doc["fingerprint_id"] = id;
    String body;
    serializeJson(doc, body);

    int code = http.POST(body);
    if (code == 200) {
        JsonDocument respDoc;
        if (!deserializeJson(respDoc, http.getString())) {
            bool unlock = respDoc["unlock"] | false;
            const char *message = respDoc["message"] | "";
            Serial.println(message);
            if (unlock) {
                pulseUnlock();
            }
        }
    } else {
        Serial.printf("fingerprint scan report -> HTTP %d\n", code);
    }
    http.end();
}

// Called every loop() iteration. getImage() returns immediately (no finger
// placed yet) so this is cheap to poll continuously alongside pollServer().
void pollFingerprint() {
    if (!fingerprintReady) {
        return;
    }

    // See the matching comment in updateEnroll() - getImage() needs a beat
    // between polls to let the sensor's own capture cycle catch up.
    delay(50);

    if (waitingForFingerRemoval) {
        if (finger.getImage() == FINGERPRINT_NOFINGER) {
            waitingForFingerRemoval = false;
        }
        return;
    }

    if (finger.getImage() != FINGERPRINT_OK) {
        return;
    }

    if (finger.image2Tz() != FINGERPRINT_OK) {
        waitingForFingerRemoval = true;
        return;
    }

    if (finger.fingerFastSearch() != FINGERPRINT_OK) {
        Serial.println("Fingerprint not recognized.");
        waitingForFingerRemoval = true;
        return;
    }

    Serial.printf("Fingerprint match: ID #%d (confidence %d)\n", finger.fingerID, finger.confidence);
    reportFingerprintScan(finger.fingerID);
    waitingForFingerRemoval = true;
}

// Advances the dashboard-triggered enrollment by one step per loop()
// iteration (mirrors the blocking Serial enrollFingerprint() sequence below,
// but never blocks so pollServer()/pollFingerprint() keep running). Any scan
// failure aborts the command rather than retrying silently, same as the
// blocking version's behaviour on error.
void updateEnroll() {
    if (enrollState == ENROLL_NONE) {
        return;
    }

    if (millis() - enrollStepStartMs > ENROLL_STEP_TIMEOUT_MS) {
        Serial.println("Enrollment timed out.");
        reportProgress(enrollCommandId, "Timed out waiting for a finger.");
        ackCommand(enrollCommandId, "failed");
        enrollState = ENROLL_NONE;
        return;
    }

    // Without this, loop() hammers getImage() as fast as the UART allows,
    // far faster than the sensor's own optical capture cycle, so it just
    // keeps echoing a stale "no finger" status back. 50ms matches the
    // polling rate used by both Adafruit's own examples and the reference
    // firmware confirmed working on this exact sensor.
    delay(50);

    switch (enrollState) {
        case ENROLL_WAIT_FIRST: {
            int p = finger.getImage();
            if (p != FINGERPRINT_OK) {
                return; // no finger yet (or a transient read error) - keep waiting
            }

            if (finger.image2Tz(1) != FINGERPRINT_OK) {
                reportProgress(enrollCommandId, "Failed to read that scan. Try again.");
                ackCommand(enrollCommandId, "failed");
                enrollState = ENROLL_NONE;
                return;
            }

            reportProgress(enrollCommandId, "Remove finger.");
            enrollState = ENROLL_WAIT_REMOVE;
            enrollStepStartMs = millis();
            break;
        }
        case ENROLL_WAIT_REMOVE: {
            if (finger.getImage() != FINGERPRINT_NOFINGER) {
                return;
            }

            reportProgress(enrollCommandId, "Place the same finger again.");
            enrollState = ENROLL_WAIT_SECOND;
            enrollStepStartMs = millis();
            break;
        }
        case ENROLL_WAIT_SECOND: {
            int p = finger.getImage();
            if (p != FINGERPRINT_OK) {
                return;
            }

            if (finger.image2Tz(2) != FINGERPRINT_OK) {
                reportProgress(enrollCommandId, "Failed to read that scan. Try again.");
                ackCommand(enrollCommandId, "failed");
                enrollState = ENROLL_NONE;
                return;
            }

            if (finger.createModel() != FINGERPRINT_OK) {
                reportProgress(enrollCommandId, "Those two scans didn't match.");
                ackCommand(enrollCommandId, "failed");
                enrollState = ENROLL_NONE;
                return;
            }

            if (finger.storeModel(enrollId) != FINGERPRINT_OK) {
                reportProgress(enrollCommandId, "Failed to store fingerprint on sensor.");
                ackCommand(enrollCommandId, "failed");
                enrollState = ENROLL_NONE;
                return;
            }

            Serial.printf("Enrolled ID #%d via dashboard.\n", enrollId);
            ackCommand(enrollCommandId, "completed");
            enrollState = ENROLL_NONE;
            break;
        }
        default:
            break;
    }
}

// Blocking two-scan enrollment (standard Adafruit sequence), triggered by
// typing "enroll <id>" in the Serial Monitor. Blocking is fine here - this
// is a manual maintenance step done over USB, not part of normal operation.
void enrollFingerprint(uint8_t id) {
    Serial.printf("Place finger to enroll as ID #%d...\n", id);
    int p = -1;
    while (p != FINGERPRINT_OK) {
        p = finger.getImage();
        delay(50);
    }

    if (finger.image2Tz(1) != FINGERPRINT_OK) {
        Serial.println("Failed to process first scan. Try again.");
        return;
    }

    Serial.println("Remove finger.");
    delay(1500);
    p = 0;
    while (p != FINGERPRINT_NOFINGER) {
        p = finger.getImage();
        delay(50);
    }

    Serial.println("Place the same finger again...");
    p = -1;
    while (p != FINGERPRINT_OK) {
        p = finger.getImage();
        delay(50);
    }

    if (finger.image2Tz(2) != FINGERPRINT_OK) {
        Serial.println("Failed to process second scan. Try again.");
        return;
    }

    if (finger.createModel() != FINGERPRINT_OK) {
        Serial.println("The two scans didn't match. Try again.");
        return;
    }

    if (finger.storeModel(id) != FINGERPRINT_OK) {
        Serial.println("Failed to store fingerprint on sensor.");
        return;
    }

    Serial.printf("Enrolled ID #%d. Now assign this ID to the member in the FitHub dashboard (Members > edit > Fingerprint ID).\n", id);
}

void deleteFingerprint(uint8_t id) {
    if (finger.deleteModel(id) == FINGERPRINT_OK) {
        Serial.printf("Deleted fingerprint ID #%d.\n", id);
    } else {
        Serial.printf("Failed to delete fingerprint ID #%d.\n", id);
    }
}

// Typed Serial Monitor commands for enrollment: "enroll <id>", "delete <id>".
// Kept local to the device on purpose - no network enrollment protocol.
void handleSerialCommands() {
    if (!Serial.available()) {
        return;
    }

    String line = Serial.readStringUntil('\n');
    line.trim();
    if (line.length() == 0) {
        return;
    }

    if (line.startsWith("seturl ")) {
        String url = line.substring(7);
        url.trim();
        while (url.endsWith("/")) {
            url.remove(url.length() - 1);
        }
        updateServerConfig(url, savedToken);
        Serial.println("Server URL updated. Restarting...");
        delay(300);
        ESP.restart();
        return;
    } else if (line.startsWith("settoken ")) {
        String token = line.substring(9);
        token.trim();
        updateServerConfig(savedServerUrl, token);
        Serial.println("Device token updated. Restarting...");
        delay(300);
        ESP.restart();
        return;
    }

    if (line == "status") {
        Serial.printf("fingerprintReady=%d enrollState=%d wifiConnected=%d ip=%s\n",
            fingerprintReady, (int)enrollState, WiFi.status() == WL_CONNECTED,
            WiFi.localIP().toString().c_str());
        return;
    }

    if (!fingerprintReady) {
        Serial.println("Fingerprint sensor not ready.");
        return;
    }

    if (enrollState != ENROLL_NONE) {
        Serial.println("Busy: a dashboard fingerprint enrollment is in progress.");
        return;
    }

    if (line.startsWith("enroll ")) {
        int id = line.substring(7).toInt();
        if (id < 1 || id > 200) {
            Serial.println("ID must be between 1 and 200.");
            return;
        }
        enrollFingerprint((uint8_t)id);
    } else if (line.startsWith("delete ")) {
        int id = line.substring(7).toInt();
        deleteFingerprint((uint8_t)id);
    } else if (line == "help") {
        Serial.println("Commands: enroll <id>, delete <id>");
    }
}

// hold the BOOT button (GPIO0) for 3s at power-on to forget WiFi/server/token
void checkForResetHold() {
    pinMode(BOOT_BUTTON_PIN, INPUT_PULLUP);
    if (digitalRead(BOOT_BUTTON_PIN) != LOW) {
        return;
    }

    Serial.println("Boot button held - keep holding for 3s to reset config...");
    unsigned long start = millis();
    while (digitalRead(BOOT_BUTTON_PIN) == LOW) {
        if (millis() - start > 3000) {
            Serial.println("Resetting saved config...");
            clearConfig();
            delay(300);
            ESP.restart();
        }
        delay(50);
    }
}

void setup() {
    Serial.begin(9600);
    delay(200);

    pinMode(RELAY_PIN, OUTPUT);
    digitalWrite(RELAY_PIN, HIGH); // start locked

    setupFingerprint();

    checkForResetHold();
    loadConfig();

    if (!configured) {
        startSetupMode(); // never returns
    }

    if (!connectWifi()) {
        Serial.println("Could not connect with saved WiFi - entering setup mode.");
        startSetupMode(); // never returns
    }

    Serial.print("Connected. IP: ");
    Serial.println(WiFi.localIP());
    Serial.println("Ready - polling server for lock commands.");
}

void loop() {
    handleSerialCommands();

    if (enrollState != ENROLL_NONE) {
        updateEnroll(); // enrollment owns the sensor - don't also run door-entry matching
    } else {
        pollFingerprint();
    }

    if (unlockUntilMs != 0 && millis() >= unlockUntilMs) {
        closeLock();
        unlockUntilMs = 0;
    }

    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi dropped, reconnecting...");
        if (!connectWifi()) {
            delay(5000);
            return;
        }
    }

    if (millis() - lastPollMs >= POLL_INTERVAL_MS) {
        lastPollMs = millis();
        pollServer();
    }
}
