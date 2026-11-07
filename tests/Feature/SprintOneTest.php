<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Job;
use App\Models\Skill;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Services\EscrowService;
use App\Services\ProposalEligibility;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SprintOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('identity');
    }

    private function user(string $role = 'student'): User
    {
        $u = User::factory()->create();
        $u->role = $role;
        $u->save();
        if ($role === 'student') {
            $u->profile()->create();
        }

        return $u;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['title' => 'Build a website', 'description' => 'Accessible website for a campus club.', 'type' => 'gig', 'budget' => '500.25', 'deadline' => today()->addDays(10)->toDateString(), 'skills' => [Skill::firstOrCreate(['name' => 'Laravel'])->id], 'screening_questions' => ['What have you built?']], $overrides);
    }

    private function job(User $client): Job
    {
        $this->actingAs($client)->post('/jobs', $this->payload())->assertRedirect();

        return Job::latest('id')->firstOrFail();
    }

    private function document(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('id.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }

    private function submit(User $student): VerificationRequest
    {
        $this->actingAs($student)->post('/verification', ['document' => $this->document()])->assertSessionHasNoErrors();

        return $student->verifications()->latest('id')->firstOrFail();
    }

    public function test_student_and_client_registration_and_admin_rejection(): void
    {
        foreach (['student', 'client'] as $role) {
            $this->post('/register', ['name' => 'New User', 'email' => $role.'@test.test', 'password' => 'SafePass!123', 'password_confirmation' => 'SafePass!123', 'role' => $role])->assertRedirect('/dashboard');
            $this->assertAuthenticated();
            $u = User::where('email', $role.'@test.test')->firstOrFail();
            $this->assertSame($role, $u->role);
            $this->assertSame($role === 'student', $u->profile()->exists());
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
        $this->post('/register', ['name' => 'Bad Admin', 'email' => 'admin@test.test', 'password' => 'SafePass!123', 'password_confirmation' => 'SafePass!123', 'role' => 'admin'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'admin@test.test']);
    }

    public function test_login_logout_and_bad_password(): void
    {
        $u = $this->user();
        $this->post('/login', ['email' => $u->email, 'password' => 'bad'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout');
        $this->assertGuest();
    }

    public function test_password_reset_uses_broker_and_changes_password(): void
    {
        Notification::fake();
        $u = $this->user();
        $this->post('/forgot-password', ['email' => $u->email])->assertSessionHas('success');
        Notification::assertSentTo($u, ResetPassword::class, function ($notification) use ($u) {
            $this->post('/reset-password', ['token' => $notification->token, 'email' => $u->email, 'password' => 'NewPassword!123', 'password_confirmation' => 'NewPassword!123'])->assertRedirect('/login');

            return true;
        });
        $this->assertTrue(Hash::check('NewPassword!123', $u->fresh()->password));
        $this->post('/reset-password', ['token' => 'invalid', 'email' => $u->email, 'password' => 'AnotherPass!123', 'password_confirmation' => 'AnotherPass!123'])->assertSessionHasErrors('email');
    }

    public function test_guests_and_wrong_roles_are_blocked(): void
    {
        $this->get('/client/jobs')->assertRedirect('/login');
        $student = $this->user();
        $this->actingAs($student)->get('/jobs/create')->assertForbidden();
        $this->post('/jobs', $this->payload())->assertForbidden();
        $this->get('/admin/verifications')->assertForbidden();
        $client = $this->user('client');
        $this->actingAs($client)->get('/verification')->assertForbidden();
        $this->post('/verification', ['document' => $this->document()])->assertForbidden();
        $this->get('/jobs')->assertForbidden();
        $this->actingAs($this->user('admin'))->get('/jobs/create')->assertForbidden();
    }

    public function test_gig_and_internship_persist_and_publish(): void
    {
        $client = $this->user('client');
        foreach (['gig', 'internship'] as $type) {
            $data = $this->payload(['type' => $type]);
            $this->actingAs($client)->post('/jobs', $data)->assertRedirect();
            $job = Job::latest('id')->firstOrFail();
            $this->assertSame('draft', $job->status);
            $this->assertSame('500.25', $job->budget);
            $this->assertSame($data['screening_questions'], $job->screening_questions);
            $this->assertSame($data['skills'], $job->skills->pluck('id')->all());
            $this->post("/jobs/$job->id/publish")->assertSessionHasNoErrors();
            $this->assertSame('published', $job->fresh()->status);
            $this->actingAs($this->user())->get("/jobs/$job->id")->assertOk()->assertSee($job->title);
        }
    }

    public function test_job_invalid_budgets_deadlines_and_relationships(): void
    {
        $this->actingAs($this->user('client'));
        foreach ([['budget' => 0], ['budget' => -1], ['budget' => '1.001'], ['deadline' => today()->toDateString()], ['deadline' => today()->subDay()->toDateString()], ['skills' => []], ['skills' => [999]], ['screening_questions' => ['']]] as $invalid) {
            $this->post('/jobs', $this->payload($invalid))->assertSessionHasErrors();
        }$this->assertDatabaseCount('jobs', 0);
    }

    public function test_job_update_syncs_skills_questions_and_isolation(): void
    {
        $client = $this->user('client');
        $job = $this->job($client);
        $otherSkill = Skill::create(['name' => 'Design']);
        $this->put("/jobs/$job->id", $this->payload(['title' => 'Updated', 'skills' => [$otherSkill->id], 'screening_questions' => ['One', 'Two']]))->assertSessionHasNoErrors();
        $this->assertSame('Updated', $job->fresh()->title);
        $this->assertSame([$otherSkill->id], $job->fresh()->skills->pluck('id')->all());
        $this->assertSame(['One', 'Two'], $job->fresh()->screening_questions);
    }

    public function test_job_lifecycle_is_one_way_and_publish_rechecks_deadline(): void
    {
        $job = $this->job($this->user('client'));
        $this->post("/jobs/$job->id/close")->assertSessionHasErrors('status');
        $job->deadline = today()->subDay();
        $job->save();
        $this->post("/jobs/$job->id/publish")->assertSessionHasErrors('deadline');
        $job->deadline = today()->addDays(2);
        $job->save();
        $this->post("/jobs/$job->id/publish")->assertSessionHasNoErrors();
        $this->post("/jobs/$job->id/publish")->assertSessionHasErrors('status');
        $this->post("/jobs/$job->id/close")->assertSessionHasNoErrors();
        $this->post("/jobs/$job->id/publish")->assertSessionHasErrors('status');
        $this->post("/jobs/$job->id/close")->assertSessionHasErrors('status');
        $this->put("/jobs/$job->id", $this->payload())->assertSessionHasErrors('job');
    }

    public function test_job_owner_authorization_and_draft_privacy(): void
    {
        $owner = $this->user('client');
        $job = $this->job($owner);
        $this->actingAs($this->user('client'));
        foreach (["/jobs/$job->id", "/jobs/$job->id/edit"] as $url) {
            $this->get($url)->assertForbidden();
        }$this->put("/jobs/$job->id", $this->payload())->assertForbidden();
        $this->post("/jobs/$job->id/publish")->assertForbidden();
        $this->post("/jobs/$job->id/close")->assertForbidden();
        $this->get('/client/jobs')->assertDontSee($job->title);
        $this->actingAs($this->user())->get("/jobs/$job->id")->assertForbidden();
        $this->get('/jobs')->assertDontSee($job->title);
        $this->actingAs($this->user('admin'))->get("/jobs/$job->id")->assertOk();
        $this->actingAs($owner)->post("/jobs/$job->id/publish");
        $this->actingAs($this->user())->get('/jobs')->assertSee($job->title);
        $this->actingAs($owner)->post("/jobs/$job->id/close");
        $this->actingAs($this->user())->get("/jobs/$job->id")->assertForbidden();
    }

    public function test_started_contract_blocks_edit_even_when_completed(): void
    {
        $client = $this->user('client');
        $student = $this->user();
        $job = $this->job($client);
        Contract::create(['job_id' => $job->id, 'client_id' => $client->id, 'student_id' => $student->id, 'budget' => 500, 'started_at' => now(), 'status' => 'completed']);
        $this->get("/jobs/$job->id/edit")->assertForbidden();
        $this->put("/jobs/$job->id", $this->payload(['title' => 'Hacked']))->assertSessionHasErrors('job');
        $this->assertNotSame('Hacked', $job->fresh()->title);
    }

    public function test_profile_basics_and_completeness_from_persisted_data(): void
    {
        $s = $this->user();
        $p = $s->profile;
        $this->assertSame(10, $p->completeness()['score']);
        $this->actingAs($s)->put("/profiles/$p->id", ['name' => 'Student Name', 'university' => 'BRAC', 'department' => 'CSE', 'bio' => 'I build web apps', 'availability' => 'part-time', 'verified' => true, 'completeness' => 100])->assertSessionHasNoErrors();
        $this->assertSame(60, $p->fresh()->completeness()['score']);
        $this->assertFalse($s->isVerified());
        $skill = Skill::create(['name' => 'PHP']);
        $this->post("/profiles/$p->id/skills", ['skill_id' => $skill->id, 'proficiency' => 'beginner'])->assertSessionHasNoErrors();
        $this->assertSame(80, $p->fresh()->completeness()['score']);
        $this->post("/profiles/$p->id/projects", ['title' => 'Website', 'description' => 'My site', 'url' => 'https://example.com'])->assertSessionHasNoErrors();
        $this->assertSame(100, $p->fresh()->completeness()['score']);
        $item = $p->portfolioItems()->firstOrFail();
        $this->put("/projects/$item->id", ['title' => 'Updated project', 'description' => 'Better', 'url' => 'http://example.com'])->assertSessionHasNoErrors();
        $this->assertSame('Updated project', $item->fresh()->title);
        $this->get("/projects/$item->id/edit")->assertOk();
        $this->delete("/projects/$item->id")->assertRedirect();
        $this->assertSame(80, $p->fresh()->completeness()['score']);
        $ss = $p->skills()->firstOrFail();
        $this->put("/profiles/$p->id/skills/$ss->id", ['skill_id' => $skill->id, 'proficiency' => 'advanced'])->assertSessionHasNoErrors();
        $this->assertSame('advanced', $ss->fresh()->proficiency);
        $this->delete("/profiles/$p->id/skills/$ss->id");
        $this->assertSame(60, $p->fresh()->completeness()['score']);
    }

    public function test_skill_duplicates_proficiency_and_urls_are_rejected(): void
    {
        $s = $this->user();
        $p = $s->profile;
        $skill = Skill::create(['name' => 'Laravel']);
        $this->actingAs($s)->post("/profiles/$p->id/skills", ['skill_id' => $skill->id, 'proficiency' => 'expert'])->assertSessionHasErrors('proficiency');
        $this->post("/profiles/$p->id/skills", ['skill_id' => $skill->id, 'proficiency' => 'intermediate'])->assertSessionHasNoErrors();
        $this->post("/profiles/$p->id/skills", ['skill_id' => $skill->id, 'proficiency' => 'advanced'])->assertSessionHasErrors('skill_id');
        $this->assertDatabaseCount('student_skills', 1);
        foreach (['bad', 'javascript:alert(1)', 'ftp://example.com'] as $url) {
            $this->post("/profiles/$p->id/projects", ['title' => 'Test', 'description' => 'Test', 'url' => $url])->assertSessionHasErrors('url');
        }$this->assertDatabaseCount('portfolio_items', 0);
    }

    public function test_profile_skill_unique_constraint(): void
    {
        $s = $this->user();
        $skill = Skill::create(['name' => 'PHP']);
        $s->profile->skills()->create(['skill_id' => $skill->id, 'proficiency' => 'beginner']);
        $this->expectException(UniqueConstraintViolationException::class);
        $s->profile->skills()->create(['skill_id' => $skill->id, 'proficiency' => 'advanced']);
    }

    public function test_all_profile_mutations_require_ownership(): void
    {
        $owner = $this->user();
        $p = $owner->profile;
        $skill = Skill::create(['name' => 'PHP']);
        $ss = $p->skills()->create(['skill_id' => $skill->id, 'proficiency' => 'beginner']);
        $project = $p->portfolioItems()->create(['title' => 'Private owner project', 'description' => 'Demo', 'url' => 'https://example.com']);
        $this->actingAs($this->user());
        $this->get("/profiles/$p->id/edit")->assertForbidden();
        $this->put("/profiles/$p->id", ['name' => 'Hacked'])->assertForbidden();
        $this->post("/profiles/$p->id/skills", ['skill_id' => $skill->id, 'proficiency' => 'advanced'])->assertForbidden();
        $this->put("/profiles/$p->id/skills/$ss->id", ['skill_id' => $skill->id, 'proficiency' => 'advanced'])->assertForbidden();
        $this->delete("/profiles/$p->id/skills/$ss->id")->assertForbidden();
        $this->post("/profiles/$p->id/projects", ['title' => 'Hack', 'description' => 'Hack', 'url' => 'https://example.com'])->assertForbidden();
        $this->get("/projects/$project->id/edit")->assertForbidden();
        $this->put("/projects/$project->id", ['title' => 'Hack', 'description' => 'Hack', 'url' => 'https://example.com'])->assertForbidden();
        $this->delete("/projects/$project->id")->assertForbidden();
        $this->assertSame('beginner', $ss->fresh()->proficiency);
        $this->assertSame('Private owner project', $project->fresh()->title);
    }

    public function test_nested_skill_must_belong_to_target_profile(): void
    {
        $s = $this->user();
        $other = $this->user();
        $skill = Skill::create(['name' => 'PHP']);
        $ss = $other->profile->skills()->create(['skill_id' => $skill->id, 'proficiency' => 'beginner']);
        $this->actingAs($s)->delete('/profiles/'.$s->profile->id.'/skills/'.$ss->id)->assertNotFound();
        $this->put('/profiles/'.$s->profile->id.'/skills/'.$ss->id, ['skill_id' => $skill->id, 'proficiency' => 'advanced'])->assertNotFound();
    }

    public function test_profile_display_omits_private_identity_and_account_data(): void
    {
        $s = $this->user();
        $v = $this->submit($s);
        $this->actingAs($this->user('client'))->get('/profiles/'.$s->profile->id)->assertOk()->assertDontSee($v->document_path)->assertDontSee($s->email)->assertDontSee($s->password);
    }

    public function test_valid_upload_pending_duplicate_block_and_private_storage(): void
    {
        $s = $this->user();
        $v = $this->submit($s);
        $this->assertSame('pending', $v->status);
        $this->assertStringStartsWith('identity/', $v->document_path);
        $this->assertStringNotContainsString('id.png', $v->document_path);
        Storage::disk('identity')->assertExists($v->document_path);
        $this->post('/verification', ['document' => $this->document()])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('verification_requests', 1);
        $this->assertFalse(app(ProposalEligibility::class)->allows($s));
        $this->get('/verification')->assertOk()->assertSee('pending');
    }

    public function test_upload_mime_content_size_and_missing_validation(): void
    {
        $this->actingAs($this->user());
        foreach ([UploadedFile::fake()->createWithContent('fake.png', 'This is not an image'), UploadedFile::fake()->create('id.exe', 2, 'application/octet-stream'), UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')] as $file) {
            $this->post('/verification', ['document' => $file])->assertSessionHasErrors('document');
        }$this->post('/verification')->assertSessionHasErrors('document');
        $this->assertDatabaseCount('verification_requests', 0);
    }

    public function test_real_pdf_and_jpeg_content_are_accepted(): void
    {
        foreach (['pdf' => "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF", 'jpg' => base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EB//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EB//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/EB//2Q==')] as $ext => $content) {
            $s = $this->user();
            $this->actingAs($s)->post('/verification', ['document' => UploadedFile::fake()->createWithContent('id.'.$ext, $content)])->assertSessionHasNoErrors();
            $this->assertSame('pending', $s->verifications()->firstOrFail()->status);
        }
    }

    public function test_approval_records_audit_and_unlocks_reusable_verification_guard(): void
    {
        $student = $this->user();
        $v = $this->submit($student);
        $admin = $this->user('admin');
        Route::middleware(['web', 'auth', 'verified.student'])->get('/test-eligibility', fn () => 'eligible');
        $this->get('/test-eligibility')->assertForbidden();
        $this->actingAs($admin)->post("/admin/verifications/$v->id/review", ['decision' => 'approved'])->assertSessionHasNoErrors();
        $v->refresh();
        $this->assertSame('approved', $v->status);
        $this->assertSame($admin->id, $v->reviewer_id);
        $this->assertNotNull($v->reviewed_at);
        $this->assertTrue(app(ProposalEligibility::class)->allows($student));
        $this->actingAs($student)->get('/test-eligibility')->assertOk();
        $this->post('/verification', ['document' => $this->document()])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('verification_requests', 1);
    }

    public function test_rejection_reason_and_resubmission_preserve_history(): void
    {
        $student = $this->user();
        $v = $this->submit($student);
        $admin = $this->user('admin');
        $this->actingAs($admin)->post("/admin/verifications/$v->id/review", ['decision' => 'rejected', 'reason' => '  '])->assertSessionHasErrors('reason');
        $this->assertSame('pending', $v->fresh()->status);
        $this->post("/admin/verifications/$v->id/review", ['decision' => 'rejected', 'reason' => 'ID is unreadable'])->assertSessionHasNoErrors();
        $this->assertFalse(app(ProposalEligibility::class)->allows($student));
        $this->actingAs($student)->get('/verification')->assertSee('ID is unreadable');
        $new = $this->submit($student);
        $this->assertNotSame($new->id, $v->id);
        $this->assertSame('pending', $new->status);
        $this->assertSame('rejected', $v->fresh()->status);
        $this->assertSame('ID is unreadable', $v->fresh()->reason);
        Storage::disk('identity')->assertExists($v->document_path);
        $this->assertDatabaseCount('verification_requests', 2);
    }

    public function test_repeated_and_conflicting_reviews_cannot_change_audit(): void
    {
        $v = $this->submit($this->user());
        $admin = $this->user('admin');
        $this->actingAs($admin)->post("/admin/verifications/$v->id/review", ['decision' => 'approved'])->assertSessionHasNoErrors();
        $original = $v->fresh()->getAttributes();
        $this->actingAs($this->user('admin'));
        foreach (['approved', 'rejected'] as $decision) {
            $this->post("/admin/verifications/$v->id/review", ['decision' => $decision, 'reason' => 'Conflict'])->assertSessionHasErrors('decision');
        }$this->assertSame($original, $v->fresh()->getAttributes());
    }

    public function test_private_document_and_review_authorization(): void
    {
        $student = $this->user();
        $v = $this->submit($student);
        $this->get("/verification/$v->id/document")->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->post("/admin/verifications/$v->id/review", ['decision' => 'approved'])->assertForbidden();
        foreach ([$this->user(), $this->user('client')] as $other) {
            $this->actingAs($other)->get("/verification/$v->id/document")->assertForbidden();
            $this->get("/admin/verifications/$v->id")->assertForbidden();
            $this->post("/admin/verifications/$v->id/review", ['decision' => 'approved'])->assertForbidden();
        }$this->actingAs($this->user('admin'))->get("/verification/$v->id/document")->assertOk();
        $this->get('/admin/verifications')->assertOk();
        $this->get("/admin/verifications/$v->id")->assertOk();
        $this->assertSame('pending', $v->fresh()->status);
    }

    public function test_role_dashboards_and_views_render(): void
    {
        foreach (['student', 'client', 'admin'] as $role) {
            $this->actingAs($this->user($role))->get('/dashboard')->assertOk()->assertSee(ucfirst($role).' workspace')->assertDontSee('@else');
        } $s = $this->user();
        $this->actingAs($s)->get('/profiles/'.$s->profile->id.'/edit')->assertOk();
        $this->actingAs($this->user('client'))->get('/jobs/create')->assertOk();
    }

    public function test_seeders_repeat_without_duplicates(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('jobs', 2);
        $this->assertDatabaseCount('skills', 7);
        $this->assertDatabaseCount('student_profiles', 1);
        $this->assertDatabaseCount('portfolio_items', 1);
        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_production_seeder_creates_catalog_without_demo_accounts(): void
    {
        $this->app->instance('env', 'production');
        $this->app->make(DatabaseSeeder::class)->run();
        $this->assertDatabaseCount('skills', 7);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_escrow_stub_explicitly_fails_without_balance_changes(): void
    {
        $service = app(EscrowService::class);
        foreach (['fund' => [1, '10.00', 'key'], 'hold' => [1, 'ref'], 'release' => [1, 'key'], 'refund' => [1, 'key']] as $method => $args) {
            try {
                $service->$method(...$args);
                $this->fail('Stub must throw');
            } catch (\LogicException $e) {
                $this->assertStringContainsString('not implemented', $e->getMessage());
            }
        }$this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('wallets', 0);
    }
}
