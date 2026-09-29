<?php

namespace Tests\Feature\Absensi;

use App\Models\Instansi;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class KaryawanRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestingDatabase();

        $this->withoutVite();
    }

    private function makeInstansiWithJabatan(string $instansiNama, string $jabatanNama): array
    {
        $instansi = Instansi::create(['nama' => $instansiNama]);
        $jabatan = Jabatan::create(['nama' => $jabatanNama]);

        DB::table('instansi_jabatan')->insert([
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$instansi, $jabatan];
    }

    public function test_registration_page_lists_instansi_and_their_jabatan(): void
    {
        [$instansi, $jabatan] = $this->makeInstansiWithJabatan('PT Contoh Satu', 'Sales');

        $this->get(route('karyawan.register'))
            ->assertInertia(fn ($page) => $page
                ->component('Karyawan/Register')
                ->where('instansiOptions', fn ($options) => collect($options)->contains(
                    fn ($item) => $item['nama'] === $instansi->nama
                        && collect($item['jabatan'])->contains(fn ($j) => $j['nama'] === $jabatan->nama),
                )));
    }

    public function test_karyawan_can_register_and_lands_on_pending_approval(): void
    {
        [$instansi, $jabatan] = $this->makeInstansiWithJabatan('PT Contoh Dua', 'Marketing');

        $response = $this->post(route('karyawan.register.store'), [
            'name' => 'Budi Karyawan',
            'email' => 'budi@example.com',
            'no_whatsapp' => '081234567890',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('pending-approval'));

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role' => User::ROLE_KARYAWAN,
            'status' => User::STATUS_PENDING,
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
        ]);

        $this->assertAuthenticatedAs(User::where('email', 'budi@example.com')->first());
    }

    public function test_registration_rejects_jabatan_not_attached_to_chosen_instansi(): void
    {
        [$instansiA] = $this->makeInstansiWithJabatan('PT Contoh Tiga', 'Finance');
        [, $jabatanB] = $this->makeInstansiWithJabatan('PT Contoh Empat', 'Procurement');

        $response = $this->post(route('karyawan.register.store'), [
            'name' => 'Salah Pilih',
            'email' => 'salah@example.com',
            'no_whatsapp' => '081234567891',
            'instansi_id' => $instansiA->id,
            'jabatan_id' => $jabatanB->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('jabatan_id');
        $this->assertDatabaseMissing('users', ['email' => 'salah@example.com']);
    }

    public function test_registration_endpoint_is_rate_limited(): void
    {
        [$instansi, $jabatan] = $this->makeInstansiWithJabatan('PT Contoh Throttle', 'Sales');

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('karyawan.register.store'), [
                'name' => "Percobaan {$i}",
                'email' => "percobaan{$i}@example.com",
                'no_whatsapp' => '081200000009',
                'instansi_id' => $instansi->id,
                'jabatan_id' => $jabatan->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            Auth::logout();
        }

        $response = $this->post(route('karyawan.register.store'), [
            'name' => 'Percobaan Ke-11',
            'email' => 'percobaan11@example.com',
            'no_whatsapp' => '081200000009',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(429);
    }

    public function test_admin_can_approve_pending_karyawan(): void
    {
        $admin = User::factory()->create();
        [$instansi, $jabatan] = $this->makeInstansiWithJabatan('PT Contoh Lima', 'Content Creator');

        $karyawan = User::create([
            'name' => 'Menunggu Approve',
            'email' => 'menunggu@example.com',
            'no_whatsapp' => '081234567892',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
        ]);

        $this->assertSame(User::STATUS_PENDING, $karyawan->fresh()->status);

        $this->actingAs($admin)
            ->from(route('hrd.karyawan.index'))
            ->patch(route('hrd.karyawan.approve', $karyawan))
            ->assertRedirect(route('hrd.karyawan.index'));

        $this->assertSame(User::STATUS_AKTIF, $karyawan->fresh()->status);
    }

    public function test_admin_can_reject_pending_karyawan(): void
    {
        $admin = User::factory()->create();
        [$instansi, $jabatan] = $this->makeInstansiWithJabatan('PT Contoh Enam', 'Finance');

        $karyawan = User::create([
            'name' => 'Ditolak',
            'email' => 'ditolak@example.com',
            'no_whatsapp' => '081234567893',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
        ]);

        $this->actingAs($admin)
            ->from(route('hrd.karyawan.index'))
            ->patch(route('hrd.karyawan.reject', $karyawan))
            ->assertRedirect(route('hrd.karyawan.index'));

        $this->assertSame(User::STATUS_DITOLAK, $karyawan->fresh()->status);
    }

    public function test_merchant_cannot_access_hrd_karyawan_approval(): void
    {
        $merchant = User::factory()->create(['role' => User::ROLE_MERCHANT]);

        $this->actingAs($merchant)
            ->get(route('hrd.karyawan.index'))
            ->assertForbidden();
    }

    public function test_pending_karyawan_login_redirects_to_pending_approval_not_dashboard(): void
    {
        [$instansi, $jabatan] = $this->makeInstansiWithJabatan('PT Contoh Tujuh', 'Sales');

        User::create([
            'name' => 'Belum Aktif',
            'email' => 'belum-aktif@example.com',
            'no_whatsapp' => '081234567894',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
        ]);

        $this->post(route('login'), [
            'email' => 'belum-aktif@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('pending-approval'));
    }

    public function test_active_karyawan_login_redirects_to_karyawan_dashboard(): void
    {
        [$instansi, $jabatan] = $this->makeInstansiWithJabatan('PT Contoh Delapan', 'Marketing');

        User::create([
            'name' => 'Sudah Aktif',
            'email' => 'sudah-aktif@example.com',
            'no_whatsapp' => '081234567895',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
            'status' => User::STATUS_AKTIF,
        ]);

        $this->post(route('login'), [
            'email' => 'sudah-aktif@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('karyawan.dashboard'));
    }

    public function test_active_karyawan_can_view_dashboard_but_admin_route_is_forbidden(): void
    {
        [$instansi, $jabatan] = $this->makeInstansiWithJabatan('PT Contoh Sembilan', 'Finance');

        $karyawan = User::create([
            'name' => 'Karyawan Dashboard',
            'email' => 'karyawan-dashboard@example.com',
            'no_whatsapp' => '081234567896',
            'instansi_id' => $instansi->id,
            'jabatan_id' => $jabatan->id,
            'password' => 'password123',
            'role' => User::ROLE_KARYAWAN,
            'status' => User::STATUS_AKTIF,
        ]);

        $this->actingAs($karyawan)
            ->get(route('karyawan.dashboard'))
            ->assertInertia(fn ($page) => $page->component('Karyawan/Dashboard'));

        $this->actingAs($karyawan)
            ->get(route('dashboard'))
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
            throw new RuntimeException('Absensi karyawan test safety guard failed before connecting.');
        }

        $actualDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($actualDatabase !== 'tes_iq_testing' || $actualDatabase === 'tes_iq') {
            throw new RuntimeException('Absensi karyawan test safety guard rejected the active database.');
        }
    }
}
