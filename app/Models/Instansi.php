<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instansi extends Model
{
    protected $table = 'instansi';

    protected $fillable = [
        'nama',
    ];

    public function jabatan(): BelongsToMany
    {
        return $this->belongsToMany(Jabatan::class, 'instansi_jabatan');
    }

    public function lokasiKantor(): HasMany
    {
        return $this->hasMany(LokasiKantor::class);
    }

    public function jadwalKerja(): HasMany
    {
        return $this->hasMany(JadwalKerja::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
