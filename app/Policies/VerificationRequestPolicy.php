<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationRequest;

class VerificationRequestPolicy
{
    public function view(User $u, VerificationRequest $verification): bool
    {
        return $u->role === 'admin' || ($u->role === 'student' && $verification->user_id === $u->id);
    }

    public function review(User $u, VerificationRequest $verification): bool
    {
        return $u->role === 'admin';
    }
}
