# Actual verification record

Verified on 8 November 2026 (Asia/Dhaka) in the supplied Windows workspace. The final application uses Laravel 13.40.0, PHP 8.3.36 and PHPUnit 12.5.43.

| Check | Actual command (portable tools) | Final evidence |
|---|---|---|
| Acceptance suite | `.tools/php/php.exe artisan test --compact` | **28 tests passed, 257 assertions**. Includes 27 SprintOne feature tests and one home-route feature test; the skeleton's trivial unit assertion was removed. |
| Full formatting | `.tools/php/php.exe vendor/bin/pint --format agent` | Passed. `--dirty` was attempted as required by generated guidelines, but requires Git; this directory is not a Git repository, so the full formatter was used. |
| Safe schema round trip | `.tools/php/php.exe scripts/verify-schema.php` | Passed fresh migration, seed twice (3 users, 2 jobs, 7 skills, 33 tables including framework support), full rollback, and remigration on **SQLite :memory:**. Script refuses any other connection/database. |
| Local persistent demo schema | `.tools/php/php.exe artisan migrate --seed --no-interaction` then `artisan db:seed --no-interaction` | Passed all five migrations and repeat seed on the newly created `database/campusgig-local.sqlite`; no existing database was reset. |
| Composer | `.tools/php/php.exe .tools/composer.phar validate --strict` | Passed after installing the skeleton-required Boost dev tooling. Lockfile pins framework and dependencies. |
| Blade | `.tools/php/php.exe artisan view:cache` | Passed. |
| Production assets | `node .tools/package/bin/npm-cli.js run build` | Passed: Vite 8.3.6, 3 modules transformed; manifest, CSS and JS written under public/build. |
| Browser | In-app browser against local demo | Client draft/create/publication, student profile save/skill add/project add and published list, synthetic ID upload/pending, admin queue/private image/review/approval, student approved dashboard. |
| Responsive layouts | Screenshots inspected at narrow/default viewport, 1280×800, 390×844 | Client detail, student opportunities/profile display/editing and admin review layouts inspected. No clipping or horizontal page overflow observed in those views; long pages scroll normally. |

Additional setup checks: official PHP ZIP SHA-256 and Composer installer SHA-384 verified; `artisan package:discover`, framework version and application route listing succeeded. Boost 2.10.4 was installed as a dev dependency and `boost:install --guidelines --no-interaction` generated AGENTS.md/CLAUDE.md guidelines only; no MCP access or cloud integration was enabled. There was no `.ai/rules` directory after setup.

The browser fixture was a generated image explicitly labeled “SYNTHETIC TEST FIXTURE / NOT A REAL ID”. Its approval is local demo data, not a real identity determination. Browser changes were retained: extra client posting, updated student bio, PHP skill, second portfolio project and approved synthetic verification. A clean seeder run creates the base accounts/profile/catalog and two published postings, not an automatically approved real student.

## Failures found and corrected

- Empty initial workspace and absent PHP/Composer in PATH: workspace-local PHP/Composer/npm installed; no system installation required.
- Sandbox virtualization caused PHP OneDrive writability/autoload/atomic-rename failures: runtime checks ran in a normal process. Vendor code stayed unchanged.
- Framework queue `jobs` collided with domain `jobs`: queue migration/config now use `queue_jobs`.
- Missing isolated-test encryption key: test-only key added to phpunit.xml; application key generated separately in ignored .env.
- A nested skill validation error was asserted under a parent key: test corrected to inspect errors, while relation persistence/counts remain asserted.
- Laravel's fake uploaded file can report a MIME from its filename: independent finfo/content validation added, testing disguised uploads meaningfully.
- Unused skeleton remote-font fetch and nonexistent JS bootstrap import blocked the asset build: both removed; the UI uses system fonts and minimal JS.
- Browser exposed `@elseHelp` as literal text: Blade directive fixed and dashboard regression assertion added.
- Production seeder test triggered Artisan's production confirmation: test now invokes the seeder directly against its isolated database.
- SQLite rollback needed the role index dropped first: migration down() corrected and schema round trip passed.
- Verification script import order was corrected after formatting revealed an unresolved class alias; subsequent round trip passed.

## Explicit limits

**MySQL 8 integration and cross-process locking have not been verified.** The available XAMPP client is MariaDB 10.4.32. SQLite sequential tests verify validation, persistence, transitions and authorization, but do not prove InnoDB concurrent-request semantics. See README/HANDOFF for exact resolution using a new dedicated MySQL 8 database.

Real SMTP delivery was not tested; Laravel broker notification/reset behavior was tested using notification fakes, and local configuration logs mail. Browser testing sampled the main paths; negative cases, rejection/resubmission, ownership, contract guard and repeated reviews were verified through PHPUnit rather than exhaustive browser clicks. No external accounts, GitHub collaborators, pushes or deployments were changed.
