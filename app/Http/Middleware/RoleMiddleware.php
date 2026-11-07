<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    public function handle($request, Closure $next, string ...$roles)
    {
        abort_unless(in_array($request->user()?->role, $roles), 403);

        return $next($request);
    }
}
