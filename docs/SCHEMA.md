# SRS schema foundation

All application data uses Eloquent, schema uses Laravel migrations, and there is no application raw SQL. Migrations run in dependency order and `down()` drops children before parents. Foundational schema is provided for every SRS entity group; only F1/F8/F15 have usable workflows in this sprint.

| SRS features | Tables and relationships | Notes |
|---|---|---|
| Auth/roles | users; password_reset_tokens; sessions | Public roles restricted by Form Request; admin via local/test seeder. suspended_at reserved for F20. |
| F1 | jobs → users(client); job_skills → jobs, skills | DECIMAL budget; deadline; JSON questions; status. Queue backend uses queue_jobs to avoid conflict. |
| F8 | student_profiles → unique users; student_skills → profiles, skills; portfolio_items → profiles | Unique profile/skill; proficiency; score computed, never stored. |
| F15 | verification_requests → student users, nullable reviewer users | Each upload is an immutable historical record after review; generated private path, MIME, decision/reason/time. |
| F2/F11 | proposals → jobs, student users | Unique job/student; bid, timeline, cover letter, screening answers, status and review/rejection fields. |
| F5 | applications → jobs, student users; application_stage_histories → applications, actor users | Unique job/student; stage and offer expiry; actor/from/to/reason history. |
| F6 | interviews → applications, proposed_by users, optional prior interview | Proposed slot start/end, status, reschedule chain, opaque room reference. |
| F3/F4 | contracts → jobs, client/student users, optional proposal/application; milestones → contracts | Offer expiry, terms, budget, started/completed times; ordered milestones with amount/due date/revision limits/status. |
| F12 | submissions → milestones, student users | Unique milestone/version; private file path, message, review state/revision reason/time. |
| F13 | student_profiles; contracts; holiday_cache | Proposal credits and concurrent contract limit; holiday country/date/name/fetched timestamp. |
| F9/F10 | saved_searches → users; skills/profile/jobs and future reviews | JSON filters; rule-based recommendations need no ML or precomputed recommendation table. |
| F14/F16 | wallets → unique users; transactions → wallets, optional milestones; withdrawal_requests → wallets | Currency, decimal available/pending balances; unique idempotency/gateway references; transaction amount/fee/status; withdrawal amount/status/reference. |
| F17 | disputes → contracts, optional milestones, opened/resolved-by users; dispute_evidence → disputes, users | Status, reason, resolution, student split percentage, resolved time; evidence description/path. |
| F18 | reviews → contracts, author/recipient users | Unique contract/author; rating, comment, flagged. Reputation can be derived later. |
| F20 | reports → reporter/reviewer users, polymorphic reportable | Report target/status/reason/resolution/time; user suspended_at reserved. Statistics derive from relational data. |
| F19 | notifications → polymorphic notifiable | Laravel-compatible UUID/type/data/read_at/timestamps. Queue_jobs/job_batches/failed_jobs and cache/cache_locks are framework support tables. |

## Provisional future choices

The SRS describes entities, not a finalized ERD. The entire second migration is **provisional** and must be reviewed before later workflows ship. It is not a claim that later features are completed.

- Application and proposal are separate paths; a contract can reference either. Future service validation must enforce actor consistency and exactly one hiring source as appropriate.
- `started_at` is the authoritative F1 edit guard, independent of current contract state. Contract-start operations must lock the job first to serialize with JobService edits.
- Screening questions/answers are JSON arrays rather than separate question rows. Versioning/answer matching needs agreement before F11.
- Future status fields are strings; legal transitions, rating/split bounds, milestone sums, revision caps, credit deductions, workload caps and withdrawal limits are not enforced until their workflows are implemented. Foreign keys and stated uniqueness exist now; future domain constraints remain outstanding.
- Transactions are provisional wallet-ledger records. Future escrow may need a dedicated hold table or double-entry ledger, gateway callbacks, encrypted payout metadata, audit events, multiple currencies and fee configuration. The current wallet amounts default to zero; nothing changes them.
- Rescheduling links interview rows; there is no scheduling/token service. Disputes currently retain final resolution; a dedicated transition-history table may be needed later.
- Polymorphic report targets and notification recipients follow Laravel conventions; target ownership and valid types will require policies in later sprints.
- Holiday cache is local schema only; no Nager.Date request is made. Reviews/reputation, reports/analytics and notifications/scheduling have no routes or services.
- Account suspension storage exists but enforcement belongs to F20. No active suspension UI or seeded suspended account exists.
- Identity retention/withdrawal/revocation policies are unspecified; this sprint preserves submissions as requested. Real production lifecycle policies require a separate decision.

No future sample contracts/payments/proposals are seeded. The explicit contract guard test creates its own isolated contract record, and the escrow test checks all operations throw with no wallet/transaction writes.
