<?php

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\User;

class StudentProfilePolicy
{
    public function update(User $u, StudentProfile $profile): bool
    {
        return $u->role === 'student' && $profile->user_id === $u->id;
    }
}
