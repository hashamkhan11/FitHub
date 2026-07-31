# FitHub Security Audit — 2026-07-30

Full-project review: Laravel backend, multi-tenant authorization, mobile app (Flutter), ESP32 firmware, and dependencies. Performance impact is noted per item — nearly everything here is a config change or a `where` clause, not a redesign, so none of it should measurably slow the app down.

Severity key: **Critical** (cross-tenant data leak / auth bypass) · **High** (real exploit path, needs a live attacker action) · **Medium** (needs a specific precondition) · **Low** (defense-in-depth) · **Info** (checked, no action needed).

---

## Critical

### 1. Cross-gym data leak in the Fingerprints dashboard (IDOR)
**File:** `app/Livewire/Fingerprints.php:47`
```php
'activeCommand' => $this->activeCommandId ? LockCommand::find($this->activeCommandId) : null,
```
`$activeCommandId` is a public Livewire property with no `#[Locked]` attribute. Livewire sends the full component state back to the server on every request, so a staff member logged into **Gym A** can tamper with that request and set `activeCommandId` to a `LockCommand` id belonging to **Gym B**. The page then renders Gym B's device name, member id, fingerprint id, and enrollment progress — a direct cross-tenant data breach, which is the single worst thing that can happen in a multi-tenant SaaS.

**Fix:** scope the lookup through the device relation, e.g.:
```php
'activeCommand' => $this->activeCommandId
    ? LockCommand::whereHas('device', fn ($q) => $q->where('gym_id', $gymId))->find($this->activeCommandId)
    : null,
```
**Performance:** none — same query, one extra indexed join.

### 2. Stripe webhook accepts unsigned requests if `STRIPE_WEBHOOK_SECRET` is blank
**File:** Cashier's `WebhookController` (vendor) only applies signature verification `if (config('cashier.webhook.secret'))`; `StripeWebhookController` inherits this unmodified. `.env.example:88` ships `STRIPE_WEBHOOK_SECRET=` blank.
If your manager deploys to cPanel and forgets to set this one variable, `POST /stripe/webhook` becomes a completely unauthenticated endpoint — anyone can POST a fake `customer.subscription.updated` event and activate/extend a gym's paid subscription for free, or forge failed-payment events to trigger suspension emails.

**Fix:** add a boot-time check (e.g. in `AppServiceProvider::boot()`) that throws/logs a critical error if `app()->environment('production')` and `config('cashier.webhook.secret')` is empty — turns a silent misconfiguration into a loud one.
**Performance:** none — one config check on boot.

---

## High

### 3. No security headers anywhere
No `X-Frame-Options`, `Content-Security-Policy`, `X-Content-Type-Options`, or `Strict-Transport-Security` set globally. The billing/payment pages are clickjackable (an attacker can iframe your login/billing page) and there's no HSTS forcing HTTPS on repeat visits.

**Fix:** one small global middleware in `bootstrap/app.php`:
```php
$middleware->append(\App\Http\Middleware\SecurityHeaders::class);
```
setting `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Strict-Transport-Security: max-age=31536000; includeSubDomains`, and a baseline CSP.
**Performance:** none — a few extra response headers.

### 4. ESP32 firmware disables TLS certificate validation
**File:** `esp32-lock/src/main.cpp:195` — `secureClient.setInsecure();`
Every request from the physical lock device (poll, ack, fingerprint scan) skips server certificate validation. Anyone who can get on the gym's Wi-Fi (rogue AP, ARP spoof) can MITM the device: steal its token, or — combined with finding 6 below — replay a captured "unlock" response to open the door at will.

**Fix:** once the production domain/cert is fixed, switch to `secureClient.setCACert(...)` with the real CA, or pin the leaf certificate.
**Performance:** negligible — proper TLS validation adds microseconds, not noticeable on a polling device.

### 5. Guzzle / Laravel framework: 6 unpatched advisories at current pinned versions
`composer audit` found 13 advisories across 3 packages. Two are trivially fixable now:
- `dompdf/dompdf` <3.1.6 → several medium-severity file-read/DoS bugs in PDF generation (used for receipts). Your `composer.json` constraint (`^3.0`) already allows the fix.
- `guzzlehttp/guzzle` <7.15.1 → header/cookie leakage bugs. Constraint (`^7.4`) already allows the fix.

`laravel/framework` (pinned `^11.31`, installed 11.54.0) has a high-severity CRLF-injection advisory in the default `email` validation rule with **no patched 11.x release available** — the fix only landed in the 12.x/13.x lines. In this codebase every place that validates `'email'` feeds straight into `Mail::to()` (Symfony Mailer, which encodes headers safely) rather than raw header construction, so real-world exploitability here looks low — but it's worth tracking.

**Fix now:** `composer update guzzlehttp/guzzle dompdf/dompdf --with-all-dependencies` (no code changes needed, both are drop-in). **Fix later:** plan a Laravel 12 upgrade after the deadline, not before — a major-version bump this close to launch is a bigger risk than the CRLF issue itself.
**Performance:** none — patch releases, not major versions.

---

## Medium

### 6. Firmware/backend: no replay protection on lock commands
**File:** `esp32-lock/src/main.cpp:267-305`, `app/Http/Controllers/Api/FingerprintController.php:20`
The device trusts whatever JSON the backend returns with no nonce/timestamp check, and the fingerprint-scan endpoint accepts a bare device token with no freshness check either. If a token or a captured "unlock" response is ever sniffed, it can be replayed indefinitely.
**Fix:** fixing #4 (real TLS validation) closes most of this. As a fast-follow, add a `expires_at`/nonce field the firmware checks before acting on a command.
**Performance:** negligible.

