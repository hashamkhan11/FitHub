# FitHub — cPanel Upload Guide

One Laravel app runs everything: the gym admin panel, the RankSol admin panel, and the API the mobile app uses. Deploy it once, in one place. No extra servers, no Node server, no background workers.

This guide has two parts:
- **Part A — Before sending the zip.** Get the project ready to upload so no one needs Composer, Node, or a code editor on the server.
- **Part B — Uploading on cPanel.** Just clicking and a short checklist, no coding needed.

---

## Part A — Get the project ready

Run these two commands inside the `backend` folder:

```bash
composer install --optimize-autoloader --no-dev
npm run build
```

This downloads everything the app needs (`vendor/`) and builds the CSS/JS (`public/build/`) on your own computer, so the zip is fully ready to go. No one needs to run `composer` or `npm` on the server — most cPanel plans don't handle that well anyway.

### What to zip

Only the **`backend`** folder goes to cPanel. The `mobile` folder (the phone app) and `esp32-lock` folder (device firmware) are separate — they don't get uploaded to a website host.

Zip the whole `backend` folder, **except** these:
- `.env` — never send the local one. A fresh one gets made on the server (Part B, Step 4).
- `.git/` — not needed to run the app.
- `node_modules/` — only needed to build the CSS/JS, which is already done in `public/build/`.
- `storage/app/firebase/service-account.json` — send this file separately and privately. It's a secret key, not something to zip along with everything else.
- `tests/` — only used for local checks, safe to leave out (optional, not required).

Everything else in `backend` — including `vendor/`, `public/build/`, `storage/`, `database/` — goes in the zip as-is.

Send: the zip, the Firebase JSON file (separately), and this guide.

---

## Part B — Upload on cPanel

### First, check with the hosting provider

- [ ] PHP **8.2 or newer** (cPanel → **MultiPHP Manager**)
- [ ] **Terminal** or SSH access (cPanel → look for a "Terminal" icon)
- [ ] MySQL database allowed (almost every cPanel plan has this)
- [ ] A domain or subdomain ready to point at the app

### Step 1 — Create the database

cPanel → **MySQL Databases**:
1. Create a database (example: `fithub`). cPanel adds a prefix automatically (example: `cpaneluser_fithub`).
2. Create a database user with a strong password.
3. Add that user to the database with **All Privileges**.
4. Write down the database name, username, password, and host (usually `localhost`).

### Step 2 — Upload the files

cPanel → **File Manager**:
1. Upload the zip **outside** `public_html` — for example, into a new folder called `fithub_app` next to `public_html`.
2. Extract it there.

Keeping it outside `public_html` means the app's code and `.env` file stay hidden from the internet. Only the `public/` folder inside it should ever be visible to visitors. This is the normal, safe way to run Laravel.

### Step 3 — Point a domain at the app

cPanel → **Domains** (or **Subdomains**):
1. Create the subdomain to use (example: `app.yourgym.com`), or use the main domain.
2. Set its **Document Root** to `fithub_app/public` — the `public` folder inside it, not the top folder.

That's it. cPanel handles the routing once the document root is set right.

### Step 4 — Create the `.env` file

In File Manager, inside `fithub_app/`, make a new file named `.env`. Copy `.env.example` as a starting point and fill in:

```
APP_NAME=FitHub
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.yourgym.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=cpaneluser_fithub
DB_USERNAME=cpaneluser_fithubuser
DB_PASSWORD=the-password-you-set

SUPPORT_EMAIL=(a real support email address — shown on the site and in emails)

MAIL_MAILER=resend
RESEND_KEY=(get this from resend.com/api-keys)
MAIL_FROM_ADDRESS=(a verified sender address — see note below)

TWILIO_SID=...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM_NUMBER=...

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=usd
```

Leave `APP_KEY` blank — Step 5 fills that in.

⚠️ **Mail note:** email is sent through Resend. Until a domain is verified in the Resend dashboard, `MAIL_FROM_ADDRESS` must stay `onboarding@resend.dev` (their shared test address), or emails will get rejected. Verify a real domain in Resend when possible so emails come from your own address.

⚠️ **Stripe note:** the keys used for testing (`pk_test_...` / `sk_test_...`) only work in test mode. Going live means switching to **live-mode** keys from the Stripe Dashboard (turn off "Test mode", get new keys under Developers → API keys).

### Step 5 — Run the one-time setup commands

Open cPanel's **Terminal**:

```bash
cd ~/fithub_app
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

What these do, in short:
- `key:generate` — makes the app's encryption key.
- `migrate --force` — builds all the database tables.
- `storage:link` — makes profile photos and uploads visible in the browser.
- the three `:cache` commands — speed the app up on shared hosting.

**No Terminal access?** These same steps can be run through a temporary web route instead — ask for that version if the host is FTP-only.

### Step 6 — Create the first admin login

Still in Terminal:

```bash
php artisan tinker
```

Then paste (change the email and password):

```php
\App\Models\PlatformAdmin::create([
    'name' => 'Admin',
    'email' => 'admin@ranksol.com',
    'password' => bcrypt('choose-a-strong-password'),
    'role' => 'super_admin',
]);
exit
```

This is the RankSol login — the account that manages all gyms. Each gym owner signs up on their own through the public signup page, so gym accounts don't need to be created by hand.

### Step 7 — Add the subscription plans

Log in with the account from Step 6, go to the RankSol admin panel → **Subscription Plans**, and create at least one plan (name, price, Stripe price ID, hardware access on/off). Gym signup needs at least one active plan to work — this step is easy to miss since nothing creates a plan automatically.

### Step 8 — Set up the cron job

cPanel → **Cron Jobs** → add a new job, run **every minute**:

```
* * * * * cd /home/cpaneluser/fithub_app && php artisan schedule:run >> /dev/null 2>&1
```

This one line drives everything else on a schedule: class reminders, renewal reminders, weekly progress reminders, trial-ending emails, and the nightly backup. Just this one line — the app decides internally when each task needs to run.

### Step 9 — Upload the Firebase file

Upload the `service-account.json` file (sent separately, see Part A) to:

```
fithub_app/storage/app/firebase/service-account.json
```

This lets the app send push notifications to the phone app. Never put this file inside `public/` or anywhere public — it's a secret key.

### Step 10 — Point Stripe's webhook at the live site

In the Stripe Dashboard (live mode, not test mode): **Developers → Webhooks → Add endpoint**:
- URL: `https://app.yourgym.com/stripe/webhook`
- Events: pick the subscription/invoice events, or "select all" if unsure
- Copy the **Signing secret** into `.env` as `STRIPE_WEBHOOK_SECRET`, then run `php artisan config:cache` again.

Without this step, Stripe payments will go through, but the app won't find out about them.

### Step 11 — Final checklist

- [ ] Visit `https://app.yourgym.com` — the landing page loads
- [ ] `/login` — gym admin login works
- [ ] `/ranksol/login` — RankSol admin login works (the account from Step 6)
- [ ] Sign up a test gym through the public signup page
- [ ] Log into the phone app against the live URL (see note below) and check login, classes, and lock features work
- [ ] Make one real test payment in Stripe **live mode**, confirm the billing page shows it as active
- [ ] Confirm an email arrives (example: trigger a password reset)
- [ ] Confirm a push notification arrives (example: book a class)

### Phone app note

The phone app currently points at a local test server. Once the live domain works, rebuild it once, pointed at the live site:

```bash
flutter build apk --release --dart-define=API_BASE_URL=https://app.yourgym.com/api
```

That's the only phone-app change needed — everything else is the steps above.
