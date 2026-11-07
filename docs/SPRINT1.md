# Sprint 1 acceptance and verification

The source requirements are the supplied CampusGig SRS Sections 4, 7 and 8, and the explicit implementation request. Only F1, F8 and F15 count as completed features. The checks below distinguish automated HTTP feature coverage from actual browser interaction.

## Acceptance mapping

Tests are in `tests/Feature/SprintOneTest.php`; method names below omit the `test_` prefix. They use Laravel's real HTTP kernel, Form Requests, policies, services, Eloquent and isolated migrations. They are not static mockups.

| Criterion | Implementation | Automated evidence |
|---|---|---|
| Registration, login/logout/reset | AuthController; Laravel guard/broker; auth views | student_and_client_registration_and_admin_rejection; login_logout_and_bad_password; password_reset_uses_broker_and_changes_password |
| Public student/client only; controlled local admin | RegisterRequest; DatabaseSeeder | student_and_client_registration_and_admin_rejection; production_seeder_creates_catalog_without_demo_accounts |
| Roles, authenticated dashboards/navigation | RoleMiddleware; routes; dashboard/layout | guests_and_wrong_roles_are_blocked; role_dashboards_and_views_render |
| Repeatable catalog/demo seeding | DatabaseSeeder uses first/create checks; production has catalog only | seeders_repeat_without_duplicates; production_seeder_creates_catalog_without_demo_accounts |
| Full SRS schema foundation | Core and future migrations; SCHEMA.md | RefreshDatabase executes migrations for every feature run; started_contract_blocks_edit_even_when_completed; fresh local migrate/seed run |
| Explicitly failing escrow stub | EscrowService | escrow_stub_explicitly_fails_without_balance_changes |
| F1: valid gig and internship drafts/publication | JobController, JobRequest, JobService, Job | gig_and_internship_persist_and_publish |
| F1: title/description/type/skills/budget/deadline/questions persist | Job casts and skill pivot; save transaction | gig_and_internship_persist_and_publish; job_update_syncs_skills_questions_and_isolation |
| F1: invalid deadline, nonpositive/overprecision budget, missing/unknown skills/questions | JobRequest; publish-time revalidation | job_invalid_budgets_deadlines_and_relationships; job_lifecycle_is_one_way_and_publish_rechecks_deadline |
| F1: explicit one-way states; no reopening | Job::transition, transactional locking | job_lifecycle_is_one_way_and_publish_rechecks_deadline |
| F1: ownership and private drafts | JobPolicy; role middleware | job_owner_authorization_and_draft_privacy; guests_and_wrong_roles_are_blocked |
| F1: contract-start edit lock | Job::editable; JobService lock; Contract foundation | started_contract_blocks_edit_even_when_completed |
| F1: list/create/edit/detail; pagination/status; student published viewing | jobs Blade views; index/browse routes | role_dashboards_and_views_render; job_owner_authorization_and_draft_privacy; gig_and_internship_persist_and_publish; browser creation/publication/list viewing |
| F8: basics/name/university/department/bio/availability | ProfileRequest; StudentProfileController | profile_basics_and_completeness_from_persisted_data |
| F8: add/update/remove skills and projects | StudentProfileController; profile views | profile_basics_and_completeness_from_persisted_data |
| F8: reject duplicate skills, unsupported proficiency and bad URLs | StudentSkillRequest; unique DB constraint; PortfolioRequest | skill_duplicates_proficiency_and_urls_are_rejected; profile_skill_unique_constraint |
| F8: score responds to additions/removals; missing feedback | StudentProfile::completeness; edit view | profile_basics_and_completeness_from_persisted_data (10 → 60 → 80 → 100 → 80 → 60) |
| F8: all profile/project ownership endpoints | StudentProfilePolicy, PortfolioItemPolicy; nested ownership | all_profile_mutations_require_ownership; nested_skill_must_belong_to_target_profile |
| F8: profile display excludes ID and sensitive account data | profile/show explicitly renders safe fields | profile_display_omits_private_identity_and_account_data |
| F15: accepted upload creates pending private record | SubmitVerificationRequest; VerificationService | valid_upload_pending_duplicate_block_and_private_storage; real_pdf_and_jpeg_content_are_accepted |
| F15: approval enables verification eligibility | User::isVerified; ProposalEligibility; verified.student middleware | approval_records_audit_and_unlocks_reusable_verification_guard |
| F15: rejection requires nonblank reason, new upload preserves history | ReviewVerificationRequest; VerificationService | rejection_reason_and_resubmission_preserve_history |
| F15: wrong/disguised/oversized/missing upload rejection | MIME rules, finfo/image content check; 5120 KiB max | upload_mime_content_size_and_missing_validation |
| F15: submitting student/admin only can view document; admin-only review | VerificationRequestPolicy; identity disk; protected routes | private_document_and_review_authorization; profile_display_omits_private_identity_and_account_data |
| F15: no duplicate pending/approved submissions | User-row lock and status query | valid_upload_pending_duplicate_block_and_private_storage; approval_records_audit_and_unlocks_reusable_verification_guard |
| F15: conflicting/repeated decisions blocked, reviewer/time/reason preserved | Request-row lock; transaction and pending-only transition | repeated_and_conflicting_reviews_cannot_change_audit; rejection_reason_and_resubmission_preserve_history |
| F15: pending/rejected blocked for future proposal identity requirement | ProposalEligibility and middleware, no production proposal route | approval_records_audit_and_unlocks_reusable_verification_guard; rejection_reason_and_resubmission_preserve_history |
| Responsive real-data UI; empty states; success/errors; confirmations | app layout/CSS and feature views; minimal JS | role_dashboards_and_views_render; actual browser checks below; confirmation dialogs observed |

