<?php

namespace App\Services;

use App\Models\User;

class ProposalEligibility
{
    public function allows(User $student): bool
    {
        return $student->isVerified();
    }
}