### 7. Member OTP (password reset / phone verification) has no per-account lockout
**File:** `app/Http/Controllers/Api/PasswordResetController.php:48-80`
The 6-digit OTP is only protected by a per-IP `throttle:6,1`. A distributed attacker (rotating IPs) can brute-force a specific member's OTP within its validity window without ever tripping a per-account lock.
**Fix:** add a failed-attempt counter per member/OTP (lock after 5 wrong tries), independent of the IP throttle.
**Performance:** one extra cache/DB counter check — negligible.

### 8. Mobile app: auth token stored in plaintext SharedPreferences
**File:** `mobile/lib/providers/auth_provider.dart:24,36-37,69-70`
The Sanctum bearer token is saved via plain `SharedPreferences`, which is unencrypted on Android (readable via root, ADB backup, or a device dump). A leaked token grants full API access as that member — including remote lock/unlock (see finding 9).
**Fix:** switch the token (only the token — UI prefs are fine as-is) to `flutter_secure_storage`, which is backed by Android Keystore / iOS Keychain.
**Performance:** none — secure storage reads/writes are just as fast for a single small string.

### 9. Mobile app: release builds default to plaintext HTTP if a build flag is missed
**File:** `mobile/lib/services/api_client.dart:33` — `String.fromEnvironment('API_SCHEME', defaultValue: 'http')`
If whoever builds the release APK forgets `--dart-define=API_SCHEME=https`, the app silently talks HTTP — login password and bearer token go over the wire in plaintext.
**Fix:** flip the default to `'https'` so a missed build flag fails safe, not fails open.
**Performance:** none.

### 10. Firmware: USB serial console can enroll/redirect the device with no backend involvement
**File:** `esp32-lock/src/main.cpp:560-589`
`enroll <id>` / `delete <id>` over serial add a fingerprint template directly, and `settoken`/`seturl` silently repoint the device at a different server — both need physical USB access, but neither requires any confirmation (the config-wipe flow already has one). This is a legitimate physical-access backdoor if a device is ever left accessible.
**Fix:** gate these serial commands behind the same physical confirmation step (e.g. hold BOOT button) already used for config wipe.
**Performance:** none.

### 11. SVG uploads accepted by the bare `image` validation rule
**File:** `app/Http/Controllers/Api/MemberController.php:134,144`, `app/Livewire/GymProfile.php:41,93`
Laravel's `image` rule permits `image/svg+xml`. A crafted SVG with an embedded `<script>` served from `storage/app/public` is a stored-XSS vector if a URL to it is ever opened directly in a browser tab.
**Fix:** use `mimes:jpg,jpeg,png,webp` instead of the bare `image` rule for member photos and gym logos.
**Performance:** none.

---

## Low / Info (no immediate action required, noted for completeness)

- **Fingerprint scan endpoint IP-keyed throttle** — the shared device-facing `throttle:120,1` is keyed by IP, not per-device-token, so devices behind the same gym NAT share one bucket. Low priority; switch to a token-keyed limiter if multiple devices per gym becomes common.
- **Mobile Sanctum tokens use default `['*']` abilities** — a leaked token has full account access rather than scoped abilities. Reasonable for MVP; consider scoping (`member:read`, `lock:operate`, etc.) later.
- **`Measurement` and `Booking` models lack the `BelongsToGym` trait** that every other tenant model has. Nothing exploits this today (all current code routes through a scoped parent first), but it's a trap for the next person who writes `Measurement::find($id)` directly. Add the trait now while it's cheap.
- **`Classes.php::edit()`** is missing the `Gate::authorize('manage-classes')` check that `save()`/`delete()` have (inconsistent, not exploitable — result is still gym-scoped).
- **No account lockout after repeated failed logins** on any of the 3 guards, beyond per-minute IP throttling. Standard MVP gap; consider progressive backoff later.
- **`.env.example` defaults to `APP_DEBUG=true`** — fine for local dev, but make sure whoever copies it to the production server uses `.env.production.example` instead (which already has this right), not the plain one.
- **No CORS policy file exists** (`config/cors.php` absent, using framework default). Not currently exploitable — no wildcard-origin + credentials combo — but worth publishing an explicit allowlist before any browser-based admin surface is added beyond the existing Blade/Livewire pages.
- **RoleAuthorizationTest.php has no cross-tenant IDOR tests** and no coverage at all for `Staff`, `Billing`, `GymProfile`, `LockDevices`, `Fingerprints`, `Classes`, `Bookings`, `Attendance`, `Activity`. Worth a follow-up test suite once the fixes above land, so the "Critical" finding above can never silently regress.
- Mass assignment, password hashing, CSRF scoping, Sanctum guard config, secrets hygiene (`.env` never committed, no real secrets in `.env.example`), `npm audit` (0 vulnerabilities), and file/document-root exposure (`public/.htaccess`, DEPLOYMENT.md's outside-`public_html` guidance) were all checked and found correct — no action needed.

---

## Recommended order of work (given the ~2-day deadline)

1. Fix #1 (Fingerprints IDOR) — one line, highest severity.
2. Fix #2 (Stripe webhook secret enforcement) — a few lines, prevents free subscription forgery.
3. `composer update guzzlehttp/guzzle dompdf/dompdf --with-all-dependencies` — zero-risk patch bump.
4. Add the security-headers middleware (#3).
5. Fix #8 and #9 (mobile token storage + HTTPS default) before the release APK is rebuilt for the manager.
6. #4/#6 (ESP32 TLS) and #10 (serial console gating) — do these once, don't need to touch again per device.
7. Everything else (#7, #11, low/info items) can follow post-launch without blocking the deadline.

None of the above requires a database migration or a breaking API change, so nothing here should slip the 2-day timeline on its own.
