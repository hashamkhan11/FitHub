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

1. **Core** — admin panel, member management, plans, QR-code attendance, member plan/QR view ✅
2. **Classes** — class scheduling, in-app booking, capacity & waitlists, class reminders (FCM) ✅
3. **Progress** — body-measurement logging, progress charts, weekly progress reminders (FCM) ✅
4. **Insight** — admin dashboards, revenue & occupancy reports, payment tracking ⏳ (not started)

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

Push notifications (class reminders, progress reminders) require a Firebase service account:

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

Two scheduled commands send push notifications:

- `app:send-class-reminders` — every 5 minutes, notifies members with a booked class starting within the hour.
- `app:send-progress-reminders` — weekly, notifies members who haven't logged a measurement in 7 days.

Windows has no cron, so the scheduler needs a long-running worker to actually fire:

```bash
php artisan schedule:work
```

(Or register `php artisan schedule:run` in Windows Task Scheduler to run every minute.)

## Admin Panel Routes

All under `auth:web` middleware, reachable in the browser after logging in at `/login`:

| Route | Purpose |
|---|---|
| `/dashboard/plans` | Manage membership plans |
| `/dashboard/members` | Enroll & manage members |
| `/dashboard/members/{member}/qr` | View a member's QR code |
| `/dashboard/attendance` | Scan member QR codes to check them in |
| `/dashboard/classes` | Manage classes/schedule |
| `/dashboard/bookings` | View class bookings |
| `/dashboard/progress` | View a member's measurement history & progress chart, log new measurements |

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

## Notes

- Data model and feature scope follow the fixed client project spec — treat it as a real deliverable, not a prototype.
- The `storage/app/firebase/service-account.json` credential file must never be committed; it's already covered by `.gitignore`.
