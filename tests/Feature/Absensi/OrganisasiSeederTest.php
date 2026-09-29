<?php

namespace Tests\Feature\Absensi;

use App\Models\Instansi;
use App\Models\Jabatan;
use App\Models\User;
use Database\Seeders\AbsensiOrganisasiSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies the real General Solusindo / Tabinaco coordinates seeded by
 * AbsensiOrganisasiSeeder actually work end-to-end through the clock-in
 * flow, not just that the rows exist in the database. Runs the seeder
 * itself in setUp() rather than relying on a prior manual `db:seed` —
 * other test classes in this suite use RefreshDatabase, which wipes
 * tes_iq_testing back to a bare migration state, so any externally
 * seeded data doesn't survive between test runs.
 */
class OrganisasiSeederTest extends TestCase
{
    use DatabaseTransactions;

    private const KANTOR_LAT = -7.4433990;

    private const KANTOR_LNG = 112.7021927;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestingDatabase();

        $this->withoutVite();
        Storage::fake('absensi_photos');

        $this->seed(AbsensiOrganisasiSeeder::class);
    }

    public function test_general_solusindo_and_tabinaco_are_seeded_with_the_agreed_coordinates(): void
    {
        foreach (['General Solusindo', 'Tabinaco'] as $nama) {
            $instansi = Instansi::where('nama', $nama)->firstOrFail();
            $lokasi = $instansi->lokasiKantor->first();

            $this->assertNotNull($lokasi, "{$nama} belum punya lokasi_kantor.");
            $this->assertEquals(self::KANTOR_LAT, (float) $lokasi->latitude, '', 0.0000001);
            $this->assertEquals(self::KANTOR_LNG, (float) $lokasi->longitude, '', 0.0000001);
            $this->assertSame(50, $lokasi->radius_meter);

            $jadwal = $instansi->jadwalKerja()->first();
            $this->assertNotNull($jadwal, "{$nama} belum punya jadwal_kerja.");
            $this->assertSame('08:30:00', $jadwal->jam_masuk);
            $this->assertSame('16:30:00', $jadwal->jam_pulang);
            $this->assertSame(10, $jadwal->toleransi_telat_menit);

            foreach (['Sales', 'Marketing', 'Finance', 'Admin/HRD'] as $namaJabatan) {
                $this->assertTrue(
                    $instansi->jabatan()->where('nama', $namaJabatan)->exists(),
                    "{$nama} belum punya jabatan {$namaJabatan}.",
                );
            }
        }
    }

    public function test_karyawan_at_general_solusindo_can_clock_in_standing_exactly_at_the_seeded_pin(): void
    {
        $instansi = Instansi::where('nama', 'General Solusindo')->firstOrFail();
        $jabatan = Jabatan::where('nama', 'Sales')->firstOrFail();

        $karyawan = User::create([
            'name' => 'Karyawan General Solusindo',
            'email' => 'karyawan-gs-'.uniqid().'@example.com',
            'no_whatsapp' => '081200000010',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
            'status' => User::STATUS_AKTIF,
        ]);

        $response = $this->actingAs($karyawan)->post(route('absen.masuk'), [
            'latitude' => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy' => 15,
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('absensi', [
            'user_id' => $karyawan->id,
            'jarak_masuk_meter' => 0,
        ]);
    }

    public function test_karyawan_at_tabinaco_clock_in_is_rejected_just_outside_the_50m_radius(): void
    {
        $instansi = Instansi::where('nama', 'Tabinaco')->firstOrFail();
        $jabatan = Jabatan::where('nama', 'Marketing')->firstOrFail();

        $karyawan = User::create([
            'name' => 'Karyawan Tabinaco',
            'email' => 'karyawan-tabinaco-'.uniqid().'@example.com',
            'no_whatsapp' => '081200000011',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
            'status' => User::STATUS_AKTIF,
        ]);

        // ~0.001 degrees latitude is roughly 111 meters, safely past the 50m radius.
        $response = $this->actingAs($karyawan)->post(route('absen.masuk'), [
            'latitude' => self::KANTOR_LAT + 0.001,
            'longitude' => self::KANTOR_LNG,
            'accuracy' => 15,
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertSessionHasErrors('latitude');
        $this->assertDatabaseMissing('absensi', ['user_id' => $karyawan->id]);
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
            throw new RuntimeException('Organisasi seeder test safety guard failed before connecting.');
        }

        $actualDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($actualDatabase !== 'tes_iq_testing' || $actualDatabase === 'tes_iq') {
            throw new RuntimeException('Organisasi seeder test safety guard rejected the active database.');
        }
    }
}
