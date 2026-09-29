<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Instansi;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HrdAbsensiDashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestingDatabase();

        $this->withoutVite();
    }

    private function makeKaryawan(string $name): User
    {
        $instansi = Instansi::create(['nama' => 'PT Rekap '.uniqid()]);
        $jabatan = Jabatan::create(['nama' => 'Staff']);

        DB::table('instansi_jabatan')->insert([
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@example.com',
            'no_whatsapp' => '081200000001',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
            'status' => User::STATUS_AKTIF,
        ]);
    }

    public function test_admin_sees_recap_for_todays_date_by_default(): void
    {
        $admin = User::factory()->create();
        $sudahAbsen = $this->makeKaryawan('Sudah Absen');
        $belumAbsen = $this->makeKaryawan('Belum Absen');

        Absensi::create([
            'user_id' => $sudahAbsen->id,
            'tanggal' => today(),
            'jam_masuk' => now(),
            'status_masuk' => 'tepat_waktu',
        ]);

        $response = $this->actingAs($admin)->get(route('hrd.absensi.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('Hrd/AbsensiDashboard')
            ->where('tanggal', today()->toDateString())
            ->where('karyawan', fn ($list) => collect($list)->contains(
                fn ($item) => $item['id'] === $sudahAbsen->id && $item['status_masuk'] === 'tepat_waktu',
            ) && collect($list)->contains(
                fn ($item) => $item['id'] === $belumAbsen->id && $item['jam_masuk'] === null,
            )));
    }

    public function test_dashboard_filters_by_requested_date(): void
    {
        $admin = User::factory()->create();
        $karyawan = $this->makeKaryawan('Karyawan Filter');

        $kemarin = today()->subDay();

        Absensi::create([
            'user_id' => $karyawan->id,
            'tanggal' => $kemarin,
            'jam_masuk' => Carbon::parse($kemarin->toDateString().' 08:00:00'),
            'status_masuk' => 'tepat_waktu',
        ]);

        $response = $this->actingAs($admin)->get(route('hrd.absensi.index', ['tanggal' => $kemarin->toDateString()]));

        $response->assertInertia(fn ($page) => $page
            ->where('tanggal', $kemarin->toDateString())
            ->where('karyawan', fn ($list) => collect($list)->contains(
                fn ($item) => $item['id'] === $karyawan->id && $item['status_masuk'] === 'tepat_waktu',
            )));

        $responseHariIni = $this->actingAs($admin)->get(route('hrd.absensi.index'));

        $responseHariIni->assertInertia(fn ($page) => $page
            ->where('karyawan', fn ($list) => collect($list)->contains(
                fn ($item) => $item['id'] === $karyawan->id && $item['jam_masuk'] === null,
            )));
    }

    public function test_only_aktif_karyawan_are_listed_not_pending_or_rejected(): void
    {
        $admin = User::factory()->create();
        $aktif = $this->makeKaryawan('Karyawan Aktif');
        $pending = $this->makeKaryawan('Karyawan Pending');
        $pending->update(['status' => User::STATUS_PENDING]);

        $response = $this->actingAs($admin)->get(route('hrd.absensi.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('karyawan', fn ($list) => collect($list)->contains(fn ($item) => $item['id'] === $aktif->id)
                && ! collect($list)->contains(fn ($item) => $item['id'] === $pending->id)));
    }

    public function test_karyawan_cannot_access_hrd_absensi_dashboard(): void
    {
        $karyawan = $this->makeKaryawan('Karyawan Biasa');

        $this->actingAs($karyawan)
            ->get(route('hrd.absensi.index'))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_hrd_absensi_dashboard(): void
    {
        $this->get(route('hrd.absensi.index'))
            ->assertRedirect(route('login'));
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
            throw new RuntimeException('HRD absensi dashboard test safety guard failed before connecting.');
        }

        $actualDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($actualDatabase !== 'tes_iq_testing' || $actualDatabase === 'tes_iq') {
            throw new RuntimeException('HRD absensi dashboard test safety guard rejected the active database.');
        }
    }
}
