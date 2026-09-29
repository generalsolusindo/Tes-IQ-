<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        // Merchant keeps its existing redirect; any other role (e.g. hrd,
        // karyawan) has no admin-adjacent home to bounce to yet, so it gets
        // a plain 403 instead of looping back and forth with EnsureUserIsMerchant.
        if ($user && $user->isMerchant()) {
            return redirect()->route('merchant.dashboard');
        }

        abort(403);
    }
}
