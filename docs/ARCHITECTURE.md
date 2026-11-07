# MVC walkthrough

Start with `routes/web.php`: the `auth` middleware requires a session, `role` selects an actor, Form Requests validate data, policies check ownership, and thin controllers delegate business rules to Eloquent models/services before returning Blade views.

## F1 - posting

- `Job` owns posting data, required skills, screening questions and contract relationship. `JobSkill` names the shared catalog pivot; the database enforces a unique job/skill pair. Budgets use `DECIMAL(12,2)` in BDT. Questions use a JSON array, maximum ten strings of 500 characters.
- `JobRequest` validates required title/description/type, positive budget with at most two decimals, a date strictly after today's application date, distinct existing skills, and questions. It authorizes ownership before validating an edit.
- `JobPolicy` permits management only by the owning client. Admin can inspect any posting, including drafts; students can view only published postings. Other clients cannot inspect another client's management/details pages even if published.
- `JobService` writes a posting and skill synchronization in one transaction. It locks an existing posting before checking `editable()`. A closed posting or any related contract with non-null `started_at` blocks edits forever, including a completed contract. An unstarted offer does not lock editing. Later contract-start code must coordinate on the same job lock; no such workflow exists yet.
- `Job::transition()` permits only **draft → published → closed**. Publishing rechecks deadline, budget and presence of skills. Closed postings cannot reopen. Editing a published posting is allowed before a contract starts; its status cannot be supplied in the update payload.
- `JobController` lists the client's own paginated postings, serves forms/detail and calls the service. Student browsing is only a paginated list of published opportunities; no F9 search/filter UI exists.
- `resources/views/jobs` renders forms, skill checkboxes, add/remove screening-question controls, states and explicit confirmations for publication/closure.

## F8 - profile and portfolio

- `StudentProfile` belongs to one User. `StudentSkill` connects a profile to shared `Skill` with beginner/intermediate/advanced proficiency. Beginner means learning basics; intermediate means independent work; advanced means complex work/mentoring. A composite unique database constraint prevents duplicate skills even if validation is bypassed.
- `PortfolioItem` holds a title, description and one HTTP/HTTPS URL. Multiple projects supply multiple links. No private verification relation is exposed by profile views.
- Profile, skill and portfolio Form Requests validate fields and policy ownership. Controllers also authorize deletion/edit-page requests and check nested skill IDs belong to the profile in the URL. A user cannot change another student's name by posting to that profile.
- `StudentProfileController` manages basic profile/name, skill add/update/remove and project CRUD. Basic fields/name save transactionally. Views are `profile/edit`, `profile/show` and project templates. Display pages require sign-in and contain only name, academic fields, bio, availability, skills and portfolio links; no email, hash, ID document or storage path appears.

**Completeness formula:** Name 10 + university 15 + department 10 + bio 15 + availability 10 + at least one skill 20 + at least one portfolio item 20 = maximum **100**. Each field's weight is awarded in full if nonblank. Multiple skills/projects do not add extra points. `completeness()` derives this from the loaded persisted profile/user plus current relationship existence queries on every request. There is no stored score column or client-settable score. Deleting the last skill/project removes its weight. It returns both the score and missing categories for feedback.

## F15 - verification

- Domain model `VerificationRequest` is distinct from `SubmitVerificationRequest` and `ReviewVerificationRequest` (HTTP Form Requests).
- **No submission → pending → approved OR rejected.** A rejection is final for that record; resubmission creates a new pending record, preserving original path, reason, admin and review time. Pending/approved submissions block new uploads. Approval is final and no revocation feature exists in this sprint.
- Uploads: JPEG/PNG/PDF only, **5 MiB (5120 KiB)** maximum. Laravel file/MIME validation plus independent fileinfo sniffing reject disguised content; images must pass `getimagesize`. The application does not trust a client MIME header. PDF checks identify MIME, not malware or semantic authenticity; the admin performs the identity review.
- Storage: generated filenames on the dedicated `identity` local disk, rooted at `storage/app/identity`, outside `public`, with URL serving disabled. No `storage:link` is needed for identity files. Only `verification.document` serves files after a policy check. Responses include private/no-store caching, nosniff and a restrictive CSP. Only the submitting student or admin may read them.
- `VerificationService::submit()` locks the User row inside a transaction before checking pending/approved records. It removes a newly stored file if database creation fails. This serializes same-student submissions on MySQL/InnoDB.
- `review()` validates its decision and nonblank rejection reason, locks the request, and rejects every second/conflicting review. It records status, reason, reviewer and timestamp together. `VerificationRequestPolicy`, admin role middleware and service role checks guard review.
- `User::isVerified()` queries authoritative approved records. User registration/profile updates never accept verified flags. `ProposalEligibility::allows()` and the reusable `verified.student` middleware share that check. Tests attach the middleware to a test-only route: pending/rejected block it, approval permits it. There is no production proposal endpoint.
- `VerificationController` serves student history, admin queue/detail, private documents and delegates mutations. Student and admin views are under `verification` and `admin`.

## Foundations

`AuthController` uses Laravel's session guard/password broker. Registration creates a profile for students atomically; public roles are strictly student/client. `RoleMiddleware` enforces actor restrictions before controllers. Policies use IDs/roles from the authenticated session, never hidden inputs. Self-registration and reset notifications require no external identity service.

`EscrowService` has provisional signatures:

```php
fund(int $milestoneId, string $amount, string $idempotencyKey): never
hold(int $milestoneId, string $gatewayReference): never
release(int $milestoneId, string $idempotencyKey): never
refund(int $milestoneId, string $idempotencyKey): never
```

Amounts are decimal strings to avoid float money. All four throw `LogicException` immediately. They perform no database or gateway write. Future signatures/return types, gateway confirmation, fee rules and transaction boundaries need agreement before implementation; `never` must change when real operations are built. No agreed signatures existed in the empty workspace.
