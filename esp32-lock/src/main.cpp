#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <ArduinoJson.h>

// --- Relay wiring: DO NOT flip this. VCC->3V3, IN->D2. Confirmed working. ---
// LOW = unlock, HIGH = locked (this is also the boot-default state).
#define RELAY_PIN 2
#define BOOT_BUTTON_PIN 0   // hold this at boot for 3s to wipe saved config
#define AP_SSID "FitHub-Lock-Setup"

const unsigned long POLL_INTERVAL_MS = 3000;   // how often we ask the server for commands
const unsigned long WIFI_CONNECT_TIMEOUT_MS = 20000;

Preferences prefs;
WebServer setupServer(80);

String savedSsid, savedPass, savedServerUrl, savedToken;
bool configured = false;
unsigned long lastPollMs = 0;

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
    Serial.println("Lock OPEN (stays open - no auto-relock yet)");
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
