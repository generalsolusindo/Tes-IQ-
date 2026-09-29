<?php

use App\Http\Controllers\Hrd\AbsensiDashboardController;
use App\Http\Controllers\Hrd\KaryawanApprovalController;
use App\Http\Controllers\Karyawan\AbsensiController;
use App\Http\Controllers\Karyawan\DashboardController;
use App\Http\Controllers\Karyawan\RegisteredKaryawanController;
use App\Http\Controllers\Karyawan\RiwayatAbsensiController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('karyawan/daftar', [RegisteredKaryawanController::class, 'create'])
        ->name('karyawan.register');

    Route::post('karyawan/daftar', [RegisteredKaryawanController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('karyawan.register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('pending-approval', fn () => inertia('Karyawan/PendingApproval', [
        'status' => request()->user()->status,
    ]))->name('pending-approval');

    Route::middleware(['active', 'karyawan'])->group(function (): void {
        Route::get('karyawan/dashboard', [DashboardController::class, 'index'])
            ->name('karyawan.dashboard');

        Route::post('absen/masuk', [AbsensiController::class, 'masuk'])
            ->middleware('throttle:30,1')
            ->name('absen.masuk');

        Route::post('absen/pulang', [AbsensiController::class, 'pulang'])
            ->middleware('throttle:30,1')
            ->name('absen.pulang');

        Route::get('riwayat-absen', [RiwayatAbsensiController::class, 'index'])
            ->name('riwayat-absen.index');
    });

    Route::middleware('absensi.hrd')
        ->prefix('hrd')
        ->name('hrd.')
        ->group(function (): void {
            Route::get('karyawan', [KaryawanApprovalController::class, 'index'])
                ->name('karyawan.index');

            Route::patch('karyawan/{user}/approve', [KaryawanApprovalController::class, 'approve'])
                ->name('karyawan.approve');

            Route::patch('karyawan/{user}/reject', [KaryawanApprovalController::class, 'reject'])
                ->name('karyawan.reject');

            Route::get('absensi', [AbsensiDashboardController::class, 'index'])
                ->name('absensi.index');
        });
});
