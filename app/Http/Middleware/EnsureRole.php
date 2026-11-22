<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets the request through only if the logged-in user has the given role.
 * Usage in routes: ->middleware('role:admin')
 */
class EnsureRole
{
    /**
     * Block the request with 403 if the user's role does not match.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($request->user()?->role === $role, 403);

        return $next($request);
    }
}
