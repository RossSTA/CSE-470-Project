<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    public function submit(User $student, UploadedFile $file): void
    {
        abort_unless($student->role === 'student', 403);
        $path = null;
        try {
            DB::transaction(function () use ($student, $file, &$path) {
                User::whereKey($student->id)->lockForUpdate()->firstOrFail();
                if ($student->verifications()->whereIn('status', ['pending', 'approved'])->exists()) {
                    throw ValidationException::withMessages(['document' => 'A pending or approved submission already exists.']);
                }
                $path = $file->store('identity', 'identity');
                $student->verifications()->create(['document_path' => $path, 'mime_type' => $file->getMimeType(), 'status' => 'pending']);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('identity')->delete($path);
            }throw $e;
        }
    }

    public function review(VerificationRequest $verification, User $admin, array $data): void
    {
        abort_unless($admin->role === 'admin', 403);
        if (! in_array($data['decision'] ?? null, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose approval or rejection.']);
        }
        if ($data['decision'] === 'rejected' && trim($data['reason'] ?? '') === '') {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }
        DB::transaction(function () use ($verification, $admin, $data) {
            $v = VerificationRequest::whereKey($verification->id)->lockForUpdate()->firstOrFail();
            if ($v->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This submission has already been reviewed.']);
            }
            $v->update(['status' => $data['decision'], 'reason' => $data['decision'] === 'rejected' ? $data['reason'] : null, 'reviewer_id' => $admin->id, 'reviewed_at' => now()]);
        });
    }
}
