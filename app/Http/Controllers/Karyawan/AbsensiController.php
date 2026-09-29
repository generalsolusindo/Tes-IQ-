<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Karyawan\StoreAbsensiRequest;
use App\Models\Absensi;
use App\Models\JadwalKerja;
use App\Models\LokasiKantor;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbsensiController extends Controller
{
    private const MAX_GPS_ACCURACY_METERS = 50;

    /**
     * jam_masuk/jam_pulang in jadwal_kerja are wall-clock business hours in
     * Indonesia (WIB) regardless of the app's configured timezone (UTC).
     * "Telat"/"pulang cepat" must be judged against WIB wall-clock time, or
     * a karyawan clocking in at 15:23 local time can be misjudged as on
     * time simply because 15:23 WIB happens to be 08:23 in server UTC.
     */
    private const BUSINESS_TIMEZONE = 'Asia/Jakarta';

    public function masuk(StoreAbsensiRequest $request): RedirectResponse
    {
        $user = $request->user();
        $nowWib = Carbon::now(self::BUSINESS_TIMEZONE);
        $tanggal = $nowWib->toDateString();

        $absensi = Absensi::firstOrNew([
            'user_id' => $user->id,
            'tanggal' => $tanggal,
        ]);

        if ($absensi->exists && $absensi->jam_masuk) {
            throw ValidationException::withMessages([
                'photo' => 'Anda sudah absen masuk hari ini.',
            ]);
        }

        $this->assertGpsAccuracyIsUsable($request->float('accuracy'));

        $jarak = $this->assertWithinLokasiKantor(
            $user,
            $request->float('latitude'),
            $request->float('longitude'),
        );

        $jadwalKerja = $this->jadwalKerja($user);

        $batasTelat = $nowWib->copy()->startOfDay()
            ->setTimeFromTimeString($jadwalKerja->jam_masuk)
            ->addMinutes($jadwalKerja->toleransi_telat_menit);

        $absensi->fill([
            'user_id' => $user->id,
            'tanggal' => $tanggal,
            'jam_masuk' => now(),
            'latitude_masuk' => $request->float('latitude'),
            'longitude_masuk' => $request->float('longitude'),
            'akurasi_masuk_meter' => (int) round($request->float('accuracy')),
            'jarak_masuk_meter' => $jarak,
            'foto_masuk_path' => $this->storePhoto($request, $user, 'masuk', $tanggal),
            'status_masuk' => $nowWib->greaterThan($batasTelat) ? 'telat' : 'tepat_waktu',
        ]);

        try {
            $absensi->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'photo' => 'Anda sudah absen masuk hari ini.',
            ]);
        }

        return back();
    }

    public function pulang(StoreAbsensiRequest $request): RedirectResponse
    {
        $user = $request->user();
        $nowWib = Carbon::now(self::BUSINESS_TIMEZONE);
        $tanggal = $nowWib->toDateString();

        DB::transaction(function () use ($request, $user, $nowWib, $tanggal): void {
            // Locked so a second near-simultaneous clock-out (double tap,
            // network retry) waits for this one to finish instead of
            // silently overwriting it — clock-out updates an existing row,
            // so unlike clock-in there's no unique-constraint to catch it.
            $absensi = Absensi::where('user_id', $user->id)
                ->where('tanggal', $tanggal)
                ->lockForUpdate()
                ->first();

            if (! $absensi || ! $absensi->jam_masuk) {
                throw ValidationException::withMessages([
                    'photo' => 'Anda belum absen masuk hari ini.',
                ]);
            }

            if ($absensi->jam_pulang) {
                throw ValidationException::withMessages([
                    'photo' => 'Anda sudah absen pulang hari ini.',
                ]);
            }

            $this->assertGpsAccuracyIsUsable($request->float('accuracy'));

            $jarak = $this->assertWithinLokasiKantor(
                $user,
                $request->float('latitude'),
                $request->float('longitude'),
            );

            $jadwalPulang = $nowWib->copy()->startOfDay()
                ->setTimeFromTimeString($this->jadwalKerja($user)->jam_pulang);

            $absensi->fill([
                'jam_pulang' => now(),
                'latitude_pulang' => $request->float('latitude'),
                'longitude_pulang' => $request->float('longitude'),
                'akurasi_pulang_meter' => (int) round($request->float('accuracy')),
                'jarak_pulang_meter' => $jarak,
                'foto_pulang_path' => $this->storePhoto($request, $user, 'pulang', $tanggal),
                'status_pulang' => $nowWib->lessThan($jadwalPulang) ? 'pulang_cepat' : 'tepat_waktu',
            ])->save();
        });

        return back();
    }

    private function assertGpsAccuracyIsUsable(float $accuracyMeters): void
    {
        if ($accuracyMeters > self::MAX_GPS_ACCURACY_METERS) {
            throw ValidationException::withMessages([
                'accuracy' => sprintf(
                    'Akurasi GPS terlalu rendah (±%dm). Coba lagi di area terbuka.',
                    round($accuracyMeters),
                ),
            ]);
        }
    }

    private function assertWithinLokasiKantor(User $user, float $latitude, float $longitude): int
    {
        $lokasiKantor = $user->instansi->lokasiKantor;

        abort_if($lokasiKantor->isEmpty(), 500, 'Lokasi kantor belum diatur untuk instansi ini.');

        $nearest = $lokasiKantor
            ->map(fn (LokasiKantor $lokasi) => [
                'jarak' => $lokasi->distanceInMetersFrom($latitude, $longitude),
                'radius' => $lokasi->radius_meter,
            ])
            ->sortBy('jarak')
            ->first();

        if ($nearest['jarak'] > $nearest['radius']) {
            throw ValidationException::withMessages([
                'latitude' => sprintf(
                    'Anda berada sekitar %dm dari kantor, di luar radius %dm yang diizinkan.',
                    round($nearest['jarak']),
                    $nearest['radius'],
                ),
            ]);
        }

        return (int) round($nearest['jarak']);
    }

    private function jadwalKerja(User $user): JadwalKerja
    {
        $jadwalKerja = $user->instansi->jadwalKerja()->first();

        abort_if($jadwalKerja === null, 500, 'Jam kerja belum diatur untuk instansi ini.');

        return $jadwalKerja;
    }

    private function storePhoto(StoreAbsensiRequest $request, User $user, string $type, string $tanggal): string
    {
        $path = $request->file('photo')->storeAs(
            $user->id.'/'.$tanggal,
            $type.'.'.$request->file('photo')->extension(),
            'absensi_photos',
        );

        abort_if($path === false, 500, 'Gagal menyimpan foto absen. Coba lagi.');

        return $path;
    }
}
