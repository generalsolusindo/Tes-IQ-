<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class AbsensiDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = Validator::make($request->only('tanggal'), [
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
        ])->valid();

        $tanggal = isset($validated['tanggal'])
            ? Carbon::createFromFormat('Y-m-d', $validated['tanggal'])->startOfDay()
            : today();

        $karyawan = User::query()
            ->where('role', User::ROLE_KARYAWAN)
            ->where('status', User::STATUS_AKTIF)
            ->with(['instansi:id,nama', 'jabatan:id,nama'])
            ->with(['absensi' => fn ($query) => $query->where('tanggal', $tanggal)])
            ->orderBy('name')
            ->get(['id', 'name', 'instansi_id', 'jabatan_id'])
            ->map(fn (User $user) => $this->presentKaryawan($user, $user->absensi->first()));

        return Inertia::render('Hrd/AbsensiDashboard', [
            'tanggal' => $tanggal->toDateString(),
            'karyawan' => $karyawan,
        ]);
    }

    /**
     * @return array{id: int, name: string, instansi: ?string, jabatan: ?string, jam_masuk: ?string, status_masuk: ?string, foto_masuk_url: ?string, jam_pulang: ?string, status_pulang: ?string, foto_pulang_url: ?string}
     */
    private function presentKaryawan(User $user, ?Absensi $absensi): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'instansi' => $user->instansi?->nama,
            'jabatan' => $user->jabatan?->nama,
            'jam_masuk' => $absensi?->jam_masuk,
            'status_masuk' => $absensi?->status_masuk,
            'foto_masuk_url' => $absensi?->fotoMasukUrl(),
            'jam_pulang' => $absensi?->jam_pulang,
            'status_pulang' => $absensi?->status_pulang,
            'foto_pulang_url' => $absensi?->fotoPulangUrl(),
        ];
    }
}
