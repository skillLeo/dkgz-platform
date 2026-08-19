# Deployment — DKGZ

The platform targets shared hosting. There is no Redis, no supervisor, no
websocket server and no npm on the server: assets are built locally and
committed, the queue is drained by the scheduler, and the portal polls for
notifications rather than subscribing.

Live target: **dkgz.skillleo.com**, Hostinger, `u290685119@46.202.183.38:65002`.

---

## 1. Before the first deploy

On your own machine:

```bash
composer install
npm install
npm run build          # writes public/build, which IS committed
php artisan test       # must be green
```

Commit `public/build`. The server cannot rebuild it.

---

## 2. Choose the layout

Hosts differ in whether you may point the document root at `/public`. Both
layouts are supported and both ship with their own `.htaccess`.

### Layout A — document root points at `/public` (preferred)

```
domains/dkgz.skillleo.com/
└── dkgz/                 <- the whole repository
    ├── app/ bootstrap/ config/ …
    └── public/           <- document root points HERE
```

`public/.htaccess` is already correct. Nothing else to change.

### Layout B — `public_html` **is** the document root (Hostinger default)

Hostinger serves `domains/<domain>/public_html` and will not let you move the
document root. Split the project in two:

```
domains/dkgz.skillleo.com/
├── dkgz/                 <- Laravel core, NOT web-reachable
│   ├── app/ bootstrap/ config/ database/ resources/ routes/
│   ├── storage/ vendor/ artisan .env
└── public_html/          <- contents of the repo's public/ folder
    ├── index.php         <- from public/index.public_html.php
    ├── .htaccess         <- from .htaccess.public_html
    ├── build/ icons/ robots.txt site.webmanifest apple-touch-icon.png
```

Then:

```bash
cd ~/domains/dkgz.skillleo.com
cp dkgz/public/index.public_html.php public_html/index.php
cp dkgz/.htaccess.public_html          public_html/.htaccess
cp -r dkgz/public/build                public_html/
cp -r dkgz/public/icons                public_html/
cp dkgz/public/site.webmanifest dkgz/public/apple-touch-icon.png public_html/
```

`public_html/index.php` differs from the stock front controller in exactly one
line — the `$core` path that points one level up at the Laravel directory:

```php
$core = __DIR__.'/../dkgz';

require $core.'/vendor/autoload.php';
$app = require_once $core.'/bootstrap/app.php';
```

Re-run those `cp` commands after every deploy that changes `public/`.

---

## 3. Environment

```bash
cd ~/domains/dkgz.skillleo.com/dkgz
cp .env.example .env
php artisan key:generate
```

Then edit `.env`:

```
APP_ENV=production
APP_DEBUG=false                       # never true on the live site
APP_URL=https://dkgz.skillleo.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=u290685119_dkgz
DB_USERNAME=u290685119_dkgz
DB_PASSWORD=<the password from hPanel>

SESSION_SECURE_COOKIE=true            # once HTTPS is confirmed
```

Leave the mail settings alone. The client enters SMTP under
**Administration → Integrationen**, where the password is stored encrypted and
overrides `.env` at runtime.

---

## 4. Install

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan storage:link            # may fail silently; see below
php artisan config:cache route:cache view:cache
```

The seeder prints a one-time super-admin password. Write it down, sign in at
`/admin/anmelden`, change it, then remove `DKGZ_ADMIN_PASSWORD` from `.env`.

**If `storage:link` does nothing** — some shared hosts disable symlinks — the
site still works. `SafeStorage` detects the missing link and serves uploaded
branding through the `/medien/{path}` route instead. Nothing to configure.

Permissions, if the host does not set them:

```bash
chmod -R 775 storage bootstrap/cache
```

---

## 5. The cron line

Add this in hPanel → Advanced → Cron Jobs, running **every minute**:

```cron
* * * * * cd /home/u290685119/domains/dkgz.skillleo.com/dkgz && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

This one line is what makes outgoing mail work. The scheduler starts a
short-lived queue worker each minute (`queue:work --stop-when-empty
--max-time=55`, guarded by `withoutOverlapping()`), which drains the mail queue
and exits. Without the cron entry no e-mail ever leaves the system — every
message will sit in the `jobs` table and the admin **System** page will show a
growing queue depth.

The same schedule also anonymises old requests nightly for GDPR retention.

---

## 6. Deploying an update

With SSH:

```bash
cd ~/domains/dkgz.skillleo.com/dkgz
./deploy.sh
```

The script pulls, installs, migrates, rebuilds caches and brings the site back
up even if a step fails. It never runs a build — `public/build` comes from the
repository.

Without SSH, upload over SFTP, then in hPanel's terminal or via a one-off cron:

```bash
php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan cache:clear
```

Always run `php artisan cache:clear` after a deploy: settings and page content
are cached for an hour.

---

## 7. Verifying the deploy

```bash
curl -sI https://dkgz.skillleo.com                 # expect 200
curl -sI https://dkgz.skillleo.com/anmelden        # expect 200
curl -s  https://dkgz.skillleo.com | grep -c googleapis   # expect 0
php artisan about --only=environment
```

In the admin panel, check **System**: the storage link, the SMTP status, the
debug flag and the queue depth are all reported there. Then send a test mail
from **Integrationen** — it reports the real SMTP error if the credentials are
wrong.

---

## 8. Troubleshooting a 503

A 503 from the *host's own* error page (dark background, "Service Unavailable")
is not the application. In order of likelihood:

1. **SSH/shell disabled or the account is suspended.** Check hPanel → Advanced →
   SSH Access. If SSH authenticates but the session closes immediately, the
   shell is switched off for the account.
2. **`public_html` is empty**, or `index.php` is missing, so there is nothing to
   serve.
3. **PHP version too low.** The application needs PHP 8.3+. Set it in
   hPanel → Advanced → PHP Configuration.
4. **`vendor/` was not uploaded** and Composer was never run, so
   `require vendor/autoload.php` fails before Laravel can render anything.
5. **`storage/` or `bootstrap/cache/` not writable**, which kills the boot
   before the error handler exists. `chmod -R 775 storage bootstrap/cache`.
6. **The app's own maintenance mode.** That renders the DKGZ-styled 503, not the
   host's. Clear it with `php artisan up`, or switch off Wartungsmodus under
   Administration → Einstellungen → Funktionen.
