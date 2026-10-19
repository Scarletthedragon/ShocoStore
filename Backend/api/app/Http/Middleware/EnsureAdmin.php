<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminEmail = config('app.admin_email');

        abort_unless(
            is_string($adminEmail) && $adminEmail !== '' && hash_equals($adminEmail, mb_strtolower($request->user()->Epasts)),
            403,
            'Admin access is not configured for this account.',
        );

        return $next($request);
    }
}
