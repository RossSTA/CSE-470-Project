# SRS attainment and disclosure drafts

## Suggested Sprint 1 attainment entry

> Implemented F1 Job and Internship Posting, F8 Profile and Skill Portfolio, and F15 Student Identity Verification with session authentication/password reset, student/client/admin authorization, migrations, repeatable local/test seeders, and an explicitly failing EscrowService stub. Verified acceptance paths through isolated PHPUnit HTTP feature tests, compiled Blade views and production assets, and exercised client/student/admin browser flows using synthetic data. Schema foundations for SRS later features are documented as provisional, with no later workflows implemented. MySQL 8 integration, real SMTP delivery, and MySQL concurrency verification remain pending. The supplied local directory was initially empty and was not a Git repository; GitHub repository/collaborator requirements were not changed or verified. No push/deployment/account changes were performed.

Count **three** features. Authentication, schema, stubs, test routes and documentation do not increase the count. The final automated totals and browser coverage are in VERIFICATION.md; transfer only that verified evidence into the SRS. Do not mark repository/faculty collaboration or all future feature workflows done based on this work.

## Factual AI-use disclosure draft

| Feature/task | Tool | Actual assistance | Student can fully explain? |
|---|---|---|---|
| Project setup/authentication/roles | OpenAI Codex | Read the supplied requirements and SRS, inspected the empty workspace, selected compatible Laravel, installed local tooling/dependencies, generated authentication/controllers/Form Requests/middleware and Blade navigation | Students must confirm |
| F1 posting | OpenAI Codex | Generated posting schema/models/pivot, validation, ownership policy, transactional service/lifecycle/contract guard, controllers, Blade forms/detail/list, screening controls and tests | Students must confirm |
| F8 profile/portfolio | OpenAI Codex | Generated profile/skill/project schema/models, proficiency/URL/uniqueness validation, ownership policies, controllers/views and deterministic completeness formula with tests | Students must confirm |
| F15 verification | OpenAI Codex | Generated private upload storage/access routes, MIME/content/size validation, transactional submission/review service, audit history, student/admin views, reusable verification eligibility and tests | Students must confirm |
| Foundation/schema/stub | OpenAI Codex | Generated provisional later-feature migrations, repeatable catalog/demo seeder and explicitly failing escrow method signatures | Students must confirm |
| Verification/debugging | OpenAI Codex | Ran PHPUnit, migrations/seeders, formatting, Composer validation and asset/view builds; fixed detected failures; performed browser interactions with a synthetic ID and sample posting/profile | Students must confirm |
| Documentation | OpenAI Codex | Drafted setup, acceptance mapping, MVC walkthrough, schema decisions, actual evidence, this attainment suggestion and AI disclosure | Students must confirm |

No independent student understanding, authorship percentage, elapsed contribution time, or individual ownership is asserted. Each member should review, explain, modify and debug the code, then fill the SRS personal-understanding and individual-contribution fields honestly. Retain the actual AI disclosure even if students later rewrite parts.

## Remaining external validation

- MySQL 8 is not installed/available for integration here. Resolution: configure a new dedicated MySQL 8 database using README, run migrations/seeding twice and exercise workflows; run simultaneous submission/review requests to validate InnoDB locking. SQLite sequential tests do not prove cross-process MySQL behavior.
- SMTP delivery was not sent. Resolution: configure a development SMTP server/account, request password reset, use its actual delivered link; do not expose log-mail reset links publicly.
- Repository hosting/collaborator setup was explicitly excluded from authorization. Confirm separately with the team/faculty; no external state was changed.
