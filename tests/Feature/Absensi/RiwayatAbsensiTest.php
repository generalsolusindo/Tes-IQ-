<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Instansi;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class RiwayatAbsensiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestingDatabase();

        $this->withoutVite();
    }

    private function makeKaryawan(): User
    {
        $instansi = Instansi::create(['nama' => 'PT Riwayat '.uniqid()]);
        $jabatan = Jabatan::create(['nama' => 'Staff']);

        DB::table('instansi_jabatan')->insert([
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::create([
            'name' => 'Karyawan Riwayat',
            'email' => 'karyawan-riwayat-'.uniqid().'@example.com',
            'no_whatsapp' => '081200000002',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
            'status' => User::STATUS_AKTIF,
        ]);
    }

    public function test_karyawan_sees_only_their_own_attendance_for_current_month_by_default(): void
    {
        $karyawan = $this->makeKaryawan();
        $orangLain = $this->makeKaryawan();

        Absensi::create([
            'user_id' => $karyawan->id,
            'tanggal' => today(),
            'jam_masuk' => now(),
            'status_masuk' => 'tepat_waktu',
        ]);

        Absensi::create([
            'user_id' => $orangLain->id,
            'tanggal' => today(),
            'jam_masuk' => now(),
            'status_masuk' => 'telat',
        ]);

        $this->actingAs($karyawan)
            ->get(route('riwayat-absen.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Karyawan/RiwayatAbsensi')
                ->where('bulan', today()->format('Y-m'))
                ->where('riwayat', fn ($list) => count($list) === 1
                    && $list[0]['tanggal'] === today()->toDateString()
                    && $list[0]['status_masuk'] === 'tepat_waktu'));
    }

    public function test_riwayat_filters_by_requested_month(): void
    {
        $karyawan = $this->makeKaryawan();
        $bulanLalu = today()->subMonthNoOverflow();

        Absensi::create([
            'user_id' => $karyawan->id,
            'tanggal' => $bulanLalu,
            'jam_masuk' => now(),
            'status_masuk' => 'tepat_waktu',
        ]);

        $this->actingAs($karyawan)
            ->get(route('riwayat-absen.index', ['bulan' => $bulanLalu->format('Y-m')]))
            ->assertInertia(fn ($page) => $page
                ->where('bulan', $bulanLalu->format('Y-m'))
                ->where('riwayat', fn ($list) => count($list) === 1
                    && $list[0]['tanggal'] === $bulanLalu->toDateString()));

        $this->actingAs($karyawan)
            ->get(route('riwayat-absen.index'))
            ->assertInertia(fn ($page) => $page->where('riwayat', fn ($list) => count($list) === 0));
    }

    public function test_guest_cannot_access_riwayat(): void
    {
        $this->get(route('riwayat-absen.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_karyawan_riwayat_route(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('riwayat-absen.index'))
            ->assertForbidden();
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
            throw new RuntimeException('Riwayat absensi test safety guard failed before connecting.');
        }

        $actualDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($actualDatabase !== 'tes_iq_testing' || $actualDatabase === 'tes_iq') {
            throw new RuntimeException('Riwayat absensi test safety guard rejected the active database.');
        }
    }
}
