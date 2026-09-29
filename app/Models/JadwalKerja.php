<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalKerja extends Model
{
    protected $table = 'jadwal_kerja';

    protected $fillable = [
        'instansi_id',
        'jam_masuk',
        'jam_pulang',
        'toleransi_telat_menit',
    ];

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }
}
