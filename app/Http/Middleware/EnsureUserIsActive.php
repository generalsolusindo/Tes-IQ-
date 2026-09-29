<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Keeps self-registered karyawan accounts out of the app until HRD
     * approves them, even though they're logged in right after registering.
     * Admin/merchant accounts are always 'aktif' (backfilled at migration
     * time), so this never affects them.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAktif()) {
            return redirect()->route('pending-approval');
        }

        return $next($request);
    }
}
