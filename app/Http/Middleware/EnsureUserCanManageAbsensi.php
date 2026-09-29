<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageAbsensi
{
    /**
     * Gates the two absensi-only HRD actions (approve registration, view
     * attendance recap) to role hrd or admin, without granting hrd any of
     * the other admin-only Tes-IQ features.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->canManageAbsensi(), 403);

        return $next($request);
    }
}
