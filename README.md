# CampusGig - Sprint 1

Student freelance and internship board for CSE470. Implemented counted features: **F1 posting, F8 profile/portfolio, F15 identity verification**. Authentication is a supporting foundation. Later workflows have schema only.

The workspace was empty, with no Git repository, prior dependencies, code, or AGENTS.md instructions. The attached SRS was read in full before implementation. Nothing was pushed or deployed and no external account was changed.

## Prerequisites

- PHP **8.3** with ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, pdo_mysql, session, tokenizer, xml, xmlwriter and zip. Enable pdo_sqlite/sqlite3 for isolated tests.
- Composer 2, MySQL **8**, Node.js 22.12+ (or compatible current LTS), npm.
- Exact installed framework: **Laravel 13.40.0**, locked in `composer.lock`; PHP used for verification: **8.3.36**; PHPUnit **12.5.43**. [Laravel 13 supports PHP 8.3](https://laravel.com/framework/docs/13.x/releases).
- Tailwind 4, Blade, Vite; no SPA or extra backend service. Lockfiles pin all resolved dependencies.

## Set up with MySQL 8

Create an empty database named `campusgig` and a database user with schema privileges using your local database administrator tool. Do not point this app or its tests at another project's database.

```powershell
composer install
Copy-Item .env.example .env
```

Set `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=campusgig`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`. The example contains no credentials. `APP_TIMEZONE=Asia/Dhaka` controls dates/deadlines. For a local demonstration keep `APP_ENV=local`.

```powershell
php artisan key:generate
php artisan migrate --seed
npm ci --ignore-scripts
npm run build
php -d upload_max_filesize=6M -d post_max_size=8M artisan serve --host=127.0.0.1 --port=8000
```

Open [CampusGig](http://127.0.0.1:8000). For asset development, run `npm run dev` in another terminal. Use `npm run build` for a self-contained local demo with no Vite server. No CDN or external font is required. Configure the same upload limits in the PHP/web-server configuration when not using `artisan serve`.

## Ready-to-run local demo in this workspace

A portable PHP 8.3 runtime and Composer are available under ignored `.tools/`; dependencies and built assets are installed. `.env` uses the newly created `database/campusgig-local.sqlite` demo database. This was created for this task; no existing database was reset. Its sample student has a synthetic ID approved during browser testing, and its client has an additional published browser-demo posting. Repeat seeding preserves these changes.

```powershell
.tools/php/php.exe -d upload_max_filesize=6M -d post_max_size=8M artisan serve --host=127.0.0.1 --port=8000
```

The development server may already be running on port 8000. A fresh checkout should use the MySQL setup above. To make a separate SQLite demo, create a new empty file, set `DB_CONNECTION=sqlite` and `DB_DATABASE` to that file, then run `php artisan migrate --seed`. This is a demonstration fallback, not MySQL 8 verification.

## Local/test demo accounts

These fixed credentials are generated **only** when `APP_ENV` is `local` or `testing`. Do not use them on a public server.

| Role | Email | Password |
|---|---|---|
| Student | student@campusgig.test | CampusGig!2026 |
| Client | client@campusgig.test | CampusGig!2026 |
| Admin | admin@campusgig.test | CampusGig!2026 |

Public registration allows student/client roles only. The controlled seeder creates the local admin. Production seeding creates the skill catalog only. A production admin provisioning command is outside the local Sprint 1 demo scope; never add admin to the public registration dropdown.

Seeders use `firstOrCreate`/new-record checks, so reruns do not duplicate samples, reset passwords, overwrite profiles, or reopen postings. Sample gig, internship, skill proficiency, and portfolio data are included. Future tables intentionally contain no fictitious payments or workflow records.

## Authentication and email

Laravel session authentication regenerates the session on login and invalidates it on logout. Laravel's password broker handles reset tokens, hashing and reset notifications. Login, registration, reset and upload endpoints are throttled. All write forms include CSRF protection.

The default `MAIL_MAILER=log` writes local reset links to `storage/logs/laravel.log`; it does not deliver email. To verify real email delivery, configure SMTP in `.env` (`MAIL_MAILER=smtp`, host, port, username, password and sender). Keep mail credentials out of version control. Reset requests return a generic response to avoid revealing account existence.

## Verify

```powershell
php artisan test
php vendor/bin/pint --test
composer validate --strict
npm run build
php artisan view:cache
php scripts/verify-schema.php
```

`phpunit.xml` forces `APP_ENV=testing`, SQLite `:memory:` and an empty `DB_URL`, even when the shell supplies another database. Each feature test uses isolated data and rolls back its transaction. Never use `migrate:fresh` against an existing non-test database. The test encryption key is a published test fixture, not an application secret.

For this machine substitute `.tools/php/php.exe` for `php`, `.tools/php/php.exe .tools/composer.phar` for `composer`, and `node .tools/package/bin/npm-cli.js` for `npm` if those commands are absent from PATH. Windows sandbox virtualization caused PHP file resolution failures in this OneDrive workspace; checks succeeded when run in a normal process. No vendor patches or special application cache paths were introduced.

MySQL 8 integration is **not verified**: only SQLite was used for schema/test execution. The installed XAMPP client is MariaDB 10.4.32, which is not a MySQL 8 substitute. On a new dedicated MySQL 8 database, run `php artisan migrate --seed`, repeat `php artisan db:seed`, and exercise the checklist. The InnoDB row-lock guarantees in VerificationService especially need MySQL concurrency validation; sequential duplicate/conflicting request tests pass on SQLite.

## Handoff documents

- [Acceptance checklist and actual verification](docs/SPRINT1.md)
- [Final command results and limitations](docs/VERIFICATION.md)
- [MVC viva walkthrough and business rules](docs/ARCHITECTURE.md)
- [SRS schema foundation and provisional decisions](docs/SCHEMA.md)
- [Suggested attainment and factual AI disclosure](docs/HANDOFF.md)

No payment, proposal, contract, search/filter, recommendation, interview, withdrawal, review, dispute, notification, or moderation workflow is implemented. `Contract` exists only to test the posting edit guard; `EscrowService` always throws explicitly.
