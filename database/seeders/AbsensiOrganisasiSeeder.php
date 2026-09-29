<?php

namespace Database\Seeders;

use App\Models\Instansi;
use App\Models\JadwalKerja;
use App\Models\Jabatan;
use App\Models\LokasiKantor;
use Illuminate\Database\Seeder;

class AbsensiOrganisasiSeeder extends Seeder
{
    /**
     * General Solusindo and Tabinaco share one physical office (the same
     * Google Maps pin for "General Solusindo"), so each gets its own
     * instansi + lokasi_kantor row pointing at identical coordinates,
     * per the agreed schema where lokasi_kantor is never unique per
     * instansi.
     */
    public function run(): void
    {
        $jabatanList = collect(['Sales', 'Marketing', 'Finance', 'Admin/HRD'])
            ->map(fn (string $nama) => Jabatan::firstOrCreate(['nama' => $nama]));

        foreach (['General Solusindo', 'Tabinaco'] as $namaInstansi) {
            $instansi = Instansi::firstOrCreate(['nama' => $namaInstansi]);

            LokasiKantor::firstOrCreate(
                [
                    'instansi_id' => $instansi->id,
                    'latitude' => -7.4433990,
                    'longitude' => 112.7021927,
                ],
                ['radius_meter' => 50],
            );

            JadwalKerja::firstOrCreate(
                ['instansi_id' => $instansi->id],
                [
                    'jam_masuk' => '08:30:00',
                    'jam_pulang' => '16:30:00',
                    'toleransi_telat_menit' => 10,
                ],
            );

            foreach ($jabatanList as $jabatan) {
                $instansi->jabatan()->syncWithoutDetaching($jabatan->id);
            }
        }
    }
}
