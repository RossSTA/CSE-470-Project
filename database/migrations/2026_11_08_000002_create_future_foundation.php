<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_id')->constrained();
            $t->foreignId('student_id')->constrained('users');
            $t->decimal('bid', 12, 2);
            $t->unsignedInteger('timeline_days');
            $t->text('cover_letter');
            $t->json('screening_answers')->nullable();
            $t->string('status')->default('submitted');
            $t->text('rejection_reason')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->unique(['job_id', 'student_id']);
            $t->timestamps();
        });
        Schema::create('applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_id')->constrained();
            $t->foreignId('student_id')->constrained('users');
            $t->string('stage')->default('applied');
            $t->timestamp('offer_expires_at')->nullable();
            $t->unique(['job_id', 'student_id']);
            $t->timestamps();
        });
        Schema::create('application_stage_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained();
            $t->foreignId('actor_id')->constrained('users');
            $t->string('from_stage')->nullable();
            $t->string('to_stage');
            $t->text('reason')->nullable();
            $t->timestamps();
        });
        Schema::create('interviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained();
            $t->foreignId('proposed_by')->constrained('users');
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->string('status')->default('proposed');
            $t->foreignId('rescheduled_from')->nullable()->constrained('interviews');
            $t->string('room_reference')->nullable();
            $t->timestamps();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_id')->constrained();
            $t->foreignId('client_id')->constrained('users');
            $t->foreignId('student_id')->constrained('users');
            $t->foreignId('proposal_id')->nullable()->constrained();
            $t->foreignId('application_id')->nullable()->constrained();
            $t->decimal('budget', 12, 2);
            $t->text('terms')->nullable();
            $t->string('status')->default('offered');
            $t->timestamp('offer_expires_at')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('milestones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contract_id')->constrained();
            $t->string('title');
            $t->text('description')->nullable();
            $t->decimal('amount', 12, 2);
            $t->date('due_date');
            $t->unsignedInteger('position');
            $t->unsignedTinyInteger('revision_limit')->default(2);
            $t->unsignedTinyInteger('revision_count')->default(0);
            $t->string('status')->default('planned');
            $t->unique(['contract_id', 'position']);
            $t->timestamps();
        });
        Schema::create('submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('milestone_id')->constrained();
            $t->foreignId('student_id')->constrained('users');
            $t->unsignedInteger('version');
            $t->text('message')->nullable();
            $t->string('file_path');
            $t->string('status')->default('submitted');
            $t->text('revision_reason')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->unique(['milestone_id', 'version']);
            $t->timestamps();
        });
        Schema::create('wallets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained();
            $t->string('currency', 3)->default('BDT');
            $t->decimal('available_balance', 14, 2)->default(0);
            $t->decimal('pending_balance', 14, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('wallet_id')->constrained();
            $t->foreignId('milestone_id')->nullable()->constrained();
            $t->string('type');
            $t->decimal('amount', 14, 2);
            $t->decimal('fee', 14, 2)->default(0);
            $t->string('status')->default('pending');
            $t->string('idempotency_key')->unique();
            $t->string('gateway_reference')->nullable()->unique();
            $t->timestamps();
        });
        Schema::create('withdrawal_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('wallet_id')->constrained();
            $t->decimal('amount', 14, 2);
            $t->string('status')->default('pending');
            $t->text('payout_reference')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('saved_searches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('name');
            $t->json('filters');
            $t->timestamps();
        });
        Schema::create('disputes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contract_id')->constrained();
            $t->foreignId('milestone_id')->nullable()->constrained();
            $t->foreignId('opened_by')->constrained('users');
            $t->text('reason');
            $t->string('status')->default('open');
            $t->foreignId('resolved_by')->nullable()->constrained('users');
            $t->text('resolution')->nullable();
            $t->decimal('student_percentage', 5, 2)->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
        Schema::create('dispute_evidence', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dispute_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->text('description')->nullable();
            $t->string('file_path')->nullable();
            $t->timestamps();
        });
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contract_id')->constrained();
            $t->foreignId('author_id')->constrained('users');
            $t->foreignId('recipient_id')->constrained('users');
            $t->unsignedTinyInteger('rating');
            $t->text('comment')->nullable();
            $t->boolean('flagged')->default(false);
            $t->unique(['contract_id', 'author_id']);
            $t->timestamps();
        });
        Schema::create('reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reporter_id')->constrained('users');
            $t->nullableMorphs('reportable');
            $t->text('reason');
            $t->string('status')->default('open');
            $t->foreignId('reviewer_id')->nullable()->constrained('users');
            $t->text('resolution')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('holiday_cache', function (Blueprint $t) {
            $t->id();
            $t->string('country_code', 2);
            $t->date('date');
            $t->string('name');
            $t->timestamp('fetched_at');
            $t->unique(['country_code', 'date', 'name']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->timestamp('suspended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('suspended_at'));
        foreach (['holiday_cache', 'notifications', 'reports', 'reviews', 'dispute_evidence', 'disputes', 'saved_searches', 'withdrawal_requests', 'transactions', 'wallets', 'submissions', 'milestones', 'contracts', 'interviews', 'application_stage_histories', 'applications', 'proposals'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
