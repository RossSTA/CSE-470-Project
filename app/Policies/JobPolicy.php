<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function view(User $u, Job $job): bool
    {
        return $u->role === 'admin' || $job->client_id === $u->id || ($u->role === 'student' && $job->status === 'published');
    }

    public function update(User $u, Job $job): bool
    {
        return $u->role === 'client' && $job->client_id === $u->id;
    }
}
