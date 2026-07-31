# FitHub — cPanel Deployment Guide

One Laravel app runs everything: the gym admin panel, the RankSol super-admin panel, and the API the mobile app talks to. **You only deploy this once, in one place.** No separate servers, no Node.js server, no background workers needed.

This guide is split into two parts:
- **Part A — you do this, before sending anything to her.** Turns the project into a ready-to-upload zip so she never needs Composer, Node, or a code editor.
- **Part B — she does this in cPanel.** Pure clicking + a short checklist, no coding.

---

## Part A — Prepare the package (you do this)

Run these on your own machine, in the `backend` folder:

```bash
composer install --optimize-autoloader --no-dev
npm run build
```

This downloads everything the app needs (`vendor/`) and builds the CSS/JS (`public/build/`) **locally**, so the zip you send is fully self-contained. She will never run `composer` or `npm` on the server — most shared cPanel plans don't support them well anyway, and this sidesteps that entirely.

Then zip the whole `backend/` folder, **except**:
- `.env` (never send your local one — she'll create a fresh one on the server, see Part B Step 5)
- `.git/`
- `storage/app/firebase/service-account.json` (send this separately, privately — it's a secret credential)
- `node_modules/`

Send her: the zip, the Firebase JSON file (separately), and this guide.

---

## Part B — Deploy on cPanel (she does this)

### What to check with the hosting provider first

- [ ] PHP **8.2 or newer** available (cPanel → **MultiPHP Manager**)
- [ ] **Terminal** or **SSH access** included in the plan (cPanel → look for a "Terminal" icon). Most modern cPanel hosts include this — if it's missing, tell your host you need shell access, or ask me and I'll adjust the steps for FTP-only hosting.
- [ ] A MySQL database is allowed (nearly all cPanel plans include this)
- [ ] A domain or subdomain is ready to point at the app (e.g. `app.yourgym.com` or the main domain)

### Step 1 — Create the database

cPanel → **MySQL Databases**:
1. Create a database (e.g. `fithub`) — cPanel will prefix it with your account name automatically (e.g. `cpaneluser_fithub`).
2. Create a database user with a strong password.
3. Add that user to the database with **All Privileges**.
4. Write down: database name, username, password, host (usually `localhost`).

### Step 2 — Upload the files

cPanel → **File Manager**:
1. Upload the zip **outside** `public_html` — e.g. into a new folder called `fithub_app` at the account root, next to `public_html`.
2. Extract it there.

Uploading outside `public_html` keeps the whole app (including `.env`, database code, etc.) unreachable from the internet — only the `public/` folder inside it should ever be web-visible. This is the standard, secure way to run Laravel on shared hosting.

### Step 3 — Point a domain/subdomain at the app

cPanel → **Domains** (or **Subdomains**):
1. Create the subdomain you want to use (e.g. `app.yourgym.com`), or use the main domain.
2. Set its **Document Root** to `fithub_app/public` (not `fithub_app` — the `public` subfolder specifically).

That's it — no manual `index.php` editing needed, cPanel handles the routing once the document root is set correctly.

### Step 4 — Create the `.env` file

In File Manager, inside `fithub_app/`, create a new file named `.env` (copy `.env.example` as a starting point and edit it). Fill in at minimum:

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

MAIL_MAILER=smtp
MAIL_HOST=(your Gmail SMTP or provider — same as local setup)
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...

TWILIO_SID=...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM_NUMBER=...

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=usd
```

Leave `APP_KEY` blank for now — Step 5 generates it.

⚠️ **Stripe**: the values you've been testing with (`pk_test_...` / `sk_test_...`) are test-mode keys. Going live means switching to **live-mode** keys from the Stripe Dashboard (toggle "Test mode" off, get new keys from Developers → API keys) — see the pricing/Stripe note at the end of this chat for why that switch matters.

### Step 5 — Run the one-time setup commands

Open cPanel's **Terminal**, then:

```bash
cd ~/fithub_app
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

What each does, in plain terms:
- `key:generate` — creates the app's unique encryption key.
- `migrate --force` — builds all the database tables.
- `storage:link` — makes member profile photos/uploads actually viewable in the browser.
- the three `:cache` commands — precompute config/routes/views so the app runs faster on shared hosting.

**No Terminal/SSH available?** Tell me and I'll give you a version of this step that runs the same commands through a temporary web route instead (a bit more manual, but works on pure-FTP hosts).

### Step 6 — Create the first Super Admin login

Still in Terminal:

```bash
php artisan tinker
```

Then paste (replace the email/password):

```php
\App\Models\PlatformAdmin::create([
    'name' => 'Admin',
    'email' => 'admin@ranksol.com',
    'password' => bcrypt('choose-a-strong-password'),
    'role' => 'super_admin',
]);
exit
```

(This is the RankSol platform login — the account that manages all gyms. Each individual gym owner signs up on their own through the public signup page, so you don't need to create gym accounts manually.)

### Step 7 — Set up the scheduler (cron job)

cPanel → **Cron Jobs** → add a new job, run **every minute**:

```
* * * * * cd /home/cpaneluser/fithub_app && php artisan schedule:run >> /dev/null 2>&1
```

This one cron job silently drives everything else: class reminders, renewal reminders, weekly progress reminders, trial-ending emails, and the nightly backup. You only ever add this **one** line — the app decides internally when each task actually needs to run.

### Step 8 — Upload the Firebase credentials file

Upload the `service-account.json` file (sent separately, see Part A) into:

```
fithub_app/storage/app/firebase/service-account.json
```

This is what lets the app send push notifications to the mobile app. Never put this file inside `public/` or commit it anywhere public — it's a secret credential.

### Step 9 — Point Stripe's webhook at the live site

In the Stripe Dashboard (live mode, not test mode): **Developers → Webhooks → Add endpoint**:
- URL: `https://app.yourgym.com/stripe/webhook`
- Events: select the subscription/invoice events (or "select all" if unsure)
- Copy the **Signing secret** it gives you into `.env` as `STRIPE_WEBHOOK_SECRET`, then run `php artisan config:cache` again.

Without this step, Stripe payments will go through but the app won't find out about them (this is exactly the bug we hit and fixed locally yesterday — same fix, just pointed at the live domain instead of your laptop).

### Step 10 — Final checklist

- [ ] Visit `https://app.yourgym.com` — landing page loads
- [ ] `/login` — admin panel login works
- [ ] `/ranksol/login` — RankSol super-admin login works (the account from Step 6)
- [ ] Sign up a fresh test gym through the public signup page
- [ ] Log into the mobile app against the live URL (see note below) and confirm login, classes, and lock features work
- [ ] Make one real test payment in Stripe **live mode** with a real card, confirm the billing page shows it as active
- [ ] Confirm an email arrives (e.g. trigger a password reset)
- [ ] Confirm a push notification arrives (e.g. book a class)

### Mobile app note

The Flutter app currently points at your local dev server. Once the live domain is confirmed working, the app needs to be rebuilt once pointing at it:

```bash
flutter build apk --release --dart-define=API_BASE_URL=https://app.yourgym.com/api
```

That's the only mobile-side change needed for deployment — everything else is the backend above.
