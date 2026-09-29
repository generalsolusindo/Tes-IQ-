<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $todayAttendance = $request->user()->absensi()
            ->where('tanggal', today())
            ->first();

        return Inertia::render('Karyawan/Dashboard', [
            'todayAttendance' => $todayAttendance ? [
                'jam_masuk' => $todayAttendance->jam_masuk,
                'status_masuk' => $todayAttendance->status_masuk,
                'jam_pulang' => $todayAttendance->jam_pulang,
                'status_pulang' => $todayAttendance->status_pulang,
            ] : null,
        ]);
    }
}
