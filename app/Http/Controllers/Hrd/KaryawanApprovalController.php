<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class KaryawanApprovalController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Hrd/KaryawanApproval', [
            'pendingUsers' => User::query()
                ->where('role', User::ROLE_KARYAWAN)
                ->where('status', User::STATUS_PENDING)
                ->with(['instansi:id,nama', 'jabatan:id,nama'])
                ->oldest()
                ->get(['id', 'name', 'email', 'no_whatsapp', 'instansi_id', 'jabatan_id', 'created_at']),
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->isKaryawan(), 404);

        $user->update(['status' => User::STATUS_AKTIF]);

        return back()->with('success', "{$user->name} disetujui.");
    }

    public function reject(User $user): RedirectResponse
    {
        abort_unless($user->isKaryawan(), 404);

        $user->update(['status' => User::STATUS_DITOLAK]);

        return back()->with('success', "{$user->name} ditolak.");
    }
}
