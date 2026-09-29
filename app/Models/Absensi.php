<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_masuk',
        'latitude_masuk',
        'longitude_masuk',
        'akurasi_masuk_meter',
        'jarak_masuk_meter',
        'foto_masuk_path',
        'status_masuk',
        'jam_pulang',
        'latitude_pulang',
        'longitude_pulang',
        'akurasi_pulang_meter',
        'jarak_pulang_meter',
        'foto_pulang_path',
        'status_pulang',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_masuk' => 'datetime',
            'jam_pulang' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fotoMasukUrl(): ?string
    {
        return $this->foto_masuk_path
            ? Storage::disk('absensi_photos')->temporaryUrl($this->foto_masuk_path, now()->addMinutes(5))
            : null;
    }

    public function fotoPulangUrl(): ?string
    {
        return $this->foto_pulang_path
            ? Storage::disk('absensi_photos')->temporaryUrl($this->foto_pulang_path, now()->addMinutes(5))
            : null;
    }
}
