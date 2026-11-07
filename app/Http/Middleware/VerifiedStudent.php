<?php

namespace App\Http\Middleware;

use Closure;

class VerifiedStudent
{
    public function handle($request, Closure $next)
    {
        abort_unless($request->user()?->isVerified(), 403, 'Approved student identity required.');

        return $next($request);
    }
}
