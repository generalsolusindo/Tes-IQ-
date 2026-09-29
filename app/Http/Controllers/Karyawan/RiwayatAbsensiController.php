<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatAbsensiController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = Validator::make($request->only('bulan'), [
            'bulan' => ['nullable', 'date_format:Y-m'],
        ])->valid();

        $bulan = isset($validated['bulan'])
            ? Carbon::createFromFormat('Y-m', $validated['bulan'])
            : today();

        $riwayat = $request->user()->absensi()
            ->whereYear('tanggal', $bulan->year)
            ->whereMonth('tanggal', $bulan->month)
            ->orderByDesc('tanggal')
            ->get()
            ->map(fn (Absensi $absensi) => [
                'tanggal' => $absensi->tanggal->toDateString(),
                'jam_masuk' => $absensi->jam_masuk,
                'status_masuk' => $absensi->status_masuk,
                'jam_pulang' => $absensi->jam_pulang,
                'status_pulang' => $absensi->status_pulang,
            ]);

        return Inertia::render('Karyawan/RiwayatAbsensi', [
            'bulan' => $bulan->format('Y-m'),
            'riwayat' => $riwayat,
        ]);
    }
}
