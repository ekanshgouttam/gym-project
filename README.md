# IronForge — Gym Management & Body Transformation Platform

Laravel 8 · MySQL · Blade + Tailwind CSS 3 · GSAP · ApexCharts · spatie/laravel-permission

## Setup (XAMPP, PHP 7.4)

```bash
composer install
npm install
cp .env.example .env   # set DB_DATABASE=gym_management, MAIL_MAILER=log
php artisan key:generate
php artisan migrate --seed
npx mix                # or: npm run watch
php artisan serve
```

> `webpack` is pinned to 5.94 because laravel-mix 6 breaks on newer webpack releases.

### Integrations (`.env`)

| Feature | Keys | Without keys |
|---|---|---|
| Razorpay payments | `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET` | Pay button hidden; memberships are activated by admin approval |
| AI Body Scanner (Claude vision) | `ANTHROPIC_API_KEY` (model `claude-opus-5-5`) | Photos are still accepted and stored; analysis can be re-run later |
| OTP SMS | `SMS_DRIVER=msg91`, `MSG91_AUTH_KEY`, `MSG91_TEMPLATE_ID` | `SMS_DRIVER=log` writes codes to `storage/logs/laravel.log` |

Razorpay webhook: point `payment.captured` at `/webhooks/razorpay`.
The Claude API is called over HTTP because the official PHP SDK needs PHP 8.1+.

AI analysis runs after the response is sent. In production set `QUEUE_CONNECTION=database` and run `php artisan queue:work`.

## AI Body Scanner

- **Baseline at joining:** the QR registration form requires front, side and back photos, weight and height. These are saved as the member's Initial Baseline Scan.
- **Photo checks (computer vision, `PhotoQualityInspector`):** brightness, contrast, sharpness (Laplacian variance) and resolution are measured on the server. Unusable photos are rejected before anything is stored; borderline ones are accepted with tips.
- **Visual reports only:** Claude describes posture, proportions, shape and visible definition. Reports never give a body-fat %, muscle mass or other medical figure, and the UI labels them as observations.
- **30-day rule (`BodyScanService`):** the first scan is allowed immediately; each later scan only 30 days after the last accepted scan. This is enforced on the server under a row lock, so simultaneous uploads can't both get through. Photos reused from an earlier scan are rejected, and the page shows the next date with a live countdown and a disabled upload button.
- **Permanent history:** every accepted scan is kept. Originals are stored byte for byte; members see EXIF-stripped display copies.
- **Admin corrections** (Users → *Scans*): void/restore a scan, correct weight/height, re-run analysis, or allow one early upload. Each action needs a reason and is written to the append-only `scan_audit_logs`.
- **Comparisons** (My Transformation): pick any two scans for side-by-side photos, a slider per view, weight and tape-measurement differences, the date range and an optional AI comparison summary (limited to 10 per member per day).

## QR registration

Each branch has a secret join link encoded in its QR code (**Gyms & QR codes → QR code**, or **Registration QR** for gym admins).
Scanning it opens a mobile form: OTP-verified mobile → profile, baseline body scan photos and plan → account with a Member ID (`IF-IRO1-00001`) and a **pending** membership.
The membership activates on online payment or when an admin approves it under **Membership approvals**.

## Demo accounts (local only, password `Password@123`)

| Role        | Email                 |
|-------------|-----------------------|
| Super Admin | superadmin@gym.test   |
| Gym Admin   | admin@gym.test        |
| Trainer     | trainer@gym.test      |
| Member      | member@gym.test       |

`DemoDataSeeder` only runs in `local`/`testing`. In production run `php artisan db:seed --class=RolesAndPermissionsSeeder`.

## Roles & permissions

Roles and their permissions are defined in one place: [`app/Support/Roles.php`](app/Support/Roles.php).
Re-run `RolesAndPermissionsSeeder` after editing it (it is idempotent).

| Role        | Can do |
|-------------|--------|
| Super Admin | Everything: all gyms, all users, any role |
| Gym Admin   | Trainers and members **in their own gym** only; plans, payments, attendance, reports |
| Trainer     | View members, workout plans, attendance |
| Member      | Own progress, progress photos |

- Route groups are guarded with `role:` / `permission:` middleware (`routes/web.php`).
- Per-record rules live in `app/Policies/UserPolicy.php` (gym scoping, no self-suspend/delete, no privilege escalation).
- Suspended accounts are blocked at login and logged out mid-session (`EnsureUserIsActive`).
- Public registration always creates a **Member**.

## Tests

Tests use the MySQL database `gym_management_test` (see `phpunit.xml`).

```bash
php artisan test
```
