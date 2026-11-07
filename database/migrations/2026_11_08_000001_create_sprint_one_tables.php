<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('student')->index();
        });
        Schema::create('skills', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::create('student_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('university')->nullable();
            $t->string('department')->nullable();
            $t->text('bio')->nullable();
            $t->string('availability')->nullable();
            $t->unsignedInteger('proposal_credits')->default(10);
            $t->unsignedTinyInteger('contract_limit')->default(3);
            $t->timestamps();
        });
        Schema::create('student_skills', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $t->foreignId('skill_id')->constrained();
            $t->string('proficiency');
            $t->unique(['student_profile_id', 'skill_id']);
            $t->timestamps();
        });
        Schema::create('portfolio_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('description');
            $t->string('url', 2048);
            $t->timestamps();
        });
        Schema::create('jobs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained('users');
            $t->string('title');
            $t->text('description');
            $t->string('type');
            $t->decimal('budget', 12, 2);
            $t->date('deadline');
            $t->json('screening_questions');
            $t->string('status')->default('draft')->index();
            $t->timestamps();
        });
        Schema::create('job_skills', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_id')->constrained()->cascadeOnDelete();
            $t->foreignId('skill_id')->constrained();
            $t->unique(['job_id', 'skill_id']);
            $t->timestamps();
        });
        Schema::create('verification_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('document_path');
            $t->string('mime_type');
            $t->string('status')->default('pending')->index();
            $t->foreignId('reviewer_id')->nullable()->constrained('users');
            $t->timestamp('reviewed_at')->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['verification_requests', 'job_skills', 'jobs', 'portfolio_items', 'student_skills', 'student_profiles', 'skills'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropIndex(['role']);
            $t->dropColumn('role');
        });
    }
};