## Commands actually executed

Executed in this workspace using `.tools/php/php.exe` (PHP 8.3.36), workspace Composer and npm. Final exact test totals/check results are recorded in `VERIFICATION.md` after the last run.

| Command | Observed result |
|---|---|
| `php artisan package:discover` | Passed after running outside Windows sandbox; no vendor changes |
| `php artisan --version` | Laravel Framework 13.40.0 |
| `php artisan route:list --except-vendor` | 36 application routes, before later workflow additions (none added) |
| `php artisan migrate --seed --no-interaction` | Passed all five migrations on a newly created local SQLite file |
| `php artisan db:seed --no-interaction` | Passed repeated seed on same new local database |
| `php vendor/phpunit/phpunit/phpunit --testdox --colors=never` | Final result in VERIFICATION.md |
| `php vendor/bin/pint` then `php vendor/bin/pint --test` | Formatted generated PHP; final check in VERIFICATION.md |
| `composer validate --strict` | Passed |
| `npm install --ignore-scripts` | Installed 55 packages; npm reported zero vulnerabilities at install time |
| `npm run build` | Passed after removing an unused remote-font fetch and nonexistent JS bootstrap import |
| `php artisan view:cache` | Passed |

Initial test runs exposed the queue table name collision, nested validation error-key expectation, fake file MIME behavior, and production console-confirmation behavior in a test. These were corrected. Browser review exposed a malformed Blade `@else` token on the student dashboard; it was fixed and guarded by a test. A failed intermediate run is not counted as a pass.

## Browser evidence and manual coverage

Actual browser interaction used the local SQLite demo and synthetic data. Checked layouts at 1280×800 desktop and 390×844 mobile; initial client creation used the browser's narrow default viewport. No real student ID was used.

- Client login, dashboard, create form, persisted gig draft with Laravel skill, BDT 15,000, future deadline and screening question, publication confirmation and published detail.
- Student login/dashboard, profile form, persisted bio update and completeness display; published opportunities list showed the newly created posting.
- Student verification page, synthetic PNG file chooser upload, pending result/history.
- Admin login/dashboard, queue/detail, authorized private-document rendering, review confirmation, approved result showing reviewer/time, final review state.
- Student re-login displayed approved status derived from the review.
- Desktop student editing added a PHP skill and a second portfolio project, then displayed both on the profile page.
- Responsive screenshots inspected for admin detail, student opportunity list and client posting detail. Additional final layout checks are recorded in VERIFICATION.md.

Automated HTTP tests, rather than browser automation, provide exhaustive invalid input, ownership, lifecycle, rejection/resubmission and repeated-review coverage. Actual SMTP delivery and concurrent MySQL requests were not tested.

## Repeatable manual walkthrough

1. Start as client. Create a gig and an internship. Add/remove screening questions; select skills. Save draft, edit it, publish, then close. Confirm closure cannot reopen. Submit zero/negative budgets and today/past dates to see errors (direct HTTP tests cover browser-constraint bypass).
2. Use another client account. Attempt the original posting's detail/edit/publish/close URLs. Expect 403. Log in as a student: draft/closed detail is denied and neither appears in opportunities.
3. Student profile: clear optional fields and observe missing categories. Save basics, add a catalog skill/proficiency, and add a project with HTTPS URL. Score changes according to ARCHITECTURE.md. Try duplicate skill, unsupported proficiency via a crafted request, and `javascript:`/FTP project URL. Edit/delete projects and skills. Try another student's profile/project URLs and expect denial.
4. Use a newly registered student (the supplied demo student was approved during browser testing). Upload a synthetic JPEG, PNG or PDF ≤5 MiB; observe pending/history. Reupload while pending is blocked. A text file renamed `.png` and a file over 5 MiB must fail.
5. Admin queue: open request and private document. Reject with an empty/whitespace reason (error), then reject with a clear reason. Student sees it and submits a new document. Admin approves the new record. Earlier rejected record remains. Re-review direct requests fail. Student dashboard says approved and future identity eligibility passes.
6. Use an unrelated student/client/guest to request the private-document URL. Expect 403 for authenticated unrelated actors and login redirect for guests. Profile display must never contain document paths or account email/hash.
7. Run the feature test `started_contract_blocks_edit_even_when_completed` to demonstrate contract-start locking without implementing a contract UI. Run the eligibility/escrow tests to explain permitted stubs/guards.
8. Repeat main pages at desktop and phone sizes, including forms with validation errors, empty lists, screening controls and confirmations. Use a dedicated fresh MySQL 8 database for final integration sign-off.
