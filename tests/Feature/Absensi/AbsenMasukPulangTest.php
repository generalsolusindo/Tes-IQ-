<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Instansi;
use App\Models\Jabatan;
use App\Models\JadwalKerja;
use App\Models\LokasiKantor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AbsenMasukPulangTest extends TestCase
{
    use DatabaseTransactions;

    private const KANTOR_LAT = -6.200000;

    private const KANTOR_LNG = 106.816666;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestingDatabase();

        $this->withoutVite();
        Storage::fake('absensi_photos');
    }

    /**
     * @return array{0: User, 1: Instansi}
     */
    private function makeKaryawan(string $jamMasuk = '08:00:00', string $jamPulang = '17:00:00', int $toleransi = 0, int $radius = 100): array
    {
        $instansi = Instansi::create(['nama' => 'PT Absen '.uniqid()]);
        $jabatan = Jabatan::create(['nama' => 'Staff']);

        DB::table('instansi_jabatan')->insert([
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        LokasiKantor::create([
            'instansi_id' => $instansi->id,
            'latitude' => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'radius_meter' => $radius,
        ]);

        JadwalKerja::create([
            'instansi_id' => $instansi->id,
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => $jamPulang,
            'toleransi_telat_menit' => $toleransi,
        ]);

        $karyawan = User::create([
            'name' => 'Karyawan Absen',
            'email' => 'karyawan-absen-'.uniqid().'@example.com',
            'no_whatsapp' => '081200000000',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
            'status' => User::STATUS_AKTIF,
        ]);

        return [$karyawan, $instansi];
    }

    private function payload(?float $lat = null, ?float $lng = null, float $accuracy = 10): array
    {
        return [
            'latitude' => $lat ?? self::KANTOR_LAT,
            'longitude' => $lng ?? self::KANTOR_LNG,
            'accuracy' => $accuracy,
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
        ];
    }

    public function test_karyawan_can_clock_in_on_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05 07:55:00', 'Asia/Jakarta'));
        [$karyawan] = $this->makeKaryawan(jamMasuk: '08:00:00', toleransi: 0);

        $this->actingAs($karyawan)
            ->post(route('absen.masuk'), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('absensi', [
            'user_id' => $karyawan->id,
            'tanggal' => '2026-01-05',
            'status_masuk' => 'tepat_waktu',
        ]);

        $absensi = Absensi::where('user_id', $karyawan->id)->first();
        $this->assertNotNull($absensi->jam_masuk);
        $this->assertSame(0, $absensi->jarak_masuk_meter);
        $this->assertNotNull($absensi->foto_masuk_path);
        Storage::disk('absensi_photos')->assertExists($absensi->foto_masuk_path);

        Carbon::setTestNow();
    }

    public function test_karyawan_clocking_in_after_tolerance_is_marked_telat(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05 08:16:00', 'Asia/Jakarta'));
        [$karyawan] = $this->makeKaryawan(jamMasuk: '08:00:00', toleransi: 15);

        $this->actingAs($karyawan)->post(route('absen.masuk'), $this->payload());

        $this->assertDatabaseHas('absensi', [
            'user_id' => $karyawan->id,
            'status_masuk' => 'telat',
        ]);

        Carbon::setTestNow();
    }

    public function test_karyawan_clocking_in_exactly_at_tolerance_deadline_is_not_telat(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05 08:15:00', 'Asia/Jakarta'));
        [$karyawan] = $this->makeKaryawan(jamMasuk: '08:00:00', toleransi: 15);

        $this->actingAs($karyawan)->post(route('absen.masuk'), $this->payload());

        $this->assertDatabaseHas('absensi', [
            'user_id' => $karyawan->id,
            'status_masuk' => 'tepat_waktu',
        ]);

        Carbon::setTestNow();
    }

    public function test_clock_in_rejected_when_gps_accuracy_too_low(): void
    {
        [$karyawan] = $this->makeKaryawan();

        $response = $this->actingAs($karyawan)
            ->post(route('absen.masuk'), $this->payload(accuracy: 51));

        $response->assertSessionHasErrors('accuracy');
        $this->assertDatabaseMissing('absensi', ['user_id' => $karyawan->id]);
    }

    public function test_clock_in_accepted_at_exactly_max_accuracy_threshold(): void
    {
        [$karyawan] = $this->makeKaryawan();

        $this->actingAs($karyawan)
            ->post(route('absen.masuk'), $this->payload(accuracy: 50))
            ->assertRedirect();

        $this->assertDatabaseHas('absensi', ['user_id' => $karyawan->id]);
    }

    public function test_clock_in_rejected_when_outside_office_radius(): void
    {
        [$karyawan] = $this->makeKaryawan(radius: 100);

        // ~1 degree away is roughly 111km, far outside any sane radius.
        $response = $this->actingAs($karyawan)
            ->post(route('absen.masuk'), $this->payload(lat: self::KANTOR_LAT + 1, lng: self::KANTOR_LNG));

        $response->assertSessionHasErrors('latitude');
        $this->assertDatabaseMissing('absensi', ['user_id' => $karyawan->id]);
    }

    public function test_clock_in_picks_nearest_of_multiple_lokasi_kantor(): void
    {
        [$karyawan, $instansi] = $this->makeKaryawan(radius: 50);

        // A second, farther office location with a huge radius: if the
        // controller picked this one instead of the nearest, the request
        // would wrongly succeed even from right outside the near office.
        LokasiKantor::create([
            'instansi_id' => $instansi->id,
            'latitude' => self::KANTOR_LAT + 5,
            'longitude' => self::KANTOR_LNG + 5,
            'radius_meter' => 999999,
        ]);

        $response = $this->actingAs($karyawan)
            ->post(route('absen.masuk'), $this->payload(lat: self::KANTOR_LAT + 1, lng: self::KANTOR_LNG));

        $response->assertSessionHasErrors('latitude');
    }

    public function test_karyawan_cannot_clock_in_twice_same_day(): void
    {
        [$karyawan] = $this->makeKaryawan();

        $this->actingAs($karyawan)->post(route('absen.masuk'), $this->payload());
        $response = $this->actingAs($karyawan)->post(route('absen.masuk'), $this->payload());

        $response->assertSessionHasErrors('photo');
        $this->assertSame(1, Absensi::where('user_id', $karyawan->id)->count());
    }

    public function test_karyawan_can_clock_out_on_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05 08:00:00', 'Asia/Jakarta'));
        [$karyawan] = $this->makeKaryawan(jamPulang: '17:00:00');
        $this->actingAs($karyawan)->post(route('absen.masuk'), $this->payload());

        Carbon::setTestNow(Carbon::parse('2026-01-05 17:05:00', 'Asia/Jakarta'));
        $this->actingAs($karyawan)
            ->post(route('absen.pulang'), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('absensi', [
            'user_id' => $karyawan->id,
            'status_pulang' => 'tepat_waktu',
        ]);

        Carbon::setTestNow();
    }

    public function test_karyawan_clocking_out_before_schedule_is_pulang_cepat(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05 08:00:00', 'Asia/Jakarta'));
        [$karyawan] = $this->makeKaryawan(jamPulang: '17:00:00');
        $this->actingAs($karyawan)->post(route('absen.masuk'), $this->payload());

        Carbon::setTestNow(Carbon::parse('2026-01-05 15:00:00', 'Asia/Jakarta'));
        $this->actingAs($karyawan)->post(route('absen.pulang'), $this->payload());

        $this->assertDatabaseHas('absensi', [
            'user_id' => $karyawan->id,
            'status_pulang' => 'pulang_cepat',
        ]);

        Carbon::setTestNow();
    }

    public function test_cannot_clock_out_without_clocking_in_first(): void
    {
        [$karyawan] = $this->makeKaryawan();

        $response = $this->actingAs($karyawan)
            ->post(route('absen.pulang'), $this->payload());

        $response->assertSessionHasErrors('photo');
        $this->assertDatabaseMissing('absensi', ['user_id' => $karyawan->id]);
    }

    public function test_cannot_clock_out_twice_same_day(): void
    {
        [$karyawan] = $this->makeKaryawan();
        $this->actingAs($karyawan)->post(route('absen.masuk'), $this->payload());
        $this->actingAs($karyawan)->post(route('absen.pulang'), $this->payload());

        $response = $this->actingAs($karyawan)
            ->post(route('absen.pulang'), $this->payload());

        $response->assertSessionHasErrors('photo');
    }

    public function test_clock_out_reuses_fresh_gps_and_is_still_radius_checked(): void
    {
        [$karyawan] = $this->makeKaryawan(radius: 100);
        $this->actingAs($karyawan)->post(route('absen.masuk'), $this->payload());

        $response = $this->actingAs($karyawan)
            ->post(route('absen.pulang'), $this->payload(lat: self::KANTOR_LAT + 1, lng: self::KANTOR_LNG));

        $response->assertSessionHasErrors('latitude');
        $this->assertDatabaseHas('absensi', ['user_id' => $karyawan->id]);
        $absensi = Absensi::where('user_id', $karyawan->id)->first();
        $this->assertNull($absensi->jam_pulang);
    }

    public function test_guest_cannot_access_absen_routes(): void
    {
        $this->post(route('absen.masuk'), $this->payload())
            ->assertRedirect(route('login'));
    }

    public function test_non_karyawan_role_cannot_access_absen_routes(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('absen.masuk'), $this->payload())
            ->assertForbidden();
    }

    public function test_pending_karyawan_cannot_access_absen_routes(): void
    {
        [$karyawan] = $this->makeKaryawan();
        $karyawan->update(['status' => User::STATUS_PENDING]);

        $this->actingAs($karyawan)
            ->post(route('absen.masuk'), $this->payload())
            ->assertRedirect(route('pending-approval'));
    }

    public function test_dashboard_reflects_todays_attendance_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05 07:55:00', 'Asia/Jakarta'));
        [$karyawan] = $this->makeKaryawan();

        $this->actingAs($karyawan)
            ->get(route('karyawan.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Karyawan/Dashboard')
                ->where('todayAttendance', null));

        $this->actingAs($karyawan)->post(route('absen.masuk'), $this->payload());

        $this->actingAs($karyawan)
            ->get(route('karyawan.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Karyawan/Dashboard')
                ->where('todayAttendance.status_masuk', 'tepat_waktu')
                ->where('todayAttendance.jam_pulang', null));

        Carbon::setTestNow();
    }

    private function assertSafeTestingDatabase(): void
    {
        $environment = app()->environment();
        $connection = config('database.default');
        $configuredDatabase = config("database.connections.{$connection}.database");

        if ($environment !== 'testing'
            || $connection !== 'mysql'
            || $configuredDatabase !== 'tes_iq_testing'
            || $configuredDatabase === 'tes_iq') {
            throw new RuntimeException('Absen masuk/pulang test safety guard failed before connecting.');
        }

        $actualDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($actualDatabase !== 'tes_iq_testing' || $actualDatabase === 'tes_iq') {
            throw new RuntimeException('Absen masuk/pulang test safety guard rejected the active database.');
        }
    }
}
