<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Karyawan\StoreKaryawanRegistrationRequest;
use App\Models\Instansi;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredKaryawanController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Karyawan/Register', [
            'instansiOptions' => Instansi::query()
                ->with(['jabatan' => fn ($query) => $query->orderBy('nama')->select(['jabatan.id', 'jabatan.nama'])])
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn (Instansi $instansi) => [
                    'id' => $instansi->id,
                    'nama' => $instansi->nama,
                    'jabatan' => $instansi->jabatan->map(fn ($jabatan) => [
                        'id' => $jabatan->id,
                        'nama' => $jabatan->nama,
                    ]),
                ]),
        ]);
    }

    /**
     * Registration never selects a role and never sets status: role is
     * always karyawan, status falls back to the users table's 'pending'
     * default, matching the agreed "self-register waits for HRD" flow.
     */
    public function store(StoreKaryawanRegistrationRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'no_whatsapp' => $request->validated('no_whatsapp'),
            'instansi_id' => $request->validated('instansi_id'),
            'jabatan_id' => $request->validated('jabatan_id'),
            'password' => $request->validated('password'),
            'role' => User::ROLE_KARYAWAN,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('pending-approval');
    }
}
