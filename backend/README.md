# FitHub — Backend API & Admin Panel

FitHub is a two-sided gym management system: a Laravel admin panel for gym owners/staff and a REST API that powers the FitHub member app. This repository contains both — the Laravel backend serves the API (used by the mobile app) and the Livewire-based admin dashboard (used by gym staff in the browser).

## Tech Stack

- **Framework:** Laravel 11 (PHP 8.2+)
- **Auth:** Laravel Sanctum (token auth for the mobile app), session auth (`auth:web`) for the admin panel
- **Admin UI:** Livewire 4 + Alpine.js + Tailwind CSS
- **Database:** MySQL
- **QR codes:** Server-side generation (`simplesoftwareio/simple-qrcode`) + camera scanning in the admin panel (`html5-qrcode`)
- **Push notifications:** Firebase Cloud Messaging via `kreait/laravel-firebase`
- **Charts:** Chart.js (bundled via Vite) for the admin Progress page
- **Scheduler:** Laravel's task scheduler (`routes/console.php`) for reminder jobs

## Core Entities

Gym, Member, Plan, Membership, Attendance, Class, Booking, Measurement.

## Delivery Phases

1. **Core** — admin panel, member management, plans, QR-code attendance, member plan/QR view, renewal-expiry reminders (FCM) ✅
2. **Classes** — class scheduling, in-app booking, capacity & waitlists, booking confirmations + class reminders (FCM) ✅
3. **Progress** — body-measurement logging (member-private), progress charts, weekly progress reminders (FCM) ✅
4. **Insight** — admin dashboards, revenue & occupancy reports, payment tracking ✅

## Setup

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Set your database credentials in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fithub
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Database

```bash
php artisan migrate
```

### 4. Firebase (push notifications)

Push notifications (booking confirmations, class reminders, progress reminders, renewal reminders) require a Firebase service account:

1. Download the service account JSON from the Firebase console for the project.
2. Place it at `storage/app/firebase/service-account.json` (this path is git-ignored — never commit it).

No `.env` entry is needed; `config/firebase.php` resolves the credentials path by default.

### 5. Build frontend assets

```bash
npm run build
# or, during development:
npm run dev
```

### 6. Run the server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Binding to `0.0.0.0` lets a physical phone on the same Wi-Fi/LAN reach the API (not just `localhost`).

### 7. Scheduled reminders

Three scheduled commands send push notifications:

- `app:send-class-reminders` — every 5 minutes, notifies members with a booked class starting within the hour.
- `app:send-progress-reminders` — weekly, notifies members who haven't logged a measurement in 7 days.
- `app:send-renewal-reminders` — daily, notifies members whose membership expires within the next 3 days.

Booking confirmations (booked or waitlisted) and waitlist-promotion notices are sent immediately, inline in `Api\ClassController`, not on a schedule.

Windows has no cron, so the scheduler needs a long-running worker to actually fire:

```bash
php artisan schedule:work
```

(Or register `php artisan schedule:run` in Windows Task Scheduler to run every minute.)

## Testing

Tests run against a dedicated `fithub_testing` MySQL database (configured in `phpunit.xml`) so they never touch the `fithub` dev database — create it once with `mysql -u root -e "CREATE DATABASE IF NOT EXISTS fithub_testing"`, then:

```bash
php artisan test
```

Coverage: auth (login/logout/token revocation), class booking (capacity, waitlisting, cancellation promoting the next waitlisted member), measurement privacy (a member only ever sees their own), the attendance check-in/check-out toggle (including the active-membership check), the renewal-reminder command, and the `Membership`/`GymClass` booking domain logic directly. Firebase Cloud Messaging calls are faked in tests via `Tests\Concerns\FakesFirebase` (binds a Mockery spy to the `firebase.manager` container key), so no real service account or network access is needed.

## Admin Panel Routes

All under `auth:web` middleware, reachable in the browser after logging in at `/login`:

| Route | Purpose |
|---|---|
| `/dashboard/plans` | Manage membership plans |
| `/dashboard/members` | Enroll & manage members |
| `/dashboard/members/{member}/qr` | View a member's QR code |
| `/dashboard/attendance` | Scan member QR codes to check members in/out |
| `/dashboard/classes` | Manage classes/schedule |
| `/dashboard/bookings` | View class bookings |
| `/dashboard/insight` | Dashboards: check-ins, peak hours, membership/renewal stats, class fill rates, revenue by plan, outstanding balances |

## API Endpoints (used by the mobile app)

All routes except `/login` require a Sanctum bearer token, obtained from `/login`.

| Method | Route | Purpose |
|---|---|---|
| POST | `/api/login` | Member login, returns a Sanctum token |
| POST | `/api/logout` | Invalidate the current token |
| GET | `/api/member` | Current authenticated member |
| GET | `/api/member/qr` | Member's QR code image (SVG) |
| GET | `/api/member/membership` | Member's current/latest membership |
| POST | `/api/member/fcm-token` | Save the device's FCM push token |
| GET | `/api/member/measurements` | List the member's measurement history |
| POST | `/api/member/measurements` | Log a new measurement |
| GET | `/api/classes` | List upcoming classes with booking status |
| POST | `/api/classes/{class}/book` | Book (or waitlist) a class |
| POST | `/api/bookings/{booking}/cancel` | Cancel a booking |

## Attendance Scanning Behavior

Scanning a member's QR code at `/dashboard/attendance` toggles their attendance state rather than always creating a new check-in:

- If the member has an open attendance record (checked in today, not yet checked out), scanning again sets `checked_out_at` — i.e. it checks them out.
- Otherwise, scanning validates the member has an active membership (`end_date >= today`) before creating a new attendance row. Members with no active membership are rejected with an on-screen message instead of being checked in.

## Notes

- Data model and feature scope follow the fixed client project spec.
- The `storage/app/firebase/service-account.json` credential file must never be committed; it's already covered by `.gitignore`.
- Per the spec's security requirements, body-measurement data is private to each member — there is no admin panel view of member measurements; that data is only ever read/written through the member's own Sanctum-authenticated API session.
