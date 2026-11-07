<?php

namespace App\Policies;

use App\Models\PortfolioItem;
use App\Models\User;

class PortfolioItemPolicy
{
    public function update(User $u, PortfolioItem $portfolioItem): bool
    {
        return $u->role === 'student' && $portfolioItem->profile->user_id === $u->id;
    }
}
